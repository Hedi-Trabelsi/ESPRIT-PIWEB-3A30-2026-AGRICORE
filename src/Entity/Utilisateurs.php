<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Maintenance;

#[ORM\Entity]
class Utilisateurs
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 25)]
    private string $nom;

    #[ORM\Column(type: "string", length: 25)]
    private string $prenom;

    #[ORM\Column(type: "integer")]
    private int $age;

    #[ORM\Column(type: "string", length: 25)]
    private string $adresse;

    #[ORM\Column(type: "string", length: 25)]
    private string $role;

    #[ORM\Column(type: "integer")]
    private int $numero_tel;

    #[ORM\Column(type: "string", length: 25)]
    private string $email;

    #[ORM\Column(type: "string", length: 65535)]
    private string $image;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $value): void
    {
        $this->id = $value;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $value): void
    {
        $this->nom = $value;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $value): void
    {
        $this->prenom = $value;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $value): void
    {
        $this->age = $value;
    }

    public function getAdresse(): string
    {
        return $this->adresse;
    }

    public function setAdresse(string $value): void
    {
        $this->adresse = $value;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $value): void
    {
        $this->role = $value;
    }

    public function getNumero_tel(): int
    {
        return $this->numero_tel;
    }

    public function setNumero_tel(int $value): void
    {
        $this->numero_tel = $value;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $value): void
    {
        $this->email = $value;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $value): void
    {
        $this->image = $value;
    }

    /** @var Collection<int, Animal> */
    #[ORM\OneToMany(mappedBy: "idAgriculteur", targetEntity: Animal::class)]
    private Collection $animals;

    /**
     * @return Collection<int, Animal>
     */
    public function getAnimals(): Collection
    {
        return $this->animals;
    }

    public function addAnimal(Animal $animal): self
    {
        if (!$this->animals->contains($animal)) {
            $this->animals[] = $animal;
            $animal->setIdAgriculteur($this->getId());
        }

        return $this;
    }

    public function removeAnimal(Animal $animal): self
    {
        $this->animals->removeElement($animal);

        return $this;
    }

    /** @var Collection<int, Equipements> */
    #[ORM\OneToMany(mappedBy: "id_fournisseur", targetEntity: Equipements::class)]
    private Collection $equipementss;

    /**
     * @return Collection<int, Equipements>
     */
    public function getEquipementss(): Collection
    {
        return $this->equipementss;
    }

    public function addEquipements(Equipements $equipements): self
    {
        if (!$this->equipementss->contains($equipements)) {
            $this->equipementss[] = $equipements;
        }

        return $this;
    }

    public function removeEquipements(Equipements $equipements): self
    {
        $this->equipementss->removeElement($equipements);

        return $this;
    }

    /** @var Collection<int, Maintenance> */
    #[ORM\OneToMany(mappedBy: "id_technicien", targetEntity: Maintenance::class)]
    private Collection $maintenances;

    /**
     * @return Collection<int, Maintenance>
     */
    public function getMaintenances(): Collection
    {
        return $this->maintenances;
    }

    public function addMaintenance(Maintenance $maintenance): self
    {
        if (!$this->maintenances->contains($maintenance)) {
            $this->maintenances[] = $maintenance;
        }

        return $this;
    }

    public function removeMaintenance(Maintenance $maintenance): self
    {
        $this->maintenances->removeElement($maintenance);

        return $this;
    }
}
