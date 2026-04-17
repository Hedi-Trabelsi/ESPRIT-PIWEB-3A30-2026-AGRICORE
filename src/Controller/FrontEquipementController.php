<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Equipement;
use App\Entity\LigneCommande;
use App\Repository\EquipementRepository;
use App\Service\CartService;
use App\Service\EquipmentAiService;
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

class FrontEquipementController extends AbstractController
{
    #[Route('/achat-equipement/catalogue', name: 'app_equipement_catalogue')]
    public function catalogue(
        EquipementRepository $equipementRepository,
        ExchangeRateService $exchangeRateService,
        Request $request
    ): Response {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $search = trim((string) $request->query->get('search', ''));
        $equipements = $equipementRepository->findActiveBySearch($search);
        $stats = $equipementRepository->getCatalogueStats();
        $featured = array_slice($equipements, 0, 4);

        return $this->render('front/achat_equipement/catalogue.html.twig', [
            'equipements' => $equipements,
            'search' => $search,
            'stats' => $stats,
            'featured' => $featured,
            'rates' => $exchangeRateService->getRatesFromTnd(),
        ]);
    }

    #[Route('/achat-equipement/detail/{id}', name: 'app_equipement_detail', methods: ['GET'])]
    public function detail(
        Equipement $equipement,
        EquipementRepository $equipementRepository,
        EquipmentAiService $equipmentAiService,
        EquipmentNewsService $equipmentNewsService,
        ExchangeRateService $exchangeRateService,
        Request $request
    ): Response {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        if (!$equipement->isActive()) {
            throw $this->createNotFoundException('Equipement introuvable.');
        }

        $priceData = $exchangeRateService->convertFromTnd((float) $equipement->getPrix());

        return $this->render('front/achat_equipement/detail.html.twig', [
            'equipement' => $equipement,
            'insights' => $equipmentAiService->generateInsights($equipement),
            'news' => $equipmentNewsService->getNewsForEquipment($equipement),
            'relatedEquipements' => $equipementRepository->findRelatedActive($equipement),
            'priceData' => $priceData,
        ]);
    }

    #[Route('/api/equipements/{id}/insights', name: 'app_api_equipement_insights', methods: ['GET'])]
    public function insightsApi(Equipement $equipement, EquipmentAiService $equipmentAiService): JsonResponse
    {
        return $this->json($equipmentAiService->generateInsights($equipement));
    }

    #[Route('/api/equipements/{id}/news', name: 'app_api_equipement_news', methods: ['GET'])]
    public function newsApi(Equipement $equipement, EquipmentNewsService $equipmentNewsService): JsonResponse
    {
        return $this->json($equipmentNewsService->getNewsForEquipment($equipement));
    }

    #[Route('/api/equipements/rates', name: 'app_api_equipement_rates', methods: ['GET'])]
    public function ratesApi(ExchangeRateService $exchangeRateService): JsonResponse
    {
        return $this->json($exchangeRateService->getRatesFromTnd());
    }

    #[Route('/ajouter-panier/{id}', name: 'app_add_to_cart', methods: ['POST'])]
    public function addToCart(Equipement $equipement, Request $request, CartService $cartService): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $qty = $request->request->getInt('quantity', 1);
        if ($qty > $equipement->getQuantite()) {
            $this->addFlash('warning', 'Stock insuffisant.');
            return $this->redirectToRoute('app_equipement_catalogue');
        }

        $cartService->add($equipement->getId(), $qty);
        $this->addFlash('success', 'Ajoute au panier.');

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/panier', name: 'app_cart_show')]
    public function showCart(
        CartService $cartService,
        EntityManagerInterface $em,
        ExchangeRateService $exchangeRateService,
        Request $request
    ): Response {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $cart = $cartService->getCart();
        $items = [];
        $total = 0.0;

        foreach ($cart as $id => $qty) {
            $eq = $em->getRepository(Equipement::class)->find($id);
            if ($eq && $eq->isActive() && $eq->getQuantite() > 0) {
                $prix = (float) $eq->getPrix();
                $sousTotal = $prix * $qty;
                $total += $sousTotal;
                $items[] = [
                    'equipement' => $eq,
                    'quantite' => $qty,
                    'prix_unitaire' => $prix,
                    'sous_total' => $sousTotal,
                ];
            } else {
                $cartService->remove((int) $id);
            }
        }

        return $this->render('front/achat_equipement/cart.html.twig', [
            'items' => $items,
            'total' => $total,
            'convertedTotal' => $exchangeRateService->convertFromTnd($total),
        ]);
    }

    #[Route('/panier/retirer/{id}', name: 'app_cart_remove')]
    public function removeFromCart(
        int $id,
        CartService $cartService,
        EntityManagerInterface $em,
        ExchangeRateService $exchangeRateService,
        Request $request
    ): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $cartService->remove($id);

