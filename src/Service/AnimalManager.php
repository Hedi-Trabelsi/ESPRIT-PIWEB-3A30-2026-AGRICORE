<?php

namespace App\Service;

use App\Entity\Animal;

/**
 * Service métier pour la validation des règles de l'entité Animal.
 *
 * Règles métier :
 * 1. Le code animal est obligatoire
 * 2. L'espèce est obligatoire
 * 3. Le sexe doit être 'Mâle' ou 'Femelle'
 * 4. La date de naissance ne peut pas être dans le futur
 */
class AnimalManager
{
    /**
     * Valide les règles métier d'un animal.
     *
     * @throws \InvalidArgumentException si une règle métier est violée
     */
    public function validate(Animal $animal): bool
    {
        // Règle 1 : Le code animal est obligatoire
        if (empty($animal->getCodeAnimal())) {
            throw new \InvalidArgumentException('Le code animal est obligatoire.');
        }

        // Règle 2 : L'espèce est obligatoire
        if (empty($animal->getEspece())) {
            throw new \InvalidArgumentException("L'espèce est obligatoire.");
        }

        // Règle 3 : Le sexe doit être Mâle ou Femelle
        $sexesValides = ['Mâle', 'Femelle'];
        if ($animal->getSexe() !== null && !in_array($animal->getSexe(), $sexesValides, true)) {
            throw new \InvalidArgumentException("Le sexe doit être 'Mâle' ou 'Femelle'.");
        }

        // Règle 4 : La date de naissance ne peut pas être dans le futur
        if ($animal->getDateNaissance() !== null) {
            $today = new \DateTime('today');
            if ($animal->getDateNaissance() > $today) {
                throw new \InvalidArgumentException('La date de naissance ne peut pas être dans le futur.');
            }
        }

        return true;
    }
}
