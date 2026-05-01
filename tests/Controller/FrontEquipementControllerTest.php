<?php

namespace App\Tests\Controller;

use App\Controller\FrontEquipementController;
use App\Entity\Commande;
use App\Entity\Equipement;
use App\Entity\User;
use App\Service\CartService;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FrontEquipementControllerTest extends TestCase
{
    public function testAddToCartAddsEquipmentWhenStockIsSufficient(): void
    {
        $request = $this->createRequestWithUser();
        $request->request->set('quantity', 2);
        $cartService = $this->createCartServiceForRequest($request);
        $controller = $this->createController($request);
        $equipement = $this->createEquipement(12, 'Tracteur', '4500', 5);

        $response = $controller->addToCart($equipement, $request, $cartService);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('app_cart_show', $controller->lastRedirectRoute);
        $this->assertSame([12 => 2], $cartService->getCart());
        $this->assertSame(['Ajoute au panier.'], $this->sessionOf($request)->getFlashBag()->peek('success'));
    }

    public function testAddToCartRejectsRequestWhenStockIsInsufficient(): void
    {
        $request = $this->createRequestWithUser();
        $request->request->set('quantity', 6);
        $cartService = $this->createCartServiceForRequest($request);
        $controller = $this->createController($request);
        $equipement = $this->createEquipement(12, 'Tracteur', '4500', 5);

        $response = $controller->addToCart($equipement, $request, $cartService);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('app_equipement_catalogue', $controller->lastRedirectRoute);
        $this->assertSame([], $cartService->getCart());
        $this->assertSame(['Stock insuffisant.'], $this->sessionOf($request)->getFlashBag()->peek('warning'));
    }

    public function testConfirmOrderRedirectsWhenCartIsEmpty(): void
    {
        $request = $this->createRequestWithUser();
        $cartService = $this->createCartServiceForRequest($request);
        $controller = $this->createController($request);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $response = $controller->confirmOrder($cartService, $em, $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('app_equipement_catalogue', $controller->lastRedirectRoute);
        $this->assertSame(['Panier vide.'], $this->sessionOf($request)->getFlashBag()->peek('warning'));
    }

    public function testConfirmOrderRejectsWhenEquipmentStockBecomesInsufficient(): void
    {
        $request = $this->createRequestWithUser();
        $cartService = $this->createCartServiceForRequest($request);
        $cartService->add(5, 3);
        $controller = $this->createController($request);
        $equipement = $this->createEquipement(5, 'Pulverisateur', '1200', 2);
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($equipement);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $response = $controller->confirmOrder($cartService, $em, $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('app_cart_show', $controller->lastRedirectRoute);
        $this->assertSame(['Stock insuffisant pour Pulverisateur.'], $this->sessionOf($request)->getFlashBag()->peek('error'));
        $this->assertSame([5 => 3], $cartService->getCart());
    }

    public function testConfirmOrderCreatesOrderLinesUpdatesStockAndClearsCart(): void
    {
        $request = $this->createRequestWithUser(33);
        $cartService = $this->createCartServiceForRequest($request);
        $cartService->add(10, 2);
        $cartService->add(11, 1);
        $controller = $this->createController($request);

        $tractor = $this->createEquipement(10, 'Tracteur', '1500', 5);
        $pump = $this->createEquipement(11, 'Pompe', '250.50', 4);
        $equipements = [
            10 => $tractor,
            11 => $pump,
        ];

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->method('find')
            ->willReturnCallback(static fn (int $id): ?Equipement => $equipements[$id] ?? null);

        $persistedCommande = null;
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function ($entity) use (&$persistedCommande): bool {
                if (!$entity instanceof Commande) {
                    return false;
                }

                $persistedCommande = $entity;

                return true;
            }));
        $em->expects($this->once())->method('flush');

        $response = $controller->confirmOrder($cartService, $em, $request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('front/achat_equipement/order_confirmation.html.twig', $controller->lastRenderedView);
        $this->assertNotNull($persistedCommande);
        $this->assertSame(33, $persistedCommande->getAgriculteurId());
        $this->assertSame('3250.50', $persistedCommande->getTotal());
        $this->assertCount(2, $persistedCommande->getLignes());
        $this->assertSame(3, $tractor->getQuantite());
        $this->assertSame(3, $pump->getQuantite());
        $this->assertSame([], $cartService->getCart());
        $this->assertSame(['Commande confirmee.'], $this->sessionOf($request)->getFlashBag()->peek('success'));
        $this->assertSame('/mocked/app_order_pdf/0', $controller->lastRenderedParameters['pdfUrl']);
    }

    private function createController(Request $request): TestFrontEquipementController
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = new ContainerBuilder();
        $container->set('request_stack', $requestStack);

        $controller = new TestFrontEquipementController();
        $controller->setContainer($container);

        return $controller;
    }

    private function createCartServiceForRequest(Request $request): CartService
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new CartService($requestStack);
    }

    private function createRequestWithUser(int $userId = 15): Request
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $user = new User();
        $user->setId($userId);
        $session->set('user', $user);

        return $request;
    }

    private function sessionOf(Request $request): Session
    {
        $session = $request->getSession();
        if (!$session instanceof Session) {
            throw new \RuntimeException('Test request did not have a Session instance.');
        }
        return $session;
    }

    private function createEquipement(int $id, string $nom, string $prix, int $quantite): Equipement
    {
        return (new Equipement())
            ->setId_equipement($id)
            ->setNom($nom)
            ->setType($nom)
            ->setPrix($prix)
            ->setQuantite($quantite)
            ->setIsActive(true);
    }
}

class TestFrontEquipementController extends FrontEquipementController
{
    public ?string $lastRedirectRoute = null;
    public ?string $lastRenderedView = null;
    /** @var array<string, mixed> */
    public array $lastRenderedParameters = [];

    /**
     * @param array<string, mixed> $parameters
     */
    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        $this->lastRedirectRoute = $route;

        return new RedirectResponse('/mocked/' . $route, $status);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $this->lastRenderedView = $view;
        $this->lastRenderedParameters = $parameters;

        return $response ?? new Response($view);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string
    {
        return '/mocked/' . $route . '/' . ($parameters['id'] ?? 0);
    }
}
