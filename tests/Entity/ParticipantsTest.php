<?php

namespace App\Tests\Entity;

use App\Entity\Participants;
use App\Entity\Evennementagricole;
use PHPUnit\Framework\TestCase;

class ParticipantsTest extends TestCase
{
    public function testEntityInitialization(): void
    {
        $participant = new Participants();
        $this->assertNull($participant->getEvenement());
        $this->assertEquals(0, $participant->getNbrPresents());
        $this->assertEquals(0, $participant->getUsedCoins());
    }

    public function testSettersAndGetters(): void
    {
        $participant = new Participants();
        $event = new Evennementagricole();
        
        $participant->setIdUtilisateur(123);
        $this->assertEquals(123, $participant->getIdUtilisateur());
        
        $participant->setEvenement($event);
        $this->assertSame($event, $participant->getEvenement());
        
        $date = new \DateTime('2026-06-01');
        $participant->setDateInscription($date);
        $this->assertEquals($date, $participant->getDateInscription());
        
        $participant->setStatutParticipation('En attente');
        $this->assertEquals('En attente', $participant->getStatutParticipation());
        
        $participant->setMontantPayee('100.50');
        $this->assertEquals('100.50', $participant->getMontantPayee());
        
        $participant->setConfirmation('confirmed');
        $this->assertEquals('confirmed', $participant->getConfirmation());
        
        $participant->setNbrPlaces(5);
        $this->assertEquals(5, $participant->getNbrPlaces());
        
        $participant->setNomParticipant('Test User');
        $this->assertEquals('Test User', $participant->getNomParticipant());
        
        $participant->setEntryCode(123456);
        $this->assertEquals(123456, $participant->getEntryCode());
        
        $participant->setEmail('test@example.com');
        $this->assertEquals('test@example.com', $participant->getEmail());
        
        $participant->setConfirmToken('test-token-123');
        $this->assertEquals('test-token-123', $participant->getConfirmToken());
        
        $participant->setNbrPresents(3);
        $this->assertEquals(3, $participant->getNbrPresents());
        
        $participant->setUsedCoins(10);
        $this->assertEquals(10, $participant->getUsedCoins());
    }

    public function testPresenceData(): void
    {
        $participant = new Participants();
        
        $participant->setPresenceData(1, 2);
        $this->assertEquals([1 => 2], $participant->getPresenceData());
        
        $participant->setPresenceData(2, 3);
        $this->assertEquals([1 => 2, 2 => 3], $participant->getPresenceData());
        
        $participant->setPresenceData(3, 1);
        $this->assertEquals([1 => 2, 2 => 3, 3 => 1], $participant->getPresenceData());
    }
}
