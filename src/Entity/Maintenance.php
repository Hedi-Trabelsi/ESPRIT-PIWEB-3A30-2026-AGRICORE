<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Tache;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
// Indispensable pour les contrôles de saisie (Workshop Symfony 6)
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Maintenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_maintenance;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(min: 3, minMessage: "Le nom doit contenir au moins 3 caractères.")]
    #[Assert\Regex(
    pattern: "/^[a-zA-Z\s]+$/",
    message: "Le nom ne doit contenir que des lettres."
)]
private string $nom_maintenance;

  #[ORM\Column(type: "string", length: 150)]
    #[Assert\NotBlank(message: "L'équipement est obligatoire.")]
    #[Assert\Regex(
        // Force au moins 3 lettres ET au moins 3 caractères au total
        pattern: "/^(?=(?:.*[a-zA-Z]){3,})[a-zA-Z0-9\s]{3,}$/",
        message: "L'équipement doit contenir au moins 3 lettres."
    )]
    private string $equipement;
#[ORM\Column(type: "string", length: 150)]
#[Assert\NotBlank(message: "Le lieu est obligatoire.")]
#[Assert\Regex(
    pattern: "/^(?=(?:.*[a-zA-Z]){3,})[a-zA-Z0-9\s]{3,}$/",
    message: "Le lieu doit contenir au moins 3 lettres."
)]
private string $lieu;

   #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Regex(
        // Force au moins 7 caractères ET interdit d'avoir UNIQUEMENT des chiffres
        pattern: "/^(?![0-9]*$)[a-zA-Z0-9\s\.,!?]{7,}$/",
        message: "La description doit faire au moins 7 caractères et ne pas contenir que des chiffres."
    )]
    private string $description;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_declaration;

    #[ORM\Column(type: "string", length: 25)]
    private string $type;

    #[ORM\Column(type: "string", length: 25)]
    private string $statut;

    #[ORM\Column(type: "string", length: 20)]
    private string $priorite;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "maintenances")]
    #[ORM\JoinColumn(name: 'id_agriculteur', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $id_agriculteur = null;

    #[ORM\OneToMany(mappedBy: "id_maintenance", targetEntity: Tache::class)]
    private Collection $taches;

    public function __construct()
    {
        $this->taches = new ArrayCollection();
    }

    // --- GETTERS & SETTERS ---

    public function getId_maintenance(): int
    {
        return $this->id_maintenance;
    }

    public function getNomMaintenance(): string
    {
        return $this->nom_maintenance;
    }

   public function setNomMaintenance(?string $value): self 
{
    $this->nom_maintenance = (string) $value; 
    return $this;
}

    public function getEquipement(): string
    {
        return $this->equipement;
    }

    public function setEquipement(?string $value): self 
{
    $this->equipement = (string) $value;
    return $this;
}

    public function getLieu(): string
    {
        return $this->lieu;
    }

   public function setLieu(?string $value): self 
{
    $this->lieu = (string) $value;
    return $this;
}

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(?string $value): self 
{
    $this->description = (string) $value;
    return $this;
}

    public function getDateDeclaration(): \DateTimeInterface
    {
        return $this->date_declaration;
    }

    public function setDateDeclaration(\DateTimeInterface $value): self
    {
        $this->date_declaration = $value;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $value): self
    {
        $this->type = $value;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $value): self
    {
        $this->statut = $value;
        return $this;
    }

    public function getPriorite(): string
    {
        return $this->priorite;
    }

    public function setPriorite(string $value): self
    {
        $this->priorite = $value;
        return $this;
    }

    public function getId_agriculteur(): ?User
    {
        return $this->id_agriculteur;
    }

    public function setId_agriculteur(?User $value): self
    {
        $this->id_agriculteur = $value;
        return $this;
    }

    public function getTaches(): Collection
    {
        return $this->taches;
    }

    public function addTache(Tache $tache): self
    {
        if (!$this->taches->contains($tache)) {
            $this->taches[] = $tache;
            $tache->setId_maintenance($this);
        }
        return $this;
    }

    public function removeTache(Tache $tache): self
    {
        if ($this->taches->removeElement($tache)) {
            if ($tache->getId_maintenance() === $this) {
                $tache->setId_maintenance(null);
            }
        }
        return $this;
    }
}
