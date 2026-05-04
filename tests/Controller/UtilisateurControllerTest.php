<?php

namespace App\Tests\Controller;

use App\Controller\UtilisateurController;
use PHPUnit\Framework\TestCase;

class UtilisateurControllerTest extends TestCase
{
    private function invokePrivate(UtilisateurController $controller, string $method, array $args): mixed
    {
        $ref = new \ReflectionClass(UtilisateurController::class);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($controller, $args);
    }

    // ----- safeImageBase64 -----

    public function testSafeImageBase64ReturnsNullForNull(): void
    {
        $controller = new UtilisateurController();
        $this->assertNull($this->invokePrivate($controller, 'safeImageBase64', [null]));
    }

    public function testSafeImageBase64ReturnsNullForEmptyString(): void
    {
        $controller = new UtilisateurController();
        $this->assertNull($this->invokePrivate($controller, 'safeImageBase64', ['']));
    }

    public function testSafeImageBase64PassesThroughAsciiBase64(): void
    {
        $controller = new UtilisateurController();
        $alreadyBase64 = base64_encode('hello world');
        $this->assertEquals($alreadyBase64, $this->invokePrivate($controller, 'safeImageBase64', [$alreadyBase64]));
    }

    public function testSafeImageBase64EncodesRawBinary(): void
    {
        $controller = new UtilisateurController();
        $rawBinary = "\x89PNG\r\n\x1a\n";
        $expected = base64_encode($rawBinary);
        $this->assertEquals($expected, $this->invokePrivate($controller, 'safeImageBase64', [$rawBinary]));
    }

    // ----- normalizeAddress -----

    public function testNormalizeAddressFixesManzelBouzelfa(): void
    {
        $controller = new UtilisateurController();
        $this->assertEquals('Menzel Bouzelfa', $this->invokePrivate($controller, 'normalizeAddress', ['manzel bou zelfa']));
        $this->assertEquals('Menzel Bouzelfa', $this->invokePrivate($controller, 'normalizeAddress', ['MANZEL BOUZELFA']));
        $this->assertEquals('Menzel Bouzelfa', $this->invokePrivate($controller, 'normalizeAddress', ['menzel bou zelfa']));
    }

    public function testNormalizeAddressFixesTunis(): void
    {
        $controller = new UtilisateurController();
        $this->assertEquals('Ville de Tunis', $this->invokePrivate($controller, 'normalizeAddress', ['tunis']));
        $this->assertEquals('Ville de Tunis', $this->invokePrivate($controller, 'normalizeAddress', ['Tunis']));
    }

    public function testNormalizeAddressFallbackTitleCases(): void
    {
        $controller = new UtilisateurController();
        $this->assertEquals('Ariana', $this->invokePrivate($controller, 'normalizeAddress', ['ariana']));
        $this->assertEquals('Sfax Centre', $this->invokePrivate($controller, 'normalizeAddress', ['sfax centre']));
    }
}
