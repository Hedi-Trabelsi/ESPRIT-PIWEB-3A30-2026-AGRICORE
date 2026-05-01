<?php

namespace App\Service;

use App\Entity\SuiviAnimal;

/**
 * Service métier pour la validation des règles de l'entité SuiviAnimal.
 *
 * Règles métier :
 * 1. La température doit être entre 30°C et 45°C
 * 2. Le poids doit être positif (> 0)
 * 3. Le rythme cardiaque doit être positif (> 0)
 * 4. L'état de santé doit être 'Bon', 'Moyen' ou 'Mauvais'
 * 5. La date du suivi est obligatoire
 */
class SuiviAnimalManager
{
    /**
     * Valide les règles métier d'un suivi animal.
     *
     * @throws \InvalidArgumentException si une règle métier est violée
     */
    public function validate(SuiviAnimal $suivi): bool
    {
        // Règle 1 : La température doit être entre 30°C et 45°C
        if ($suivi->getTemperature() !== null) {
            if ($suivi->getTemperature() < 30.0 || $suivi->getTemperature() > 45.0) {
                throw new \InvalidArgumentException('La température doit être entre 30°C et 45°C.');
            }
        }

        // Règle 2 : Le poids doit être positif
        if ($suivi->getPoids() !== null && $suivi->getPoids() <= 0) {
            throw new \InvalidArgumentException('Le poids doit être supérieur à zéro.');
        }

        // Règle 3 : Le rythme cardiaque doit être positif
        if ($suivi->getRythmeCardiaque() !== null && $suivi->getRythmeCardiaque() <= 0) {
            throw new \InvalidArgumentException('Le rythme cardiaque doit être supérieur à zéro.');
        }

        // Règle 4 : L'état de santé doit être valide
        $etatsValides = ['Bon', 'Moyen', 'Mauvais'];
        if ($suivi->getEtatSante() !== null && !in_array($suivi->getEtatSante(), $etatsValides, true)) {
            throw new \InvalidArgumentException("L'état de santé doit être 'Bon', 'Moyen' ou 'Mauvais'.");
        }

        // Règle 5 : La date du suivi est obligatoire
        if ($suivi->getDateSuivi() === null) {
            throw new \InvalidArgumentException('La date du suivi est obligatoire.');
        }

        return true;
    }
}
