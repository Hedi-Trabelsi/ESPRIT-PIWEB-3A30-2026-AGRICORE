<?php

namespace App\Service;

use App\Entity\Participants;

class ParticipantsManager
{
    public function validate(Participants $participant): bool
    {
        try {
            $nom = $participant->getNomParticipant();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException('Le nom du participant est obligatoire.');
        }

        if (empty($nom)) {
            throw new \InvalidArgumentException('Le nom du participant est obligatoire.');
        }

        if (strlen(trim($nom)) < 3) {
            throw new \InvalidArgumentException('Le nom du participant doit contenir au moins 3 caractères.');
        }

        try {
            $email = $participant->getEmailAddress();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException("L'adresse email est obligatoire.");
        }

        if (empty($email)) {
            throw new \InvalidArgumentException("L'adresse email est obligatoire.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("L'adresse email n'est pas valide.");
        }

        try {
            $nbrPlaces = $participant->getNbrPlaces();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException('Le nombre de places est obligatoire.');
        }

        if ($nbrPlaces === null) {
            throw new \InvalidArgumentException('Le nombre de places est obligatoire.');
        }

        if ($nbrPlaces < 1) {
            throw new \InvalidArgumentException('Le nombre de places doit être au moins 1.');
        }

        return true;
    }
}
