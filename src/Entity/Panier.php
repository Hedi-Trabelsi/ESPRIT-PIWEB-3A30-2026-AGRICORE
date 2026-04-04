<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Equipements;

#[ORM\Entity]
class Panier
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_panier;

        #[ORM\ManyToOne(targetEntity: Equipements::class, inversedBy: "paniers")]
    #[ORM\JoinColumn(name: 'id_equipement', referencedColumnName: 'id_equipement', onDelete: 'CASCADE')]
    private Equipements $id_equipement;

    #[ORM\Column(type: "integer")]
    private int $quantite;

    #[ORM\Column(type: "string", length: 25)]
    private string $total;

    #[ORM\Column(type: "integer")]
    private int $id_agriculteur;

    public function getId_panier()
    {
        return $this->id_panier;
    }

    public function setId_panier($value)
    {
        $this->id_panier = $value;
    }

    public function getId_equipement()
    {
        return $this->id_equipement;
    }

    public function setId_equipement($value)
    {
        $this->id_equipement = $value;
    }

    public function getQuantite()
    {
        return $this->quantite;
    }

    public function setQuantite($value)
    {
        $this->quantite = $value;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function setTotal($value)
    {
        $this->total = $value;
    }

    public function getId_agriculteur()
    {
        return $this->id_agriculteur;
    }

    public function setId_agriculteur($value)
    {
        $this->id_agriculteur = $value;
    }
}
