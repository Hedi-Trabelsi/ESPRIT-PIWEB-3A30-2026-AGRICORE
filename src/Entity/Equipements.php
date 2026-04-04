<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use App\Entity\Panier;

#[ORM\Entity]
class Equipements
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_equipement;

    #[ORM\Column(type: "string", length: 25)]
    private string $nom;

    #[ORM\Column(type: "string", length: 25)]
    private string $type;

    #[ORM\Column(type: "string", length: 25)]
    private string $prix;

    #[ORM\Column(type: "integer")]
    private int $quantite;

        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "equipementss")]
    #[ORM\JoinColumn(name: 'id_fournisseur', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $id_fournisseur;

    public function getId_equipement()
    {
        return $this->id_equipement;
    }

    public function setId_equipement($value)
    {
        $this->id_equipement = $value;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
    }

    public function getPrix()
    {
        return $this->prix;
    }

    public function setPrix($value)
    {
        $this->prix = $value;
    }

    public function getQuantite()
    {
        return $this->quantite;
    }

    public function setQuantite($value)
    {
        $this->quantite = $value;
    }

    public function getId_fournisseur()
    {
        return $this->id_fournisseur;
    }

    public function setId_fournisseur($value)
    {
        $this->id_fournisseur = $value;
    }

    #[ORM\OneToMany(mappedBy: "id_equipement", targetEntity: Panier::class)]
    private Collection $paniers;

    public function __construct()
    {
        $this->paniers = new ArrayCollection();
    }

        public function getPaniers(): Collection
        {
            return $this->paniers;
        }
    
        public function addPanier(Panier $panier): self
        {
            if (!$this->paniers->contains($panier)) {
                $this->paniers[] = $panier;
                $panier->setId_equipement($this);
            }
    
            return $this;
        }
    
        public function removePanier(Panier $panier): self
        {
            if ($this->paniers->removeElement($panier)) {
                // set the owning side to null (unless already changed)
                if ($panier->getId_equipement() === $this) {
                    $panier->setId_equipement(null);
                }
            }
    
            return $this;
        }

        public function getIdEquipement(): ?int
        {
            return $this->id_equipement;
        }

        public function getIdFournisseur(): ?User
        {
            return $this->id_fournisseur;
        }

        public function setIdFournisseur(?User $id_fournisseur): static
        {
            $this->id_fournisseur = $id_fournisseur;

            return $this;
        }
}
