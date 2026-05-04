<?php

namespace App\Tests\Controller;

use App\Entity\Evennementagricole;
use App\Entity\Participants;
use PHPUnit\Framework\TestCase;

class EvenementBackControllerTest extends TestCase
{
    // ─── Helper ───────────────────────────────────────────────────────────────

    private function makeEvent(
        string $titre = 'Événement Back',
        int $capacite = 50,
        int $frais = 30,
        string $lieu = 'Ariana',
        string $description = 'Description back'
    ): Evennementagricole {
        $ev = new Evennementagricole();
        $ev->setTitre($titre);
        $ev->setCapaciteMax($capacite);
        $ev->setFraisInscription($frais);
        $ev->setLieu($lieu);
        $ev->setDescription($description);
        $ev->setDateDebut(new \DateTime('+1 day'));
        $ev->setDateFin(new \DateTime('+3 days'));
        return $ev;
    }

    private function makeParticipant(
        Evennementagricole $ev,
        string $nom = 'Participant Test',
        int $places = 1
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
        $p->setEmail('test@agricore.tn');
        return $p;
    }

    // ─── CRUD Evennementagricole (Back) ───────────────────────────────────────

    /** CREATE */
    public function testCreateEvenementBack(): void
    {
        $ev = $this->makeEvent();

        $this->assertSame('Événement Back', $ev->getTitre());
        $this->assertSame(50, $ev->getCapaciteMax());
        $this->assertSame(30, $ev->getFraisInscription());
        $this->assertSame('Ariana', $ev->getLieu());
        // Statut calculé dynamiquement depuis les dates (dates futures → COMING)
        $this->assertSame('COMING', $ev->getStatut());
    }

    /** READ */
    public function testReadEvenementBack(): void
    {
        $ev = $this->makeEvent('Conférence Agricole', 200, 0, 'Sfax', 'Conférence annuelle');

        $this->assertSame('Conférence Agricole', $ev->getTitre());
        $this->assertSame(200, $ev->getCapaciteMax());
        $this->assertSame(0, $ev->getFraisInscription());
        $this->assertSame('Sfax', $ev->getLieu());
        $this->assertSame('Conférence annuelle', $ev->getDescription());
    }

    /** UPDATE */
    public function testUpdateEvenementBack(): void
    {
        $ev = $this->makeEvent();

        $ev->setTitre('Titre Modifié');
        $ev->setCapaciteMax(100);
        $ev->setFraisInscription(60);
        $ev->setLieu('Sousse');
        $ev->setStatut('COMING');
        $ev->setDescription('Nouvelle description');

        $this->assertSame('Titre Modifié', $ev->getTitre());
        $this->assertSame(100, $ev->getCapaciteMax());
        $this->assertSame(60, $ev->getFraisInscription());
        $this->assertSame('Sousse', $ev->getLieu());
        $this->assertSame('COMING', $ev->getStatut());
        $this->assertSame('Nouvelle description', $ev->getDescription());
    }

    /** DELETE — simulation */
    public function testDeleteEvenementBack(): void
    {
        $events = [
            $this->makeEvent('Événement 1'),
            $this->makeEvent('Événement 2'),
            $this->makeEvent('Événement 3'),
        ];

        $events = array_values(array_filter(
            $events,
            fn(Evennementagricole $e) => $e->getTitre() !== 'Événement 2'
        ));

        $this->assertCount(2, $events);
        $titres = array_map(fn($e) => $e->getTitre(), $events);
        $this->assertNotContains('Événement 2', $titres);
    }

    // ─── Statuts ──────────────────────────────────────────────────────────────

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

    // ─── CRUD Participants (Back) ─────────────────────────────────────────────

