<?php

namespace App\Tests\Controller;

use App\Controller\EvenementController;
use App\Entity\Evennementagricole;
use App\Entity\Participants;
use PHPUnit\Framework\TestCase;

class EvenementControllerTest extends TestCase
{
    // ─── Helper ───────────────────────────────────────────────────────────────

    private function makeEvent(
        string $titre = 'Foire Agricole',
        int $capacite = 100,
        float $frais = 50.0,
        string $lieu = 'Tunis',
        string $description = 'Description test',
        string $statut = 'BROUILLON'
    ): Evennementagricole {
        $ev = new Evennementagricole();
        $ev->setTitre($titre);
        $ev->setCapaciteMax($capacite);
        $ev->setFraisInscription((int) $frais);
        $ev->setLieu($lieu);
        $ev->setDescription($description);
        $ev->setDateDebut(new \DateTime('+1 day'));
        $ev->setDateFin(new \DateTime('+3 days'));
        return $ev;
    }

    private function makeParticipant(
        Evennementagricole $ev,
        string $nom = 'Ahmed Ben Ali',
        int $places = 2,
        string $email = 'ahmed@test.tn'
    ): Participants {
        $p = new Participants();
        $p->setEvenement($ev);
        $p->setNomParticipant($nom);
        $p->setNbrPlaces($places);
        $p->setIdUtilisateur(1);
        $p->setDateInscription(new \DateTime());
        $p->setStatutParticipation('En attente');
        $p->setMontantPayee((string) ($places * $ev->getFraisInscription()));
        $p->setEntryCode(random_int(100000, 999999));
        $p->setConfirmation('pending');
        $p->setEmail($email);
        return $p;
    }

    // ─── CRUD Evennementagricole ───────────────────────────────────────────────

    /** CREATE */
    public function testCreateEvenement(): void
    {
        $ev = $this->makeEvent();

        $this->assertSame('Foire Agricole', $ev->getTitre());
        $this->assertSame(100, $ev->getCapaciteMax());
        $this->assertSame(50, $ev->getFraisInscription());
        $this->assertSame('Tunis', $ev->getLieu());
        $this->assertSame('Description test', $ev->getDescription());
        // Statut est calculé dynamiquement depuis les dates (dates futures → COMING)
        $this->assertSame('COMING', $ev->getStatut());
        $this->assertInstanceOf(\DateTimeInterface::class, $ev->getDateDebut());
        $this->assertInstanceOf(\DateTimeInterface::class, $ev->getDateFin());
    }

    /** READ */
    public function testReadEvenementFields(): void
    {
        $ev = $this->makeEvent('Salon du Cheval', 200, 0.0, 'Sfax', 'Salon annuel', 'COMING');

        $this->assertSame('Salon du Cheval', $ev->getTitre());
        $this->assertSame(200, $ev->getCapaciteMax());
        $this->assertSame(0, $ev->getFraisInscription());
        $this->assertSame('Sfax', $ev->getLieu());
        $this->assertSame('COMING', $ev->getStatut());
    }

    /** UPDATE */
    public function testUpdateEvenement(): void
    {
        $ev = $this->makeEvent();

        $ev->setTitre('Foire Modifiée');
        $ev->setCapaciteMax(150);
        $ev->setFraisInscription(75);
        $ev->setLieu('Sousse');
        // On met des dates passées pour forcer HISTORIQUE
        $ev->setDateDebut(new \DateTime('-5 days'));
        $ev->setDateFin(new \DateTime('-1 day'));

        $this->assertSame('Foire Modifiée', $ev->getTitre());
        $this->assertSame(150, $ev->getCapaciteMax());
        $this->assertSame(75, $ev->getFraisInscription());
        $this->assertSame('Sousse', $ev->getLieu());
        $this->assertSame('HISTORIQUE', $ev->getStatut());
    }

    /** DELETE — simulation */
    public function testDeleteEvenement(): void
    {
        $events = [
            $this->makeEvent('Événement A'),
            $this->makeEvent('Événement B'),
            $this->makeEvent('Événement C'),
        ];

        // Supprimer "Événement B"
        $events = array_values(array_filter(
            $events,
            fn(Evennementagricole $e) => $e->getTitre() !== 'Événement B'
        ));

        $this->assertCount(2, $events);
        $titres = array_map(fn($e) => $e->getTitre(), $events);
        $this->assertNotContains('Événement B', $titres);
        $this->assertContains('Événement A', $titres);
        $this->assertContains('Événement C', $titres);
    }

    // ─── Logique métier Evennementagricole ────────────────────────────────────

    public function testStatutCOMING(): void
    {
        $ev = $this->makeEvent();
        $ev->setDateDebut(new \DateTime('+2 days'));
        $ev->setDateFin(new \DateTime('+5 days'));

        $this->assertSame('COMING', $ev->getStatut());
    }

    public function testStatutEN_COURS(): void
    {
        $ev = $this->makeEvent();
        $ev->setDateDebut(new \DateTime('-1 day'));
        $ev->setDateFin(new \DateTime('+1 day'));

        $this->assertSame('EN_COURS', $ev->getStatut());
    }

    public function testStatutHISTORIQUE(): void
    {
        $ev = $this->makeEvent();
        $ev->setDateDebut(new \DateTime('-5 days'));
        $ev->setDateFin(new \DateTime('-1 day'));

        $this->assertSame('HISTORIQUE', $ev->getStatut());
    }

    public function testEvenementGratuit(): void
    {
        $ev = $this->makeEvent(frais: 0);
        $this->assertSame(0, $ev->getFraisInscription());
    }

