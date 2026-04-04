<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\MaintenanceRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('front/home/index.html.twig');
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('front/home/about.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('front/home/contact.html.twig');
    }

    #[Route('/maintenance', name: 'app_maintenance')]
    public function maintenance(): Response
    {
        return $this->render('front/maintenance/maintenance.html.twig');
    }

    #[Route('/maintenance/{id_maintenance}/taches', name: 'app_maintenance_taches')]
    public function maintenanceTaches(MaintenanceRepository $maintenanceRepository, int $id_maintenance): Response
    {
        $maintenance = $maintenanceRepository->find($id_maintenance);
        
        if (!$maintenance) {
            throw $this->createNotFoundException('Maintenance non trouvée');
        }

        $totalCost = 0;
        foreach ($maintenance->getTaches() as $tache) {
            $cout = $tache->getCoutEstimee();
            $totalCost += is_numeric($cout) ? (float) $cout : (float) str_replace([',', ' '], ['.', ''], $cout);
        }

        return $this->render('front/maintenance/maintenance_taches.html.twig', [
            'maintenance' => $maintenance,
            'taches' => $maintenance->getTaches(),
            'totalCost' => $totalCost,
        ]);
    }

    #[Route('/maintenance/traiter', name: 'app_maintenance_traiter')]
    public function maintenanceATraiter(MaintenanceRepository $maintenanceRepository): Response
    {
        $maintenances = $maintenanceRepository->findBy(['statut' => 'En attente']);
        
        return $this->render('front/maintenance/interventions_a_traiter.html.twig', [
            'listeMaintenances' => $maintenances,
        ]);
    }

    #[Route('/evenements', name: 'app_evenements')]
    public function evenements(): Response
    {
        return $this->render('front/evenements/evenements.html.twig');
    }

    #[Route('/suivi-animal', name: 'app_suivi_animal')]
    public function suiviAnimal(): Response
    {
        return $this->render('front/suivi_animal/suivi_animal.html.twig');
    }

    #[Route('/achat-equipement', name: 'app_achat_equipement')]
    public function achatEquipement(): Response
    {
        return $this->render('front/achat_equipement/achat_equipement.html.twig');
    }

    #[Route('/ventes-depenses', name: 'app_ventes_depenses')]
    public function ventesDepenses(): Response
    {
        return $this->render('front/ventes_depenses/ventes_depenses.html.twig');
    }

    // ← AJOUTE CE QUI SUIT
    #[Route('/profil', name: 'app_profile')]
    public function profile(): Response
    {
        return $this->render('front/utilisateurs/profil.html.twig');
    }
    #[Route('/services', name: 'app_services')]
public function services(): Response
{
    return $this->render('front/home/services.html.twig');
}
#[Route('/tech', name: 'app_tech_home')]
public function techHome(): Response
{
    return $this->render('front/home/tech_home.html.twig');
}

    #[Route('/utilisateurs/login', name: 'front_login')]
    public function login(): Response
    {
        return $this->render('front/utilisateurs/login.html.twig');
    }

    #[Route('/utilisateurs/register', name: 'front_register')]
    public function register(): Response
    {
        return $this->render('front/utilisateurs/register.html.twig');
    }
}