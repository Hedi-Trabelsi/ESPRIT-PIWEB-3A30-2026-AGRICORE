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

    public function getAge()
    {
        return $this->age;
    }

    public function setAge($value)
    {
        $this->age = $value;
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

    public function getNumero_tel()
    {
        return $this->numero_tel;
    }

    public function setNumero_tel($value)
    {
        $this->numero_tel = $value;
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

    #[ORM\OneToMany(mappedBy: "id_technicien", targetEntity: Maintenance::class)]
    private Collection $maintenances;

        public function getMaintenances(): Collection
        {
            return $this->maintenances;
        }
    
        public function addMaintenance(Maintenance $maintenance): self
        {
            if (!$this->maintenances->contains($maintenance)) {
                $this->maintenances[] = $maintenance;
                $maintenance->setId_technicien($this);
            }
    
            return $this;
        }
    
        public function removeMaintenance(Maintenance $maintenance): self
        {
            if ($this->maintenances->removeElement($maintenance)) {
                // set the owning side to null (unless already changed)
                if ($maintenance->getId_technicien() === $this) {
                    $maintenance->setId_technicien(null);
                }
            }
    
            return $this;
        }
}
