<?php

namespace App\Tests\Service;

use App\Service\CartService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class CartServiceTest extends TestCase
{
    private CartService $cartService;

    protected function setUp(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $this->cartService = new CartService($requestStack);
    }

    public function testAddCreatesCartLine(): void
    {
        $this->cartService->add(12, 3);

        $this->assertSame([12 => 3], $this->cartService->getCart());
    }

    public function testAddAccumulatesQuantityForSameEquipment(): void
    {
        $this->cartService->add(12, 2);
        $this->cartService->add(12, 4);

        $this->assertSame([12 => 6], $this->cartService->getCart());
    }

    public function testRemoveDeletesEquipmentFromCart(): void
    {
        $this->cartService->add(12, 2);
        $this->cartService->add(15, 1);

        $this->cartService->remove(12);

        $this->assertSame([15 => 1], $this->cartService->getCart());
    }

    public function testUpdateQuantityReplacesExistingQuantity(): void
    {
        $this->cartService->add(12, 2);

        $this->cartService->updateQuantity(12, 7);

        $this->assertSame([12 => 7], $this->cartService->getCart());
    }

    public function testUpdateQuantityWithZeroRemovesEquipment(): void
    {
        $this->cartService->add(12, 2);

        $this->cartService->updateQuantity(12, 0);

        $this->assertSame([], $this->cartService->getCart());
    }

    public function testClearEmptiesTheWholeCart(): void
    {
        $this->cartService->add(12, 2);
        $this->cartService->add(15, 1);

        $this->cartService->clear();

        $this->assertSame([], $this->cartService->getCart());
    }
}
