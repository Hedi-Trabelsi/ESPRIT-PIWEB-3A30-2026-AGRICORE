<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\MaintenanceProximityService;
use App\Repository\MaintenanceRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AnimalRepository;
use Knp\Component\Pager\PaginatorInterface;

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
    public function maintenanceATraiter(
        Request $request,
        MaintenanceRepository $maintenanceRepository,
        MaintenanceProximityService $proximityService,
        PaginatorInterface $paginator
    ): Response
    {
        $maintenances = $maintenanceRepository->findBy(['statut' => 'En attente']);

        $sessionUser = $request->getSession()->get('user');
        $technicianAddress = null;
        if (is_object($sessionUser) && method_exists($sessionUser, 'getAdresse')) {
            $technicianAddress = (string) $sessionUser->getAdresse();
        }

        $proximityResult = $proximityService->sortByRoadDistance($maintenances, $technicianAddress);
        $pagination = $paginator->paginate(
            $proximityResult['maintenances'],
            $request->query->getInt('page', 1),
            6
        );
        
        return $this->render('front/maintenance/interventions_a_traiter.html.twig', [
            'listeMaintenances' => $pagination,
            'maintenanceDistances' => $proximityResult['distances'],
            'proximityEnabled' => $proximityResult['enabled'],
        ]);
    }

#[Route('/maintenance/planifiee', name: 'app_maintenance_planifiee')]
public function maintenancePlanifiee(
    MaintenanceRepository $maintenanceRepository,
    MaintenanceProximityService $proximityService, // Injectez le service
    Request $request // Injectez la requête pour avoir l'utilisateur
): Response
{
    $maintenances = $maintenanceRepository->findBy(['statut' => 'Planifiée']);
    
    // Récupérer l'adresse du technicien (pour calculer la distance)
    $sessionUser = $request->getSession()->get('user');
    $technicianAddress = (is_object($sessionUser) && method_exists($sessionUser, 'getAdresse')) 
        ? (string)$sessionUser->getAdresse() 
        : null;

    // Utiliser le service pour obtenir les distances
    $proximityResult = $proximityService->sortByRoadDistance($maintenances, $technicianAddress);

    return $this->render('front/maintenance/interventions_planifiees.html.twig', [
        'listeMaintenances'    => $proximityResult['maintenances'], // Utilisez les maintenances triées
        'maintenanceDistances' => $proximityResult['distances'],    // <--- VOICI CE QUI MANQUAIT
        'proximityEnabled'     => $proximityResult['enabled'],      // <--- Variable de contrôle
    ]);
}

#[Route('/maintenance/historique', name: 'app_maintenance_historique')]
    public function Hmaintenance(MaintenanceRepository $maintenanceRepository): Response
    {
       
        $maintenancesResolues = $maintenanceRepository->findBy(['statut' => 'Résolue']);

        return $this->render('front/maintenance/HistoriqueMaintenances.html.twig', [
            'listeMaintenances' => $maintenancesResolues,
        ]);
    }
    #[Route('/evenements', name: 'app_evenements')]
    public function evenements(): Response
    {
        return $this->render('front/evenements/evenements.html.twig');
    }

  

     #[Route('/suivi-animal', name: 'app_suivi_animal')]
    public function suiviAnimal(Request $request, AnimalRepository $animalRepository): Response
    {
        $q = $request->query->get('q', '');
        $sortBy = $request->query->get('sortBy', 'codeAnimal');
        $order = $request->query->get('order', 'ASC');

        $animals = $animalRepository->findAll(); // simple pour maintenant

        return $this->render('front/suivi_animal/animal/index.html.twig', [
            'animals' => $animals,
            'q' => $q,
            'sortBy' => $sortBy,
            'order' => $order,
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
    
