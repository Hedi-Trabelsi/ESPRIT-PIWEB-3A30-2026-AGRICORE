<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\AnimalRepository;

#[ORM\Entity(repositoryClass: AnimalRepository::class)]
#[ORM\Table(name: 'animal')]
class Animal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idAnimal = null;

    public function getIdAnimal(): ?int
    {
        return $this->idAnimal;
    }

    public function setIdAnimal(int $idAnimal): self
    {
        $this->idAnimal = $idAnimal;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $idAgriculteur = null;

    public function getIdAgriculteur(): ?int
    {
        return $this->idAgriculteur;
    }

    public function setIdAgriculteur(int $idAgriculteur): self
    {
        $this->idAgriculteur = $idAgriculteur;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $codeAnimal = null;

    public function getCodeAnimal(): ?string
    {
        return $this->codeAnimal;
    }

    public function setCodeAnimal(string $codeAnimal): self
    {
        $this->codeAnimal = $codeAnimal;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $espece = null;

    public function getEspece(): ?string
    {
        return $this->espece;
    }

    public function setEspece(string $espece): self
    {
        $this->espece = $espece;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $race = null;

    public function getRace(): ?string
    {
        return $this->race;
    }

    public function setRace(string $race): self
    {
        $this->race = $race;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $sexe = null;

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(string $sexe): self
    {
        $this->sexe = $sexe;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $dateNaissance = null;

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: SuiviAnimal::class, mappedBy: 'animal')]
    private Collection $suiviAnimals;

    public function __construct()
    {
        $this->suiviAnimals = new ArrayCollection();
    }

    /**
     * @return Collection<int, SuiviAnimal>
     */
    public function getSuiviAnimals(): Collection
    {
        if (!$this->suiviAnimals instanceof Collection) {
            $this->suiviAnimals = new ArrayCollection();
        }
        return $this->suiviAnimals;
    }

    public function addSuiviAnimal(SuiviAnimal $suiviAnimal): self
    {
        if (!$this->getSuiviAnimals()->contains($suiviAnimal)) {
            $this->getSuiviAnimals()->add($suiviAnimal);
        }
        return $this;
    }

    public function removeSuiviAnimal(SuiviAnimal $suiviAnimal): self
    {
        $this->getSuiviAnimals()->removeElement($suiviAnimal);
        return $this;
    }

}
