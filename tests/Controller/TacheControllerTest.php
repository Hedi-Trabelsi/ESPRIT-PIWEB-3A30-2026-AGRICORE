<?php

namespace App\Tests\Controller;

use App\Controller\TacheController;
use App\Entity\Maintenance;
use App\Entity\User;
use App\Repository\TacheRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Doctrine\ORM\EntityManagerInterface;

class TacheControllerTest extends TestCase
{
    public function testDayLoadUnauthorizedWhenNoSession(): void
    {
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $em = $this->createMock(EntityManagerInterface::class);
        $tacheRepo = $this->createMock(TacheRepository::class);

        $controller = new TacheController();
        $response = $controller->dayLoad($request, $em, $tacheRepo);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testDayLoadReturnsCount(): void
    {
        $request = new Request(['date' => (new \DateTimeImmutable('today'))->format('Y-m-d')]);
        $session = new Session(new MockArraySessionStorage());
        $session->set('user', ['id' => 42, 'role' => 2]);
        $request->setSession($session);

        $user = new \App\Entity\User();
        $user->setId(42);

        // simple repository stub returning our user
        $userRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $userRepo->method('find')->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($userRepo);

        $tacheRepo = $this->createMock(TacheRepository::class);
        $tacheRepo->method('countTasksForTechnicianOnDate')->willReturn(3);

        $controller = new TacheController();
        $response = $controller->dayLoad($request, $em, $tacheRepo);

        $this->assertSame(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertSame(3, $data['count']);
    }

    public function testGenerateDescriptionUnauthorizedWhenNoSessionUser(): void
    {
        $body = json_encode(['id_maintenance' => 0]);
        $this->assertNotFalse($body);
        $request = new Request([], [], [], [], [], [], $body);
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $maintenanceRepo = $this->createMock(\App\Repository\MaintenanceRepository::class);
        $aiService = $this->createMock(\App\Service\TaskDescriptionAiService::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $controller = new TacheController();
        $response = $controller->generateDescription($request, $maintenanceRepo, $aiService, $em);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testGenerateDescriptionSuccess(): void
    {
        $payload = ['id_maintenance' => 1, 'nomTache' => 'Test'];
        $body2 = json_encode($payload);
        $this->assertNotFalse($body2);
        $request = new Request([], [], [], [], [], [], $body2);
        $session = new Session(new MockArraySessionStorage());
        $sessionUser = new \App\Entity\User();
        $sessionUser->setId(7);
        $session->set('user', $sessionUser);
        $request->setSession($session);

        $maintenance = new Maintenance();
        $maintenance->setNomMaintenance('M1')->setDescription('d');

        $maintenanceRepo = $this->createMock(\App\Repository\MaintenanceRepository::class);
        $maintenanceRepo->method('find')->willReturn($maintenance);

        $aiService = $this->createMock(\App\Service\TaskDescriptionAiService::class);
        $aiService->method('generateForMaintenance')->willReturn('generated description');

        $user = new \App\Entity\User();
        $user->setId(7);
        $userRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $userRepo->method('find')->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($userRepo);

        $controller = new TacheController();
        $response = $controller->generateDescription($request, $maintenanceRepo, $aiService, $em);

        $content2 = $response->getContent();
        $this->assertIsString($content2);
        $this->assertSame(200, $response->getStatusCode(), 'Response content: ' . $content2);
        $data = json_decode($content2, true);
        $this->assertIsArray($data);
        $this->assertSame('generated description', $data['description']);
    }
}
