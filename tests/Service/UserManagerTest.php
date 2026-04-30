<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    /**
     * Cas valide : toutes les regles metier sont respectees.
     */
    public function testValidUser(): void
    {
        $user = new User();
        $user->setNom('Trabelsi');
        $user->setPrenom('Hedi');
        $user->setEmail('hedi.trabelsi@gmail.com');
        $user->setPassword('Password1');
        $user->setNumeroT(21987654);
        $user->setRole(1);
        $user->setAdresse('Ariana');
        $user->setGenre('Homme');

        $manager = new UserManager();

        $this->assertTrue($manager->validate($user));
    }

    /**
     * Regle 1 : le nom est obligatoire.
     */
    public function testUserWithoutNom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire');

        $user = new User();
        $user->setNom('');
        $user->setPrenom('Hedi');
        $user->setEmail('hedi@gmail.com');
        $user->setPassword('Password1');
        $user->setNumeroT(21987654);
        $user->setRole(1);
        $user->setAdresse('Ariana');
        $user->setGenre('Homme');

        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Regle 2 : le prenom est obligatoire.
     */
    public function testUserWithoutPrenom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prenom est obligatoire');

        $user = new User();
        $user->setNom('Trabelsi');
        $user->setPrenom('');
        $user->setEmail('hedi@gmail.com');
        $user->setPassword('Password1');
        $user->setNumeroT(21987654);
        $user->setRole(1);
        $user->setAdresse('Ariana');
        $user->setGenre('Homme');

        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Regle 3 : l'email doit etre valide.
     */
    public function testUserWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email invalide');

        $user = new User();
        $user->setNom('Trabelsi');
        $user->setPrenom('Hedi');
        $user->setEmail('email_invalide');
        $user->setPassword('Password1');
        $user->setNumeroT(21987654);
        $user->setRole(1);
        $user->setAdresse('Ariana');
        $user->setGenre('Homme');

        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Regle 4a : le mot de passe doit avoir au moins 6 caracteres.
     */
    public function testUserWithShortPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le mot de passe doit contenir au moins 6 caracteres');

        $user = new User();
        $user->setNom('Trabelsi');
        $user->setPrenom('Hedi');
        $user->setEmail('hedi@gmail.com');
        $user->setPassword('Aa1');
        $user->setNumeroT(21987654);
        $user->setRole(1);
        $user->setAdresse('Ariana');
        $user->setGenre('Homme');

        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Regle 4b : le mot de passe doit contenir une majuscule.
     */
    public function testUserWithWeakPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('majuscule');

        $user = new User();
        $user->setNom('Trabelsi');
        $user->setPrenom('Hedi');
        $user->setEmail('hedi@gmail.com');
        $user->setPassword('password1');
        $user->setNumeroT(21987654);
        $user->setRole(1);
        $user->setAdresse('Ariana');
        $user->setGenre('Homme');

        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Regle 5 : le numero de telephone doit etre positif.
     */
    public function testUserWithNegativePhone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('numero de telephone');

        $user = new User();
        $user->setNom('Trabelsi');
        $user->setPrenom('Hedi');
        $user->setEmail('hedi@gmail.com');
        $user->setPassword('Password1');
        $user->setNumeroT(-5);
        $user->setRole(1);
        $user->setAdresse('Ariana');
        $user->setGenre('Homme');

        $manager = new UserManager();
        $manager->validate($user);
    }

    /**
     * Regle 6 : le role doit etre 0, 1 ou 2.
     */
    public function testUserWithInvalidRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('role');

        $user = new User();
        $user->setNom('Trabelsi');
        $user->setPrenom('Hedi');
        $user->setEmail('hedi@gmail.com');
        $user->setPassword('Password1');
        $user->setNumeroT(21987654);
        $user->setRole(5);
        $user->setAdresse('Ariana');
        $user->setGenre('Homme');

        $manager = new UserManager();
        $manager->validate($user);
    }
}
