<?php

namespace App\Controller;

use App\Entity\Depense;
use App\Entity\Vente;
use App\Form\DepenseType;
use App\Form\VenteType;
use App\Repository\UserRepository;
use App\Repository\VenteRepository;
use App\Repository\DepenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BackController extends AbstractController
{
    #[Route('/dash', name: 'back_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('back/base_back.html.twig');
    }

    #[Route('/back/maintenance', name: 'back_maintenance')]
    public function maintenance(): Response
    {
        return $this->render('back/maintenance/maintenance.html.twig');
    }

    #[Route('/back/equipements', name: 'back_equipements')]
    public function equipements(): Response
    {
        return $this->render('back/achat_equipement/equipement.html.twig');
    }

    #[Route('/back/evenements', name: 'back_evenements')]
    public function evenements(): Response
    {
        return $this->render('back/evenements/evenements.html.twig');
    }

    #[Route('/back/animaux', name: 'back_animaux')]
    public function animaux(): Response
    {
        return $this->render('back/suivi_animal/animal.html.twig');
    }

    #[Route('/back/ventes-depenses', name: 'back_ventes_depenses')]
    public function ventesDepenses(Request $request, UserRepository $userRepository, VenteRepository $venteRepository, DepenseRepository $depenseRepository): Response
    {
        $searchTerm = $request->query->get('q');
        $filterRole = $request->query->get('role');
        
        $queryBuilder = $userRepository->createQueryBuilder('u');
        
        if ($searchTerm) {
            $queryBuilder->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
        
        if ($filterRole !== null && $filterRole !== '') {
            $queryBuilder->andWhere('u.role = :role')
                ->setParameter('role', (int)$filterRole);
        }
        
        $users = $queryBuilder->getQuery()->getResult();
        
        // Calculate some basic stats for the dashboard
        $totalUsers = count($users);
        $totalExpensesAll = 0;
        
        $usersWithStats = [];
        foreach ($users as $user) {
            $userTotalExpenses = 0;
            foreach ($user->getDepenses() as $depense) {
                $userTotalExpenses += $depense->getMontant();
            }
            
            $userTotalVentes = 0;
            foreach ($user->getVentes() as $vente) {
                $userTotalVentes += $vente->getChiffreAffaires();
            }
            
            $usersWithStats[] = [
                'user' => $user,
                'totalExpenses' => $userTotalExpenses,
                'totalVentes' => $userTotalVentes,
                'grandTotal' => $userTotalVentes - $userTotalExpenses
            ];
            
            $totalExpensesAll += $userTotalExpenses;
        }

        return $this->render('back/ventes_depenses/ventes_depenses.html.twig', [
            'usersWithStats' => $usersWithStats,
            'totalUsers' => $totalUsers,
            'totalExpenses' => $totalExpensesAll,
            'searchTerm' => $searchTerm,
            'filterRole' => $filterRole
        ]);
    }

    #[Route('/back/ventes-depenses/{id}/details', name: 'back_user_details')]
    public function userDetails(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        return $this->render('back/ventes_depenses/vente_depense_details.html.twig', [
            'user' => $user,
            'ventes' => $user->getVentes(),
            'depenses' => $user->getDepenses(),
        ]);
    }

    #[Route('/back/ventes-depenses/{id}/analyse', name: 'back_user_analyse')]
    public function userAnalyse(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Data for Pie Chart (Expenses by Category)
        $expensesByCategory = [];
        foreach ($user->getDepenses() as $depense) {
            $category = $depense->getType();
            if (!isset($expensesByCategory[$category])) {
                $expensesByCategory[$category] = 0;
            }
            $expensesByCategory[$category] += $depense->getMontant();
        }

        // Data for Bar Chart (Expenses vs Sales by Month)
        $monthlyStats = [];
        foreach ($user->getDepenses() as $depense) {
            $month = $depense->getDate()->format('Y-m');
            if (!isset($monthlyStats[$month])) {
                $monthlyStats[$month] = ['expenses' => 0, 'sales' => 0];
            }
            $monthlyStats[$month]['expenses'] += $depense->getMontant();
        }
        foreach ($user->getVentes() as $vente) {
            $month = $vente->getDate()->format('Y-m');
            if (!isset($monthlyStats[$month])) {
                $monthlyStats[$month] = ['expenses' => 0, 'sales' => 0];
            }
            $monthlyStats[$month]['sales'] += $vente->getChiffreAffaires();
        }
        ksort($monthlyStats);

        return $this->render('back/ventes_depenses/vente_depense_analyse.html.twig', [
            'user' => $user,
            'expensesByCategory' => $expensesByCategory,
            'monthlyStats' => $monthlyStats,
        ]);
    }

    #[Route('/back/ventes-depenses/{id}/outils', name: 'back_user_outils')]
    public function userOutils(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        return $this->render('back/ventes_depenses/vente_depense_outils.html.twig', [
            'user' => $user,
            'ventes' => $user->getVentes(),
            'depenses' => $user->getDepenses(),
        ]);
    }

    #[Route('/back/ventes-depenses/{id}/add-depense', name: 'back_add_depense')]
    public function addDepense(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = $userRepository->find($id);
        if (!$user) throw $this->createNotFoundException('User not found');

        $depense = new Depense();
        $depense->setUser($user);
        $form = $this->createForm(DepenseType::class, $depense);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($depense);
            $em->flush();
            return $this->redirectToRoute('back_user_outils', ['id' => $id]);
        }

        return $this->render('back/ventes_depenses/add_depense.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'isEdit' => false
        ]);
    }

    #[Route('/back/depense/{id}/edit', name: 'back_edit_depense')]
    public function editDepense(int $id, Request $request, DepenseRepository $depenseRepository, EntityManagerInterface $em): Response
    {
        $depense = $depenseRepository->find($id);
        if (!$depense) throw $this->createNotFoundException('Dépense non trouvée');

        $user = $depense->getUser();
        $form = $this->createForm(DepenseType::class, $depense);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('back_user_outils', ['id' => $user->getId()]);
        }

        return $this->render('back/ventes_depenses/update_depense.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/back/depense/{id}/delete', name: 'back_delete_depense', methods: ['POST', 'GET'])]
    public function deleteDepense(int $id, DepenseRepository $depenseRepository, EntityManagerInterface $em): Response
    {
        $depense = $depenseRepository->find($id);
        if (!$depense) throw $this->createNotFoundException('Dépense non trouvée');

        $userId = $depense->getUser()->getId();
        $em->remove($depense);
        $em->flush();

        return $this->redirectToRoute('back_user_outils', ['id' => $userId]);
    }

    #[Route('/back/ventes-depenses/{id}/add-vente', name: 'back_add_vente')]
    public function addVente(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = $userRepository->find($id);
        if (!$user) throw $this->createNotFoundException('User not found');

        $vente = new Vente();
        $vente->setUser($user);
        $form = $this->createForm(VenteType::class, $vente);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Auto calculate chiffreAffaires
            $vente->setChiffreAffaires($vente->getPrixUnitaire() * $vente->getQuantite());
            $em->persist($vente);
            $em->flush();
            return $this->redirectToRoute('back_user_outils', ['id' => $id]);
        }

        return $this->render('back/ventes_depenses/add_vente.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'isEdit' => false
        ]);
    }

    #[Route('/back/vente/{id}/edit', name: 'back_edit_vente')]
    public function editVente(int $id, Request $request, VenteRepository $venteRepository, EntityManagerInterface $em): Response
    {
        $vente = $venteRepository->find($id);
        if (!$vente) throw $this->createNotFoundException('Vente non trouvée');

        $user = $vente->getUser();
        $form = $this->createForm(VenteType::class, $vente);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Recalculate if changed
            $vente->setChiffreAffaires($vente->getPrixUnitaire() * $vente->getQuantite());
            $em->flush();
            return $this->redirectToRoute('back_user_outils', ['id' => $user->getId()]);
        }

        return $this->render('back/ventes_depenses/update_vente.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/back/vente/{id}/delete', name: 'back_delete_vente', methods: ['POST', 'GET'])]
    public function deleteVente(int $id, VenteRepository $venteRepository, EntityManagerInterface $em): Response
    {
        $vente = $venteRepository->find($id);
        if (!$vente) throw $this->createNotFoundException('Vente non trouvée');

        $userId = $vente->getUser()->getId();
        $em->remove($vente);
        $em->flush();

        return $this->redirectToRoute('back_user_outils', ['id' => $userId]);
    }

    #[Route('/back/utilisateurs', name: 'back_utilisateurs')]
    public function utilisateurs(): Response
    {
        return $this->render('back/utilisateurs/utilisateurs.html.twig');
    }

    #[Route('/back/profile', name: 'back_profile')]
    public function profile(): Response
    {
        return $this->render('back/utilisateurs/profile.html.twig');
    }

}