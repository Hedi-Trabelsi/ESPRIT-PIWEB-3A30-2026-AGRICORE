<?php

namespace App\Service;

use App\Entity\Equipement;
use App\Entity\Panier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $em,
    )
    {
    }

    private function getUserId(): ?int
    {
        $sessionUser = $this->requestStack->getSession()->get('user');
        if (is_object($sessionUser) && method_exists($sessionUser, 'getId')) {
            $id = $sessionUser->getId();
            return is_numeric($id) ? (int) $id : null;
        }

        return null;
    }

    public function add(int $equipementId, int $quantity = 1): void
    {
        $userId = $this->getUserId();
        if ($userId === null || $quantity <= 0) {
            return;
        }

        /** @var Equipement|null $equipement */
        $equipement = $this->em->getRepository(Equipement::class)->find($equipementId);
        if (!$equipement || !$equipement->isActive() || $equipement->getQuantite() <= 0) {
            return;
        }

        /** @var Panier|null $item */
        $item = $this->em->getRepository(Panier::class)->findOneBy([
            'id_agriculteur' => $userId,
            'equipement' => $equipement,
        ]);

        if (!$item) {
            $item = new Panier();
            $item->setIdAgriculteur($userId);
            $item->setEquipement($equipement);
            $item->setQuantite(0);
            $this->em->persist($item);
        }

        $newQuantity = min($item->getQuantite() + $quantity, $equipement->getQuantite());
        $this->applyLineTotal($item, $newQuantity);
        $this->em->flush();
    }

    public function remove(int $equipementId): void
    {
        $userId = $this->getUserId();
        if ($userId === null) {
            return;
        }

        /** @var Equipement|null $equipement */
        $equipement = $this->em->getRepository(Equipement::class)->find($equipementId);
        if (!$equipement) {
            return;
        }

        /** @var Panier|null $item */
        $item = $this->em->getRepository(Panier::class)->findOneBy([
            'id_agriculteur' => $userId,
            'equipement' => $equipement,
        ]);

        if ($item) {
            $this->em->remove($item);
            $this->em->flush();
        }
    }

    public function updateQuantity(int $equipementId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($equipementId);
            return;
        }

        $userId = $this->getUserId();
        if ($userId === null) {
            return;
        }

        /** @var Equipement|null $equipement */
        $equipement = $this->em->getRepository(Equipement::class)->find($equipementId);
        if (!$equipement || !$equipement->isActive() || $equipement->getQuantite() <= 0) {
            $this->remove($equipementId);
            return;
        }

        /** @var Panier|null $item */
        $item = $this->em->getRepository(Panier::class)->findOneBy([
            'id_agriculteur' => $userId,
            'equipement' => $equipement,
        ]);

        if (!$item) {
            $item = new Panier();
            $item->setIdAgriculteur($userId);
            $item->setEquipement($equipement);
            $this->em->persist($item);
        }

        $this->applyLineTotal($item, min($quantity, $equipement->getQuantite()));
        $this->em->flush();
    }

    /**
     * @return array<int, int>
     */
    public function getCart(): array
    {
        $userId = $this->getUserId();
        if ($userId === null) {
            return [];
        }

        $rows = $this->em->getRepository(Panier::class)->findBy(['id_agriculteur' => $userId]);
        $cart = [];
        foreach ($rows as $row) {
            if (!$row instanceof Panier || !$row->getEquipement() || $row->getQuantite() <= 0) {
                continue;
            }
            $cart[(int) $row->getEquipement()->getId()] = (int) $row->getQuantite();
        }

        return $cart;
    }

    public function clear(): void
    {
        $userId = $this->getUserId();
        if ($userId === null) {
            return;
        }

        foreach ($this->em->getRepository(Panier::class)->findBy(['id_agriculteur' => $userId]) as $item) {
            $this->em->remove($item);
        }
        $this->em->flush();
    }

    private function applyLineTotal(Panier $item, int $quantity): void
    {
        $item->setQuantite($quantity);
        $price = (float) str_replace(',', '.', (string) $item->getEquipement()?->getPrix());
        $item->setTotal(number_format($price * $quantity, 2, '.', ''));
    }
}
