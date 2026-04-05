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

    #[Route('/back/ventes-depenses', name: 'back_ventes_depenses')]
    public function ventesDepenses(): Response
    {
        return $this->render('back/ventes_depenses/ventes_depenses.html.twig');
    }
}
