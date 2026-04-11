<?php

namespace App\Controller;

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
    public function equipements(Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser || $sessionUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        return $this->redirectToRoute('back_equipement_index');
    }

}
