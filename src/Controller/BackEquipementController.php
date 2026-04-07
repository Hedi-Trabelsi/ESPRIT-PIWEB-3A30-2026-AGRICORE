<?php

namespace App\Controller;

use App\Entity\Equipement;
use App\Entity\User;
use App\Form\EquipementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/back/achat_equipement')]
class BackEquipementController extends AbstractController
{
    #[Route('/', name: 'back_equipement_index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser || $sessionUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        return $this->render('back/achat_equipement/index.html.twig', [
            'equipements' => $em->getRepository(Equipement::class)->findBy(['isActive' => true]),
        ]);
    }

    #[Route('/new', name: 'back_equipement_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser || $sessionUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $equipement = new Equipement();
        $equipement->setUser($em->getReference(User::class, $sessionUser->getId()));
        $form = $this->createForm(EquipementType::class, $equipement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $newFilename = uniqid('', true) . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('equipement_images_directory'), $newFilename);
                $equipement->setImageFilename($newFilename);
            }

            if (!$equipement->getUser()) {
                $equipement->setUser($em->getReference(User::class, $sessionUser->getId()));
            }

            $em->persist($equipement);
            $em->flush();
            $this->addFlash('admin_success', 'Equipement cree.');

            return $this->redirectToRoute('back_equipement_index');
        }

        return $this->render('back/achat_equipement/form.html.twig', [
            'form' => $form->createView(),
            'equipement' => $equipement,
        ]);
    }

    #[Route('/edit/{id}', name: 'back_equipement_edit')]
    public function edit(Equipement $equipement, Request $request, EntityManagerInterface $em): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser || $sessionUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $form = $this->createForm(EquipementType::class, $equipement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $old = $equipement->getImageFilename();
                if ($old) {
                    $oldPath = $this->getParameter('equipement_images_directory') . '/' . $old;
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $newFilename = uniqid('', true) . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('equipement_images_directory'), $newFilename);
                $equipement->setImageFilename($newFilename);
            }

            $em->flush();
            $this->addFlash('admin_success', 'Equipement modifie.');

            return $this->redirectToRoute('back_equipement_index');
        }

        return $this->render('back/achat_equipement/form.html.twig', [
            'form' => $form->createView(),
            'equipement' => $equipement,
        ]);
    }

    #[Route('/delete/{id}', name: 'back_equipement_delete', methods: ['POST'])]
    public function delete(Equipement $equipement, Request $request, EntityManagerInterface $em): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser || $sessionUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        if ($this->isCsrfTokenValid('delete' . $equipement->getId(), $request->request->get('_token'))) {
            $equipement->setIsActive(false);
            $em->flush();
            $this->addFlash('admin_success', 'Equipement supprime.');
        }

        return $this->redirectToRoute('back_equipement_index');
    }
}
