<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Utilisateurs;

#[ORM\Entity]
class Ventes
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_vente;

        #[ORM\ManyToOne(targetEntity: Utilisateurs::class, inversedBy: "ventess")]
    #[ORM\JoinColumn(name: 'id_client', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateurs $id_client;

    #[ORM\Column(type: "string", length: 25)]
    private string $prix_unitaire;

    #[ORM\Column(type: "integer")]
    private int $quantite;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_vente;

    #[ORM\Column(type: "string", length: 25)]
    private string $produit;

    public function getId_vente()
    {
        return $this->id_vente;
    }

    public function setId_vente($value)
    {
        $this->id_vente = $value;
    }

    public function getId_client()
    {
        return $this->id_client;
    }

    public function setId_client($value)
    {
        $this->id_client = $value;
    }

    public function getPrix_unitaire()
    {
        return $this->prix_unitaire;
    }

    public function setPrix_unitaire($value)
    {
        $this->prix_unitaire = $value;
    }

    public function getQuantite()
    {
        return $this->quantite;
    }

    public function setQuantite($value)
    {
        $this->quantite = $value;
    }

    public function getDate_vente()
    {
        return $this->date_vente;
    }

    public function setDate_vente($value)
    {
        $this->date_vente = $value;
    }

    public function getProduit()
    {
        return $this->produit;
    }

    public function setProduit($value)
    {
        $this->produit = $value;
    }
}
