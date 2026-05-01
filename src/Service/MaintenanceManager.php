<?php

namespace App\Service;

use App\Entity\Maintenance;

class MaintenanceManager
{
    public function validate(Maintenance $maintenance): bool
    {
        try {
            $name = $maintenance->getNomMaintenance();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException('Le nom de la maintenance est obligatoire');
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('Le nom de la maintenance est obligatoire');
        }

        try {
            $description = $maintenance->getDescription();
        } catch (\Error | \TypeError $e) {
            throw new \InvalidArgumentException('La description doit faire au moins 7 caractères');
        }

        if (strlen(trim($description ?? '')) < 7) {
            throw new \InvalidArgumentException('La description doit faire au moins 7 caractères');
        }

        return true;
    }
}
