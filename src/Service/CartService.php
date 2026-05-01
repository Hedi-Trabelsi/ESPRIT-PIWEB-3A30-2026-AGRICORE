<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private const CART_KEY = 'panier_agriculteur';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }

    public function add(int $equipementId, int $quantity = 1): void
    {
        $cart = $this->getSession()->get(self::CART_KEY, []);
        $cart[$equipementId] = ($cart[$equipementId] ?? 0) + $quantity;
        $this->getSession()->set(self::CART_KEY, $cart);
    }

    public function remove(int $equipementId): void
    {
        $cart = $this->getSession()->get(self::CART_KEY, []);
        unset($cart[$equipementId]);
        $this->getSession()->set(self::CART_KEY, $cart);
    }

    public function updateQuantity(int $equipementId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($equipementId);
            return;
        }

        $cart = $this->getSession()->get(self::CART_KEY, []);
        $cart[$equipementId] = $quantity;
        $this->getSession()->set(self::CART_KEY, $cart);
    }

    /**
     * @return array<int, int>
     */
    public function getCart(): array
    {
        return $this->getSession()->get(self::CART_KEY, []);
    }

    public function clear(): void
    {
        $this->getSession()->remove(self::CART_KEY);
    }
}