    public function testCapaciteMaxPositive(): void
    {
        $ev = $this->makeEvent(capacite: 500);
        $this->assertGreaterThan(0, $ev->getCapaciteMax());
    }

    // ─── CRUD Participants ────────────────────────────────────────────────────

    /** CREATE */
    public function testCreateParticipant(): void
    {
        $ev = $this->makeEvent();
        $p  = $this->makeParticipant($ev);

        $this->assertSame('Ahmed Ben Ali', $p->getNomParticipant());
        $this->assertSame(2, $p->getNbrPlaces());
        $this->assertSame('ahmed@test.tn', $p->getEmail());
        $this->assertSame('En attente', $p->getStatutParticipation());
        $this->assertSame('pending', $p->getConfirmation());
        $this->assertSame($ev, $p->getEvenement());
        $this->assertSame('100', $p->getMontantPayee()); // 2 places × 50 DT
    }

    /** READ */
    public function testReadParticipantFields(): void
    {
        $ev = $this->makeEvent(frais: 30);
        $p  = $this->makeParticipant($ev, 'Sonia Trabelsi', 3, 'sonia@test.tn');

        $this->assertSame('Sonia Trabelsi', $p->getNomParticipant());
        $this->assertSame(3, $p->getNbrPlaces());
        $this->assertSame('sonia@test.tn', $p->getEmail());
        $this->assertSame('90', $p->getMontantPayee()); // 3 × 30
    }

    /** UPDATE */
    public function testUpdateParticipant(): void
    {
        $ev = $this->makeEvent();
        $p  = $this->makeParticipant($ev);

        $p->setNomParticipant('Karim Mansour');
        $p->setNbrPlaces(5);
        $p->setStatutParticipation('confirmed');
        $p->setConfirmation('confirmed');
        $p->setMontantPayee('250');

        $this->assertSame('Karim Mansour', $p->getNomParticipant());
        $this->assertSame(5, $p->getNbrPlaces());
        $this->assertSame('confirmed', $p->getStatutParticipation());
        $this->assertSame('confirmed', $p->getConfirmation());
        $this->assertSame('250', $p->getMontantPayee());
    }

    /** DELETE — simulation */
    public function testDeleteParticipant(): void
    {
        $ev = $this->makeEvent();
        $participants = [
            $this->makeParticipant($ev, 'Alice', 1, 'alice@test.tn'),
            $this->makeParticipant($ev, 'Bob',   2, 'bob@test.tn'),
            $this->makeParticipant($ev, 'Carol', 1, 'carol@test.tn'),
        ];

        // Supprimer Bob
        $participants = array_values(array_filter(
            $participants,
            fn(Participants $p) => $p->getNomParticipant() !== 'Bob'
        ));

        $this->assertCount(2, $participants);
        $noms = array_map(fn($p) => $p->getNomParticipant(), $participants);
        $this->assertNotContains('Bob', $noms);
        $this->assertContains('Alice', $noms);
        $this->assertContains('Carol', $noms);
    }

    // ─── Logique métier Participants ──────────────────────────────────────────

    public function testMontantCalcule(): void
    {
        $ev = $this->makeEvent(frais: 40);
        $p  = $this->makeParticipant($ev, 'Test', 3);

        // 3 places × 40 DT = 120 DT
        $this->assertSame('120', $p->getMontantPayee());
    }

    public function testMontantGratuit(): void
    {
        $ev = $this->makeEvent(frais: 0);
        $p  = $this->makeParticipant($ev, 'Test', 5);

        $this->assertSame('0', $p->getMontantPayee());
    }

    public function testWaitlistStatut(): void
    {
        $ev = $this->makeEvent();
        $p  = $this->makeParticipant($ev);
        $p->setStatutParticipation('waitlist');

        $this->assertSame('waitlist', $p->getStatutParticipation());
    }

    public function testConfirmationToken(): void
    {
        $ev = $this->makeEvent();
        $p  = $this->makeParticipant($ev);

        $token = bin2hex(random_bytes(16));
        $p->setConfirmToken($token);

        $this->assertSame($token, $p->getConfirmToken());
        $this->assertSame(32, strlen($token));
    }

    public function testEntryCodeRange(): void
    {
        $ev = $this->makeEvent();
        $p  = $this->makeParticipant($ev);

        $this->assertGreaterThanOrEqual(100000, $p->getEntryCode());
        $this->assertLessThanOrEqual(999999, $p->getEntryCode());
    }

    public function testPlacesRestantesApresInscription(): void
    {
        $ev = $this->makeEvent(capacite: 10);

        // Simuler 3 participants qui réservent 2 places chacun = 6 places prises
        $participants = [
            $this->makeParticipant($ev, 'P1', 2),
            $this->makeParticipant($ev, 'P2', 2),
            $this->makeParticipant($ev, 'P3', 2),
        ];

        $placesReservees = array_sum(array_map(fn($p) => $p->getNbrPlaces(), $participants));
        $placesRestantes = $ev->getCapaciteMax() - $placesReservees;

        $this->assertSame(6, $placesReservees);
        $this->assertSame(4, $placesRestantes);
    }

    public function testDiscountPercentage(): void
    {
        $controller = new EvenementController();
        $ref = new \ReflectionClass(EvenementController::class);
        $method = $ref->getMethod('getDiscountPercentage');
        $method->setAccessible(true);

        $this->assertSame(0,   $method->invoke($controller, 0));
        $this->assertSame(10,  $method->invoke($controller, 10));
        $this->assertSame(10,  $method->invoke($controller, 15));
        $this->assertSame(50,  $method->invoke($controller, 55));
        $this->assertSame(100, $method->invoke($controller, 100));
        $this->assertSame(100, $method->invoke($controller, 200)); // plafonné à 100
    }
}
