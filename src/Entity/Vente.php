<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Vente
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idVente;

    #[ORM\Column(type: "integer")]
    private int $prixUnitaire;

    #[ORM\Column(type: "integer")]
    private int $quantite;

    #[ORM\Column(type: "integer")]
    private int $chiffreAffaires;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date;

    #[ORM\Column(type: "string", length: 25)]
    private string $produit;

    #[ORM\Column(type: "integer")]
    private int $userId;

    public function getIdVente()
    {
        return $this->idVente;
    }

    public function setIdVente($value)
    {
        $this->idVente = $value;
    }

    public function getPrixUnitaire()
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire($value)
    {
        $this->prixUnitaire = $value;
    }

    public function getQuantite()
    {
        return $this->quantite;
    }

    public function setQuantite($value)
    {
        $this->quantite = $value;
    }

    public function getChiffreAffaires()
    {
        return $this->chiffreAffaires;
    }

    public function setChiffreAffaires($value)
    {
        $this->chiffreAffaires = $value;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($value)
    {
        $this->date = $value;
    }

    public function getProduit()
    {
        return $this->produit;
    }

    public function setProduit($value)
    {
        $this->produit = $value;
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
