<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


##[ORM\Entity]
class Depenses
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_depense;

    #[ORM\Column(type: "string", length: 25)]
    private string $type_depense;

    #[ORM\Column(type: "string", length: 25)]
    private string $montant;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $Date;

    public function getId_depense(): int
    {
        return $this->id_depense;
    }

    public function setId_depense(int $value): void
    {
        $this->id_depense = $value;
    }

    public function getType_depense(): string
    {
        return $this->type_depense;
    }

    public function setType_depense(string $value): void
    {
        $this->type_depense = $value;
    }

    public function getMontant(): string
    {
        return $this->montant;
    }

    public function setMontant(string $value): void
    {
        $this->montant = $value;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->Date;
    }

    public function setDate(\DateTimeInterface $value): void
    {
        $this->Date = $value;
    }
}
