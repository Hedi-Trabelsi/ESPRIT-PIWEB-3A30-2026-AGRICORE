<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Equipement;
use App\Entity\LigneCommande;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontEquipementController extends AbstractController
{
    #[Route('/achat-equipement/catalogue', name: 'app_equipement_catalogue')]
    public function catalogue(EntityManagerInterface $em, Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $search = trim((string) $request->query->get('search', ''));
        $qb = $em->getRepository(Equipement::class)->createQueryBuilder('e')
            ->andWhere('e.isActive = true')
            ->orderBy('e.id_equipement', 'DESC');

        if ($search !== '') {
            $qb->andWhere('e.nom LIKE :search OR e.type LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $this->render('front/achat_equipement/catalogue.html.twig', [
            'equipements' => $qb->getQuery()->getResult(),
            'search' => $search,
        ]);
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
    public function showCart(CartService $cartService, EntityManagerInterface $em, Request $request): Response
    {
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
        ]);
    }

    #[Route('/panier/retirer/{id}', name: 'app_cart_remove')]
    public function removeFromCart(int $id, CartService $cartService, Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $cartService->remove($id);
        return $this->redirectToRoute('app_cart_show');
    }

    #[Route('/panier/modifier/{id}/{quantite}', name: 'app_cart_update')]
    public function updateCart(int $id, int $quantite, CartService $cartService, Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $cartService->updateQuantity($id, $quantite);
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
        ]);
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
}
