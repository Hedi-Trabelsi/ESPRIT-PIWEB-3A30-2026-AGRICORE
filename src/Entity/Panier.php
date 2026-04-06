<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PanierRepository;

#[ORM\Entity(repositoryClass: PanierRepository::class)]
#[ORM\Table(name: 'panier')]
class Panier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_panier = null;

    public function getId_panier(): ?int
    {
        return $this->id_panier;
    }

    public function setId_panier(int $id_panier): self
    {
        $this->id_panier = $id_panier;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Equipement::class, inversedBy: 'paniers')]
    #[ORM\JoinColumn(name: 'id_equipement', referencedColumnName: 'id_equipement')]
    private ?Equipement $equipement = null;

    public function getEquipement(): ?Equipement
    {
        return $this->equipement;
    }

    public function setEquipement(?Equipement $equipement): self
    {
        $this->equipement = $equipement;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $quantite = null;

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $total = null;

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): self
    {
        $this->total = $total;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_agriculteur = null;

    public function getId_agriculteur(): ?int
    {
        return $this->id_agriculteur;
    }

    public function setId_agriculteur(int $id_agriculteur): self
    {
        $this->id_agriculteur = $id_agriculteur;
        return $this;
    }

    public function getIdPanier(): ?int
    {
        return $this->id_panier;
    }

    public function getIdAgriculteur(): ?int
    {
        return $this->id_agriculteur;
    }

    public function setIdAgriculteur(int $id_agriculteur): static
    {
        $this->id_agriculteur = $id_agriculteur;

        return $this;
    }

}
