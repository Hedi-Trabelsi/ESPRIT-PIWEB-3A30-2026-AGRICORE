<?php

namespace App\Service;

use App\Entity\User;

class UserManager
{
    /**
     * Valide une entite User selon les regles metier definies.
     *
     * Regles metier:
     *   1. Le nom est obligatoire (non vide).
     *   2. Le prenom est obligatoire (non vide).
     *   3. L'email doit etre valide (format RFC).
     *   4. Le mot de passe doit contenir au moins 6 caracteres
     *      avec au moins une minuscule, une majuscule et un chiffre.
     *   5. Le numero de telephone doit etre un nombre positif (> 0).
     *   6. Le role doit etre 0 (Admin), 1 (Agriculteur) ou 2 (Technicien).
     *
     * @throws \InvalidArgumentException si une des regles n'est pas respectee
     */
    public function validate(User $user): bool
    {
        // 1. Nom obligatoire
        if (empty($user->getNom())) {
            throw new \InvalidArgumentException('Le nom est obligatoire');
        }

        // 2. Prenom obligatoire
        if (empty($user->getPrenom())) {
            throw new \InvalidArgumentException('Le prenom est obligatoire');
        }

        // 3. Email valide
        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }

        // 4. Mot de passe (min 6 chars + minuscule + majuscule + chiffre)
        $password = $user->getPassword();
        if (strlen($password) < 6) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 6 caracteres');
        }
        if (!preg_match('/[a-z]/', $password)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins une minuscule');
        }
        if (!preg_match('/[A-Z]/', $password)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins une majuscule');
        }
        if (!preg_match('/[0-9]/', $password)) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins un chiffre');
        }

        // 5. Numero de telephone positif
        if ($user->getNumeroT() === null || $user->getNumeroT() <= 0) {
            throw new \InvalidArgumentException('Le numero de telephone doit etre un nombre positif');
        }

        // 6. Role valide (0, 1 ou 2)
        if (!in_array($user->getRole(), [0, 1, 2], true)) {
            throw new \InvalidArgumentException('Le role doit etre 0 (Admin), 1 (Agriculteur) ou 2 (Technicien)');
        }

        return true;
    }
}
