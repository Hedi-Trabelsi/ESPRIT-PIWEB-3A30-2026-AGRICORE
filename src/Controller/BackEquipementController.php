<?php

namespace App\Controller;

use App\Entity\Equipement;
use App\Entity\User;
use App\Entity\Utilisateurs;
use App\Form\EquipementType;
use App\Repository\EquipementRepository;
use App\Service\EquipmentNewsService;
use App\Service\EquipmentPdfService;
use App\Service\ExchangeRateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/back/achat_equipement')]
class BackEquipementController extends AbstractController
{
    #[Route('/', name: 'back_equipement_index')]
    public function index(
        Request $request,
        EquipementRepository $equipementRepository,
        ExchangeRateService $exchangeRateService
    ): Response {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser || $sessionUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $equipements = $equipementRepository->findBy(['isActive' => true], ['id_equipement' => 'DESC']);
        $stats = $equipementRepository->getCatalogueStats();

        return $this->render('back/achat_equipement/index.html.twig', [
            'equipements' => $equipements,
            'stats' => $stats,
            'rates' => $exchangeRateService->getRatesFromTnd(),
        ]);
    }

    #[Route('/stats.json', name: 'back_equipement_stats_api', methods: ['GET'])]
    public function statsApi(Request $request, EquipementRepository $equipementRepository): JsonResponse
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser || $sessionUser->getRole() !== 0) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($equipementRepository->getCatalogueStats());
    }

    #[Route('/export/pdf', name: 'back_equipement_export_pdf', methods: ['GET'])]
    public function exportPdf(
        Request $request,
        EquipementRepository $equipementRepository,
        EquipmentPdfService $equipmentPdfService
    ): Response {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser || $sessionUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $equipements = $equipementRepository->findBy(['isActive' => true], ['id_equipement' => 'DESC']);
        $content = $equipmentPdfService->renderCataloguePdf($equipements, $equipementRepository->getCatalogueStats());

        $response = new Response($content);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'catalogue-equipements-agricore.pdf'
        );
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/news-preview/{id}', name: 'back_equipement_news_preview', methods: ['GET'])]
    public function newsPreview(
        Equipement $equipement,
        Request $request,
        EquipmentNewsService $equipmentNewsService
    ): JsonResponse {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser || $sessionUser->getRole() !== 0) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($equipmentNewsService->getNewsForEquipment($equipement));
    }

    #[Route('/new', name: 'back_equipement_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser || $sessionUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $this->ensureLegacySupplierExists($sessionUser, $em);

        $equipement = new Equipement();
        $equipement->setUser($em->getReference(User::class, $sessionUser->getId()));
        $form = $this->createForm(EquipementType::class, $equipement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    private function ensureLegacySupplierExists(User $sessionUser, EntityManagerInterface $em): void
    {
        $legacySupplier = $em->getRepository(Utilisateurs::class)->find($sessionUser->getId());
        if ($legacySupplier instanceof Utilisateurs) {
            return;
        }

        $legacySupplier = new Utilisateurs();
        $legacySupplier->setId($sessionUser->getId());
        $legacySupplier->setNom($sessionUser->getNom() ?? '');
        $legacySupplier->setPrenom($sessionUser->getPrenom() ?? '');
        $legacySupplier->setAdresse($sessionUser->getAdresse() ?? '');
        $legacySupplier->setEmail($sessionUser->getEmail() ?? '');
        $legacySupplier->setNumero_tel($sessionUser->getNumeroT() ?? 0);
        $legacySupplier->setRole((string) ($sessionUser->getRole() ?? ''));
        $legacySupplier->setImage($sessionUser->getImage() ?? '');
        $legacySupplier->setAge($this->resolveAgeFromSessionUser($sessionUser));

        $em->persist($legacySupplier);
        $em->flush();
    }

    private function resolveAgeFromSessionUser(User $sessionUser): int
    {
        $birthDate = $sessionUser->getDate();
        if (!$birthDate instanceof \DateTimeInterface) {
            return 0;
        }

        return max(0, (int) $birthDate->diff(new \DateTimeImmutable('today'))->y);
    }
}
