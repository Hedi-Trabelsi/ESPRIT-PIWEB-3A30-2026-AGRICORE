<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\AnimalRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\TacheRepository;
use App\Repository\EvennementagricoleRepository;
use App\Repository\ParticipantsRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Knp\Component\Pager\PaginatorInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(
        Request $request, 
        EntityManagerInterface $em,
        MaintenanceRepository $maintenanceRepo,
        TacheRepository $tacheRepo,
        ParticipantsRepository $participantsRepo,
        EvennementagricoleRepository $eventRepo
    ): Response
    {
        $sessionUser = $request->getSession()->get('user');
        $isAgriculteur = false;
        $calendarEvents = [];
        
        if ($sessionUser instanceof User) {
            $userId = $sessionUser->getId();
            $isAgriculteur = (int)$sessionUser->getRole() === 1;
            
            if ($isAgriculteur && $userId !== null) {
                // Get subscribed events
                $participations = $participantsRepo->findBy(['id_utilisateur' => $userId]);
                foreach ($participations as $p) {
                    $ev = $p->getEvenement();
                    if ($ev && $p->getStatutParticipation() !== 'waitlist') {
                        $calendarEvents[] = [
                            'title' => $ev->getTitre(),
                            'start' => $ev->getDateDebut()?->format('Y-m-d\TH:i:s'),
                            'end' => $ev->getDateFin()?->format('Y-m-d\TH:i:s'),
                            'type' => 'event',
                            'color' => '#3b82f6',
                            'extendedProps' => [
                                'description' => $ev->getDescription(),
                                'lieu' => $ev->getLieu(),
                            ]
                        ];
                    }
                }
                
                // Get planned maintenances with their tasks
                $maintenances = $maintenanceRepo->findBy(['id_agriculteur' => $userId, 'statut' => 'Planifiée']);
                foreach ($maintenances as $m) {
                    foreach ($m->getTaches() as $tache) {
                        $datePrevue = $tache->getDatePrevue();
                        if ($datePrevue) {
                            $calendarEvents[] = [
                                'title' => $tache->getNomTache() . ' - ' . $m->getNomMaintenance(),
                                'start' => $datePrevue->format('Y-m-d'),
                                'type' => 'maintenance',
                                'color' => '#f97316',
                                'extendedProps' => [
                                    'description' => $tache->getDescription(),
                                    'equipement' => $m->getEquipement(),
                                    'lieu' => $m->getLieu(),
                                ]
                            ];
                        }
                    }
                }
            }
        }
        
        return $this->render('front/home/index.html.twig', [
            'isAgriculteur' => $isAgriculteur,
            'calendarEvents' => $calendarEvents,
        ]);
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

    #[Route('/evenements', name: 'app_evenements')]
    public function evenements(): Response
    {
        return $this->render('front/evenements/evenements.html.twig');
    }

     #[Route('/suivi-animal', name: 'app_suivi_animal')]
    public function suiviAnimal(Request $request, AnimalRepository $animalRepository, PaginatorInterface $paginator): Response
    {
        $q      = (string) $request->query->get('q', '');
        $sortBy = (string) $request->query->get('sortBy', 'codeAnimal');
        $order  = (string) $request->query->get('order', 'ASC');

        $query = $animalRepository->searchQuery($q, $sortBy, $order, null);

        $animals = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('front/suivi_animal/animal/index.html.twig', [
            'animals' => $animals,
            'q'       => $q,
            'sortBy'  => $sortBy,
            'order'   => $order,
        ]);
    }

    #[Route('/achat-equipement', name: 'app_achat_equipement')]
    public function achatEquipement(Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        return $this->redirectToRoute('app_equipement_catalogue');
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

}
