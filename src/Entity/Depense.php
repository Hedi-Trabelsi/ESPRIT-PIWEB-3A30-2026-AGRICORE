<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\User;

#[ORM\Entity]
class Depense
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idDepense;

    #[ORM\Column(type: "string")]
    private string $type;

    #[ORM\Column(type: "float")]
    private float $montant;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date;

        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "depenses")]
    #[ORM\JoinColumn(name: 'userId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $userId;

    public function getIdDepense()
    {
        return $this->idDepense;
    }

    public function setIdDepense($value)
    {
        $this->idDepense = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
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
        return $this->date;
    }

    public function setDate($value)
    {
        $this->date = $value;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($value)
    {
        $this->userId = $value;
    }
}