    /** CREATE */
    public function testCreateParticipantBack(): void
    {
        $ev = $this->makeEvent();
        $p  = $this->makeParticipant($ev, 'Mohamed Triki', 2);

        $this->assertSame('Mohamed Triki', $p->getNomParticipant());
        $this->assertSame(2, $p->getNbrPlaces());
        $this->assertSame('En attente', $p->getStatutParticipation());
        $this->assertSame('pending', $p->getConfirmation());
        $this->assertSame($ev, $p->getEvenement());
        $this->assertSame('60', $p->getMontantPayee()); // 2 × 30
    }

    /** READ */
    public function testReadParticipantBack(): void
    {
        $ev = $this->makeEvent(frais: 20);
        $p  = $this->makeParticipant($ev, 'Leila Hamdi', 4);

        $this->assertSame('Leila Hamdi', $p->getNomParticipant());
        $this->assertSame(4, $p->getNbrPlaces());
        $this->assertSame('80', $p->getMontantPayee()); // 4 × 20
        $this->assertSame('test@agricore.tn', $p->getEmail());
    }

    /** UPDATE */
    public function testUpdateParticipantBack(): void
    {
        $ev = $this->makeEvent();
        $p  = $this->makeParticipant($ev);

        $p->setNomParticipant('Nom Modifié');
        $p->setNbrPlaces(3);
        $p->setStatutParticipation('confirmed');
        $p->setConfirmation('confirmed');
        $p->setMontantPayee('90');

        $this->assertSame('Nom Modifié', $p->getNomParticipant());
        $this->assertSame(3, $p->getNbrPlaces());
        $this->assertSame('confirmed', $p->getStatutParticipation());
        $this->assertSame('confirmed', $p->getConfirmation());
        $this->assertSame('90', $p->getMontantPayee());
    }

    /** DELETE — simulation */
    public function testDeleteParticipantBack(): void
    {
        $ev = $this->makeEvent();
        $participants = [
            $this->makeParticipant($ev, 'P1'),
            $this->makeParticipant($ev, 'P2'),
            $this->makeParticipant($ev, 'P3'),
        ];

        $participants = array_values(array_filter(
            $participants,
            fn(Participants $p) => $p->getNomParticipant() !== 'P2'
        ));

        $this->assertCount(2, $participants);
        $noms = array_map(fn($p) => $p->getNomParticipant(), $participants);
        $this->assertNotContains('P2', $noms);
    }

    // ─── Logique métier Back ──────────────────────────────────────────────────

    public function testPlacesRestantesBack(): void
    {
        $ev = $this->makeEvent(capacite: 20);

        $participants = [
            $this->makeParticipant($ev, 'P1', 5),
            $this->makeParticipant($ev, 'P2', 3),
        ];

        $reservees = array_sum(array_map(fn($p) => $p->getNbrPlaces(), $participants));
        $restantes = $ev->getCapaciteMax() - $reservees;

        $this->assertSame(8, $reservees);
        $this->assertSame(12, $restantes);
    }

    public function testTauxRemplissage(): void
    {
        $ev = $this->makeEvent(capacite: 100);

        $participants = [
            $this->makeParticipant($ev, 'P1', 30),
            $this->makeParticipant($ev, 'P2', 20),
        ];

        $reservees = array_sum(array_map(fn($p) => $p->getNbrPlaces(), $participants));
        $taux = (int) round($reservees / $ev->getCapaciteMax() * 100);

        $this->assertSame(50, $taux);
    }

    public function testEvenementComplet(): void
    {
        $ev = $this->makeEvent(capacite: 5);

        $participants = [
            $this->makeParticipant($ev, 'P1', 3),
            $this->makeParticipant($ev, 'P2', 2),
        ];

        $reservees = array_sum(array_map(fn($p) => $p->getNbrPlaces(), $participants));
        $restantes = max(0, $ev->getCapaciteMax() - $reservees);

        $this->assertSame(0, $restantes); // complet
    }

    public function testEntryCodeRange(): void
    {
        $ev = $this->makeEvent();
        $p  = $this->makeParticipant($ev);

        $this->assertGreaterThanOrEqual(100000, $p->getEntryCode());
        $this->assertLessThanOrEqual(999999, $p->getEntryCode());
    }
}
