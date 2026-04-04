<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function ventesDepenses(): Response
    {
        return $this->render('back/ventes_depenses/ventes_depenses.html.twig');
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