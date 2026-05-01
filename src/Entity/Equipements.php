<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use App\Entity\Panier;

#[ORM\Entity]
#[ORM\Table(name: 'equipements_legacy')]
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

    public function getId_equipement(): int
    {
        return $this->id_equipement;
    }

    public function setId_equipement(int $value): void
    {
        $this->id_equipement = $value;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $value): void
    {
        $this->nom = $value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $value): void
    {
        $this->type = $value;
    }

    public function getPrix(): string
    {
        return $this->prix;
    }

    public function setPrix(string $value): void
    {
        $this->prix = $value;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $value): void
    {
        $this->quantite = $value;
    }

    public function getId_fournisseur(): User
    {
        return $this->id_fournisseur;
    }

    public function setId_fournisseur(User $value): void
    {
        $this->id_fournisseur = $value;
    }

    /** @var Collection<int, Panier> */
    #[ORM\OneToMany(mappedBy: "id_equipement", targetEntity: Panier::class)]
    private Collection $paniers;

    public function __construct()
    {
        $this->paniers = new ArrayCollection();
    }

    /**
     * @return Collection<int, Panier>
     */
    public function getPaniers(): Collection
    {
        return $this->paniers;
    }

    public function addPanier(Panier $panier): self
    {
        if (!$this->paniers->contains($panier)) {
            $this->paniers[] = $panier;
        }

        return $this;
    }

    public function removePanier(Panier $panier): self
    {
        $this->paniers->removeElement($panier);

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
