<?php

namespace App\Tests\Service;

use App\Entity\Participants;
use App\Service\ParticipantsManager;
use PHPUnit\Framework\TestCase;

class ParticipantsManagerTest extends TestCase
{
    public function testValidParticipant(): void
    {
        $participant = new Participants();
        $participant->setNomParticipant('Valid Participant');
        $participant->setEmail('valid@example.com');
        $participant->setNbrPlaces(2);

        $manager = new ParticipantsManager();
        $this->assertTrue($manager->validate($participant));
    }

    public function testParticipantWithoutNom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $participant = new Participants();
        $participant->setEmail('valid@example.com');
        $participant->setNbrPlaces(2);

        $manager = new ParticipantsManager();
        $manager->validate($participant);
    }

    public function testParticipantShortNom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $participant = new Participants();
        $participant->setNomParticipant('Hi');
        $participant->setEmail('valid@example.com');
        $participant->setNbrPlaces(2);

        $manager = new ParticipantsManager();
        $manager->validate($participant);
    }

    public function testParticipantWithoutEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $participant = new Participants();
        $participant->setNomParticipant('Valid Participant');
        $participant->setNbrPlaces(2);

        $manager = new ParticipantsManager();
        $manager->validate($participant);
    }

    public function testParticipantInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $participant = new Participants();
        $participant->setNomParticipant('Valid Participant');
        $participant->setEmail('invalid-email');
        $participant->setNbrPlaces(2);

        $manager = new ParticipantsManager();
        $manager->validate($participant);
    }

    public function testParticipantZeroPlaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $participant = new Participants();
        $participant->setNomParticipant('Valid Participant');
        $participant->setEmail('valid@example.com');
        $participant->setNbrPlaces(0);

        $manager = new ParticipantsManager();
        $manager->validate($participant);
    }
}
