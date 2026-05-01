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

    public function getId_vente(): int
    {
        return $this->id_vente;
    }

    public function setId_vente(int $value): void
    {
        $this->id_vente = $value;
    }

    public function getId_client(): Utilisateurs
    {
        return $this->id_client;
    }

    public function setId_client(Utilisateurs $value): void
    {
        $this->id_client = $value;
    }

    public function getPrix_unitaire(): string
    {
        return $this->prix_unitaire;
    }

    public function setPrix_unitaire(string $value): void
    {
        $this->prix_unitaire = $value;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $value): void
    {
        $this->quantite = $value;
    }

    public function getDate_vente(): \DateTimeInterface
    {
        return $this->date_vente;
    }

    public function setDate_vente(\DateTimeInterface $value): void
    {
        $this->date_vente = $value;
    }

    public function getProduit(): string
    {
        return $this->produit;
    }

    public function setProduit(string $value): void
    {
        $this->produit = $value;
    }
}
