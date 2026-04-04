<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\SuiviAnimalRepository;

#[ORM\Entity(repositoryClass: SuiviAnimalRepository::class)]
#[ORM\Table(name: 'suivi_animal')]
class SuiviAnimal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idSuivi = null;

    public function getIdSuivi(): ?int
    {
        return $this->idSuivi;
    }

    public function setIdSuivi(int $idSuivi): self
    {
        $this->idSuivi = $idSuivi;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Animal::class, inversedBy: 'suiviAnimals')]
    #[ORM\JoinColumn(name: 'idAnimal', referencedColumnName: 'idAnimal')]
    private ?Animal $animal = null;

    public function getAnimal(): ?Animal
    {
        return $this->animal;
    }

    public function setAnimal(?Animal $animal): self
    {
        $this->animal = $animal;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $dateSuivi = null;

    public function getDateSuivi(): ?\DateTimeInterface
    {
        return $this->dateSuivi;
    }

    public function setDateSuivi(\DateTimeInterface $dateSuivi): self
    {
        $this->dateSuivi = $dateSuivi;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $temperature = null;

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: false)]
    private ?float $poids = null;

    public function getPoids(): ?float
    {
        return $this->poids;
    }

    public function setPoids(float $poids): self
    {
        $this->poids = $poids;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $rythmeCardiaque = null;

    public function getRythmeCardiaque(): ?int
    {
        return $this->rythmeCardiaque;
    }

    public function setRythmeCardiaque(int $rythmeCardiaque): self
    {
        $this->rythmeCardiaque = $rythmeCardiaque;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $niveauActitive = null;

    public function getNiveauActitive(): ?string
    {
        return $this->niveauActitive;
    }

    public function setNiveauActitive(?string $niveauActitive): self
    {
        $this->niveauActitive = $niveauActitive;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $etatSante = null;

    public function getEtatSante(): ?string
    {
        return $this->etatSante;
    }

    public function setEtatSante(string $etatSante): self
    {
        $this->etatSante = $etatSante;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $remarque = null;

    public function getRemarque(): ?string
    {
        return $this->remarque;
    }

    public function setRemarque(string $remarque): self
    {
        $this->remarque = $remarque;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $niveauActivite = null;

    public function getNiveauActivite(): ?string
    {
        return $this->niveauActivite;
    }

    public function setNiveauActivite(string $niveauActivite): self
    {
        $this->niveauActivite = $niveauActivite;
        return $this;
    }

}
