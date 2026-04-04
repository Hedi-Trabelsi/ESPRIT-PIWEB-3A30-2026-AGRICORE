<?php

namespace App\Controller;
use App\Entity\Maintenance;        // Pour créer l'objet $maintenance
use App\Form\MaintenanceType;      // Pour créer le formulaire
use App\Repository\MaintenanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MaintenanceController extends AbstractController
{
    /**
     * Cette route va remplacer celle qui était dans HomeController
     * On garde le même nom 'app_maintenance' pour ne pas casser tes liens existants
     */
    #[Route('/maintenance', name: 'app_maintenance')]
    public function index(MaintenanceRepository $maintenanceRepository): Response
    {
        // On récupère toutes les données de la table maintenance
        $maintenances = $maintenanceRepository->findAll();

        // On pointe vers ton fichier existant : front/maintenance/maintenance.html.twig
        return $this->render('front/maintenance/maintenance.html.twig', [
            'listeMaintenances' => $maintenances,
        ]);
    }
 #[Route('/maintenance/ajouter', name: 'app_maintenance_add')]
public function add(Request $request, EntityManagerInterface $em): Response
{
    $maintenance = new Maintenance();
    
    // DATE AUTOMATIQUE : On fixe la date d'aujourd'hui ici
    $maintenance->setDateDeclaration(new \DateTime());
    $maintenance->setStatut('En attente'); 

    $form = $this->createForm(MaintenanceType::class, $maintenance);
    $form->handleRequest($request);

    // VALIDATION CÔTÉ SERVEUR (Workshop Page 2)
    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($maintenance);
        $em->flush();

        return $this->redirectToRoute('app_maintenance');
    }

    return $this->render('front/maintenance/new.html.twig', [
        'form' => $form->createView(),
    ]);
}
#[Route('/maintenance/modifier/{id_maintenance}', name: 'app_maintenance_edit')]
public function edit(Maintenance $maintenance, Request $request, EntityManagerInterface $em): Response
{
    // On réutilise le même formulaire MaintenanceType
    $form = $this->createForm(MaintenanceType::class, $maintenance);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Pas besoin de persist() pour une modification, juste flush()
        $em->flush();

        return $this->redirectToRoute('app_maintenance');
    }

    return $this->render('front/maintenance/edit.html.twig', [
        'form' => $form->createView(),
        'maintenance' => $maintenance
    ]);
}

#[Route('/maintenance/supprimer/{id_maintenance}', name: 'app_maintenance_delete', methods: ['POST'])]
public function delete(Request $request, Maintenance $maintenance, EntityManagerInterface $em): Response
{
    if ($this->isCsrfTokenValid('delete'.$maintenance->getId_maintenance(), $request->request->get('_token'))) {
        $em->remove($maintenance);
        $em->flush();

        $this->addFlash('success', 'Maintenance supprimée avec succès.');
    }

    return $this->redirectToRoute('app_maintenance');
}
}