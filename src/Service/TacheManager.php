<?php

namespace App\Service;

use App\Entity\Tache;

class TacheManager
{
    public function validate(Tache $tache): bool
    {
        try {
            $name = $tache->getNomTache();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException('Le nom de la tâche est obligatoire');
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('Le nom de la tâche est obligatoire');
        }

        $date = $tache->getDatePrevue();
        if ($date instanceof \DateTimeInterface) {
            $today = (new \DateTimeImmutable('today'));
            if ($date < $today) {
                throw new \InvalidArgumentException('La date prévue ne peut pas être antérieure à aujourd\'hui');
            }
        }

        return true;
    }
}
