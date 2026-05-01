<?php

namespace App\Service;

use App\Entity\Evennementagricole;

class EvenementagricoleManager
{
    public function validate(Evennementagricole $event): bool
    {
        try {
            $titre = $event->getTitre();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }

        if (empty($titre)) {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }

        if (strlen(trim($titre)) < 5) {
            throw new \InvalidArgumentException('Le titre doit contenir au moins 5 caractères.');
        }

        try {
            $description = $event->getDescription();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException('La description est obligatoire.');
        }

        if (empty($description)) {
            throw new \InvalidArgumentException('La description est obligatoire.');
        }

        if (strlen(trim($description)) < 10) {
            throw new \InvalidArgumentException('La description doit contenir au moins 10 caractères.');
        }

        try {
            $lieu = $event->getLieu();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException('Le lieu est obligatoire.');
        }

        if (empty($lieu)) {
            throw new \InvalidArgumentException('Le lieu est obligatoire.');
        }

        try {
            $dateDebut = $event->getDateDebut();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException('La date de début est obligatoire.');
        }

        if (!$dateDebut) {
            throw new \InvalidArgumentException('La date de début est obligatoire.');
        }

        try {
            $dateFin = $event->getDateFin();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException('La date de fin est obligatoire.');
        }

        if (!$dateFin) {
            throw new \InvalidArgumentException('La date de fin est obligatoire.');
        }

        if ($dateFin <= $dateDebut) {
            throw new \InvalidArgumentException('La date de fin doit être après la date de début.');
        }

        try {
            $fraisInscription = $event->getFraisInscription();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException('Les frais d\'inscription sont obligatoires.');
        }

        if ($fraisInscription === null) {
            throw new \InvalidArgumentException('Les frais d\'inscription sont obligatoires.');
        }

        if ($fraisInscription < 0) {
            throw new \InvalidArgumentException('Les frais d\'inscription ne peuvent pas être négatifs.');
        }

        try {
            $capaciteMax = $event->getCapaciteMax();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException('La capacité maximale est obligatoire.');
        }

        if ($capaciteMax === null) {
            throw new \InvalidArgumentException('La capacité maximale est obligatoire.');
        }

        if ($capaciteMax <= 0) {
            throw new \InvalidArgumentException('La capacité maximale doit être supérieure à 0.');
        }

        return true;
    }

    public function getDiscountPercentage(int $coins): int
    {
        $pct = floor($coins / 10) * 10;
        return min(100, (int)$pct);
    }
}