        if ($request->isXmlHttpRequest()) {
            return $this->json($this->buildCartPayload($cartService, $em, $exchangeRateService));
        }

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/panier/modifier/{id}/{quantite}', name: 'app_cart_update')]
    public function updateCart(
        int $id,
        int $quantite,
        CartService $cartService,
        EntityManagerInterface $em,
        ExchangeRateService $exchangeRateService,
        Request $request
    ): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        /** @var Equipement|null $equipement */
        $equipement = $em->getRepository(Equipement::class)->find($id);

        if (!$equipement || !$equipement->isActive() || $equipement->getQuantite() <= 0) {
            $cartService->remove($id);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'error' => 'Equipement indisponible.',
                    ...$this->buildCartPayload($cartService, $em, $exchangeRateService),
                ], Response::HTTP_NOT_FOUND);
            }

            $this->addFlash('warning', 'Equipement indisponible.');

            return $this->redirectToRoute('app_cart_show');
        }

        $quantite = max(0, min($quantite, $equipement->getQuantite()));
        $cartService->updateQuantity($id, $quantite);

        if ($request->isXmlHttpRequest()) {
            return $this->json($this->buildCartPayload($cartService, $em, $exchangeRateService));
        }

        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/commande/valider', name: 'app_order_confirm')]
    public function confirmOrder(CartService $cartService, EntityManagerInterface $em, Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $cart = $cartService->getCart();
        if (empty($cart)) {
            $this->addFlash('warning', 'Panier vide.');
            return $this->redirectToRoute('app_equipement_catalogue');
        }

        foreach ($cart as $id => $qty) {
            $eq = $em->getRepository(Equipement::class)->find($id);
            if (!$eq || !$eq->isActive() || $eq->getQuantite() < $qty) {
                $name = $eq ? $eq->getNom() : 'cet equipement';
                $this->addFlash('error', 'Stock insuffisant pour ' . $name . '.');
                return $this->redirectToRoute('app_cart_show');
            }
        }

        $commande = new Commande();
        $commande->setAgriculteurId($sessionUser->getId());
        $totalCommande = 0.0;

        foreach ($cart as $id => $qty) {
            $eq = $em->getRepository(Equipement::class)->find($id);
            $prix = (float) $eq->getPrix();
            $totalLigne = $prix * $qty;
            $totalCommande += $totalLigne;

            $ligne = new LigneCommande();
            $ligne->setEquipement($eq);
            $ligne->setQuantite($qty);
            $ligne->setPrixUnitaire(number_format($prix, 2, '.', ''));
            $ligne->setTotalLigne(number_format($totalLigne, 2, '.', ''));
            $ligne->setCommande($commande);
            $em->persist($ligne);

            $eq->setQuantite($eq->getQuantite() - $qty);
        }

        $commande->setTotal(number_format($totalCommande, 2, '.', ''));
        $em->persist($commande);
        $em->flush();

        $cartService->clear();
        $this->addFlash('success', 'Commande confirmee.');

        return $this->render('front/achat_equipement/order_confirmation.html.twig', [
            'commande' => $commande,
            'pdfUrl' => $this->generateUrl('app_order_pdf', ['id' => $commande->getId()]),
        ]);
    }

    #[Route('/commande/{id}/pdf', name: 'app_order_pdf', methods: ['GET'])]
    public function orderPdf(
        Commande $commande,
        EquipmentPdfService $equipmentPdfService,
        Request $request
    ): Response {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        if ($commande->getAgriculteurId() !== $sessionUser->getId()) {
            throw $this->createAccessDeniedException('Commande non autorisee.');
        }

        $content = $equipmentPdfService->renderOrderPdf($commande);
        $response = new Response($content);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            'commande-' . $commande->getId() . '.pdf'
        );
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/mes-commandes', name: 'app_my_orders')]
    public function myOrders(EntityManagerInterface $em, Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $commandes = $em->getRepository(Commande::class)->findBy(
            ['agriculteurId' => $sessionUser->getId()],
            ['dateCommande' => 'DESC']
        );

        return $this->render('front/achat_equipement/my_orders.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    private function buildCartPayload(
        CartService $cartService,
        EntityManagerInterface $em,
        ExchangeRateService $exchangeRateService
    ): array {
        $items = [];
        $total = 0.0;

        foreach ($cartService->getCart() as $id => $qty) {
            /** @var Equipement|null $equipement */
            $equipement = $em->getRepository(Equipement::class)->find($id);
            if (!$equipement || !$equipement->isActive() || $equipement->getQuantite() <= 0) {
                $cartService->remove((int) $id);
                continue;
            }

            $quantite = min($qty, $equipement->getQuantite());
            if ($quantite !== $qty) {
                $cartService->updateQuantity((int) $id, $quantite);
            }

            $prixUnitaire = (float) $equipement->getPrix();
            $sousTotal = $prixUnitaire * $quantite;
            $total += $sousTotal;

            $items[] = [
                'id' => $equipement->getId(),
                'quantite' => $quantite,
                'stock' => $equipement->getQuantite(),
                'prix_unitaire' => $prixUnitaire,
                'sous_total' => $sousTotal,
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'convertedTotal' => $exchangeRateService->convertFromTnd($total),
        ];
    }
}
