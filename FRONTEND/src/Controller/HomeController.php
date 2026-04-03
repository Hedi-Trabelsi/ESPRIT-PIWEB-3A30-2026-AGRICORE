<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }

    #[Route('/maintenance', name: 'app_maintenance')]
    public function maintenance(): Response
    {
        return $this->render('maintenance/maintenance.html.twig');
    }

    #[Route('/evenements', name: 'app_evenements')]
    public function evenements(): Response
    {
        return $this->render('evenements/evenements.html.twig');
    }

    #[Route('/suivi-animal', name: 'app_suivi_animal')]
    public function suiviAnimal(): Response
    {
        return $this->render('suivi_animal/suivi_animal.html.twig');
    }

    #[Route('/achat-equipement', name: 'app_achat_equipement')]
    public function achatEquipement(): Response
    {
        return $this->render('achat_equipement/achat_equipement.html.twig');
    }

    #[Route('/ventes-depenses', name: 'app_ventes_depenses')]
    public function ventesDepenses(): Response
    {
        return $this->render('ventes_depenses/ventes_depenses.html.twig');
    }

    #[Route('/profil', name: 'app_profile')]
    public function profile(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        return $this->render('utilisateurs/profil.html.twig');
    }
    
    #[Route('/services', name: 'app_services')]
    public function services(): Response
    {
        return $this->render('home/services.html.twig');
    }
    
    #[Route('/tech', name: 'app_tech_home')]
    public function techHome(): Response
    {
        return $this->render('home/tech_home.html.twig');
    }
    
    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        // Vérifier si l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Rediriger vers le tableau de bord adapté selon le rôle
        $user = $this->getUser();
        
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->render('dashboard/admin_dashboard.html.twig');
        }
        
        return $this->render('dashboard/user_dashboard.html.twig');
    }
}