<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Tache;

#[ORM\Entity]
class User
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 25)]
    private string $nom;

    #[ORM\Column(type: "string", length: 25)]
    private string $prenom;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date;

    #[ORM\Column(type: "string", length: 25)]
    private string $adresse;

    #[ORM\Column(type: "integer")]
    private int $role;

    #[ORM\Column(type: "integer", name: "numeroT")]
    private int $numeroT;

    #[ORM\Column(type: "string", length: 100)]
    private string $email;

    #[ORM\Column(type: "string")]
    private string $image;

    #[ORM\Column(type: "string", length: 255)]
    private string $password;

    #[ORM\Column(type: "string", length: 255)]
    private string $genre;

    #[ORM\Column(type: "boolean")]
    private bool $profile_complete;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
    }

    public function getPrenom()
    {
        return $this->prenom;
    }

    public function setPrenom($value)
    {
        $this->prenom = $value;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($value)
    {
        $this->date = $value;
    }

    public function getAdresse()
    {
        return $this->adresse;
    }

    public function setAdresse($value)
    {
        $this->adresse = $value;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($value)
    {
        $this->role = $value;
    }

    public function getNumeroT()
    {
        return $this->numeroT;
    }

    public function setNumeroT($value)
    {
        $this->numeroT = $value;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($value)
    {
        $this->email = $value;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($value)
    {
        $this->image = $value;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($value)
    {
        $this->password = $value;
    }

    public function getGenre()
    {
        return $this->genre;
    }

    public function setGenre($value)
    {
        $this->genre = $value;
    }

    public function getProfile_complete()
    {
        return $this->profile_complete;
    }

    public function setProfile_complete($value)
    {
        $this->profile_complete = $value;
    }

    #[ORM\OneToMany(mappedBy: "idAgriculteur", targetEntity: Animal::class)]
    private Collection $animals;

        public function getAnimals(): Collection
        {
            return $this->animals;
        }
    
        public function addAnimal(Animal $animal): self
        {
            if (!$this->animals->contains($animal)) {
                $this->animals[] = $animal;
                $animal->setIdAgriculteur($this);
            }
    
            return $this;
        }
    
        public function removeAnimal(Animal $animal): self
        {
            if ($this->animals->removeElement($animal)) {
                // set the owning side to null (unless already changed)
                if ($animal->getIdAgriculteur() === $this) {
                    $animal->setIdAgriculteur(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "userId", targetEntity: Depense::class)]
    private Collection $depenses;

        public function getDepenses(): Collection
        {
            return $this->depenses;
        }
    
        public function addDepense(Depense $depense): self
        {
            if (!$this->depenses->contains($depense)) {
                $this->depenses[] = $depense;
                $depense->setUserId($this);
            }
    
            return $this;
        }
    
        public function removeDepense(Depense $depense): self
        {
            if ($this->depenses->removeElement($depense)) {
                // set the owning side to null (unless already changed)
                if ($depense->getUserId() === $this) {
                    $depense->setUserId(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "id_fournisseur", targetEntity: Equipements::class)]
    private Collection $equipementss;

        public function getEquipementss(): Collection
        {
            return $this->equipementss;
        }
    
        public function addEquipements(Equipements $equipements): self
        {
            if (!$this->equipementss->contains($equipements)) {
                $this->equipementss[] = $equipements;
                $equipements->setId_fournisseur($this);
            }
    
            return $this;
        }
    
        public function removeEquipements(Equipements $equipements): self
        {
            if ($this->equipementss->removeElement($equipements)) {
                // set the owning side to null (unless already changed)
                if ($equipements->getId_fournisseur() === $this) {
                    $equipements->setId_fournisseur(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "id_agriculteur", targetEntity: Maintenance::class)]
    private Collection $maintenances;

        public function getMaintenances(): Collection
        {
            return $this->maintenances;
        }
    
        public function addMaintenance(Maintenance $maintenance): self
        {
            if (!$this->maintenances->contains($maintenance)) {
                $this->maintenances[] = $maintenance;
                $maintenance->setId_agriculteur($this);
            }
    
            return $this;
        }
    
        public function removeMaintenance(Maintenance $maintenance): self
        {
            if ($this->maintenances->removeElement($maintenance)) {
                // set the owning side to null (unless already changed)
                if ($maintenance->getId_agriculteur() === $this) {
                    $maintenance->setId_agriculteur(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "id_technicien", targetEntity: Tache::class)]
    private Collection $taches;

        public function getTaches(): Collection
        {
            return $this->taches;
        }
    
        public function addTache(Tache $tache): self
        {
            if (!$this->taches->contains($tache)) {
                $this->taches[] = $tache;
                $tache->setId_technicien($this);
            }
    
            return $this;
        }
    
        public function removeTache(Tache $tache): self
        {
            if ($this->taches->removeElement($tache)) {
                // set the owning side to null (unless already changed)
                if ($tache->getId_technicien() === $this) {
                    $tache->setId_technicien(null);
                }
            }
    
            return $this;
        }
}
