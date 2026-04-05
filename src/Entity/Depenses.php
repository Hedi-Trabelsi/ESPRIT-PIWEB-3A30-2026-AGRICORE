<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
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

    public function getId_depense()
    {
        return $this->id_depense;
    }

    public function setId_depense($value)
    {
        $this->id_depense = $value;
    }

    public function getType_depense()
    {
        return $this->type_depense;
    }

    public function setType_depense($value)
    {
        $this->type_depense = $value;
    }

    public function getMontant()
    {
        return $this->montant;
    }

    public function setMontant($value)
    {
        $this->montant = $value;
    }

    public function getDate()
    {
        return $this->Date;
    }

    public function setDate($value)
    {
        $this->Date = $value;
    }
}
