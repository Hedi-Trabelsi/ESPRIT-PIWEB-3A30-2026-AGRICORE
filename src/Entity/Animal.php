<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use App\Entity\Suivi_animal;

#[ORM\Entity]
class Animal
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idAnimal;

        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "animals")]
    #[ORM\JoinColumn(name: 'idAgriculteur', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $idAgriculteur;

    #[ORM\Column(type: "string", length: 50)]
    private string $codeAnimal;

    #[ORM\Column(type: "string", length: 50)]
    private string $espece;

    #[ORM\Column(type: "string", length: 50)]
    private string $race;

    #[ORM\Column(type: "string", length: 50)]
    private string $sexe;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $dateNaissance;

    public function getIdAnimal()
    {
        return $this->idAnimal;
    }

    public function setIdAnimal($value)
    {
        $this->idAnimal = $value;
    }

    public function getIdAgriculteur()
    {
        return $this->idAgriculteur;
    }

    public function setIdAgriculteur($value)
    {
        $this->idAgriculteur = $value;
    }

    public function getCodeAnimal()
    {
        return $this->codeAnimal;
    }

    public function setCodeAnimal($value)
    {
        $this->codeAnimal = $value;
    }

    public function getEspece()
    {
        return $this->espece;
    }

    public function setEspece($value)
    {
        $this->espece = $value;
    }

    public function getRace()
    {
        return $this->race;
    }

    public function setRace($value)
    {
        $this->race = $value;
    }

    public function getSexe()
    {
        return $this->sexe;
    }

    public function setSexe($value)
    {
        $this->sexe = $value;
    }

    public function getDateNaissance()
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance($value)
    {
        $this->dateNaissance = $value;
    }

    #[ORM\OneToMany(mappedBy: "idAnimal", targetEntity: Suivi_animal::class)]
    private Collection $suivi_animals;

        public function getSuivi_animals(): Collection
        {
            return $this->suivi_animals;
        }
    
        public function addSuivi_animal(Suivi_animal $suivi_animal): self
        {
            if (!$this->suivi_animals->contains($suivi_animal)) {
                $this->suivi_animals[] = $suivi_animal;
                $suivi_animal->setIdAnimal($this);
            }
    
            return $this;
        }
    
        public function removeSuivi_animal(Suivi_animal $suivi_animal): self
        {
            if ($this->suivi_animals->removeElement($suivi_animal)) {
                // set the owning side to null (unless already changed)
                if ($suivi_animal->getIdAnimal() === $this) {
                    $suivi_animal->setIdAnimal(null);
                }
            }
    
            return $this;
        }
}
