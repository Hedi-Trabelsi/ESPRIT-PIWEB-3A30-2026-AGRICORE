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