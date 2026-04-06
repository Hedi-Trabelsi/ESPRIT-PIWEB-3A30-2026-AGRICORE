<?php

namespace App\Controller;

use App\Repository\AnimalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;


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
