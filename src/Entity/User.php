<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom = null;

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $prenom = null;

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $date = null;

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $adresse = null;

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $role = null;

    public function getRole(): ?int
    {
        return $this->role;
    }

    public function setRole(int $role): self
    {
        $this->role = $role;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $numeroT = null;

    public function getNumeroT(): ?int
    {
        return $this->numeroT;
    }

    public function setNumeroT(int $numeroT): self
    {
        $this->numeroT = $numeroT;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'blob', nullable: true)]
    private ?string $image = null;

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $password = null;

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $genre = null;

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(string $genre): self
    {
        $this->genre = $genre;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $profile_complete = null;

    public function isProfile_complete(): ?bool
    {
        return $this->profile_complete;
    }

    public function setProfile_complete(?bool $profile_complete): self
    {
        $this->profile_complete = $profile_complete;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Depense::class, mappedBy: 'user')]
    private Collection $depenses;

    /**
     * @return Collection<int, Depense>
     */
    public function getDepenses(): Collection
    {
        if (!$this->depenses instanceof Collection) {
            $this->depenses = new ArrayCollection();
        }
        return $this->depenses;
    }

    public function addDepense(Depense $depense): self
    {
        if (!$this->getDepenses()->contains($depense)) {
            $this->getDepenses()->add($depense);
        }
        return $this;
    }

    public function removeDepense(Depense $depense): self
    {
        $this->getDepenses()->removeElement($depense);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Equipement::class, mappedBy: 'user')]
    private Collection $equipements;

    /**
     * @return Collection<int, Equipement>
     */
    public function getEquipements(): Collection
    {
        if (!$this->equipements instanceof Collection) {
            $this->equipements = new ArrayCollection();
        }
        return $this->equipements;
    }

    public function addEquipement(Equipement $equipement): self
    {
        if (!$this->getEquipements()->contains($equipement)) {
            $this->getEquipements()->add($equipement);
        }
        return $this;
    }

    public function removeEquipement(Equipement $equipement): self
    {
        $this->getEquipements()->removeElement($equipement);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Maintenance::class, mappedBy: 'user')]
    private Collection $maintenances;

    /**
     * @return Collection<int, Maintenance>
     */
    public function getMaintenances(): Collection
    {
        if (!$this->maintenances instanceof Collection) {
            $this->maintenances = new ArrayCollection();
        }
        return $this->maintenances;
    }

    public function addMaintenance(Maintenance $maintenance): self
    {
        if (!$this->getMaintenances()->contains($maintenance)) {
            $this->getMaintenances()->add($maintenance);
        }
        return $this;
    }

    public function removeMaintenance(Maintenance $maintenance): self
    {
        $this->getMaintenances()->removeElement($maintenance);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Vente::class, mappedBy: 'user')]
    private Collection $ventes;

    public function __construct()
    {
        $this->depenses = new ArrayCollection();
        $this->equipements = new ArrayCollection();
        $this->maintenances = new ArrayCollection();
        $this->ventes = new ArrayCollection();
    }

    /**
     * @return Collection<int, Vente>
     */
    public function getVentes(): Collection
    {
        if (!$this->ventes instanceof Collection) {
            $this->ventes = new ArrayCollection();
        }
        return $this->ventes;
    }

    public function addVente(Vente $vente): self
    {
        if (!$this->getVentes()->contains($vente)) {
            $this->getVentes()->add($vente);
        }
        return $this;
    }

    public function removeVente(Vente $vente): self
    {
        $this->getVentes()->removeElement($vente);
        return $this;
    }

    public function isProfileComplete(): ?bool
    {
        return $this->profile_complete;
    }

    public function setProfileComplete(?bool $profile_complete): static
    {
        $this->profile_complete = $profile_complete;

        return $this;
    }

}
