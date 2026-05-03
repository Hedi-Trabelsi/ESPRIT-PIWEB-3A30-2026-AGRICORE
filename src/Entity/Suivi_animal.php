<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'suivi_animal_legacy')]
class Suivi_animal
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idSuivi;

    #[ORM\Column(name: 'idAnimal', type: 'integer')]
    private int $idAnimal = 0;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $dateSuivi;

    #[ORM\Column(type: "float")]
    private float $temperature;

    #[ORM\Column(type: "float")]
    private float $poids;

    #[ORM\Column(type: "integer")]
    private int $rythmeCardiaque;

    #[ORM\Column(type: "string", length: 50)]
    private string $niveauActitive;

    #[ORM\Column(type: "string", length: 50)]
    private string $etatSante;

    #[ORM\Column(type: "text")]
    private string $remarque;

    #[ORM\Column(type: "string", length: 50)]
    private string $niveauActivite;

    public function getIdSuivi(): int
    {
        return $this->idSuivi;
    }

    public function setIdSuivi(int $value): void
    {
        $this->idSuivi = $value;
    }

    public function getIdAnimal(): int
    {
        return $this->idAnimal;
    }

    public function setIdAnimal(int $value): void
    {
        $this->idAnimal = $value;
    }

    public function getDateSuivi(): \DateTimeInterface
    {
        return $this->dateSuivi;
    }

    public function setDateSuivi(\DateTimeInterface $value): void
    {
        $this->dateSuivi = $value;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function setTemperature(float $value): void
    {
        $this->temperature = $value;
    }

    public function getPoids(): float
    {
        return $this->poids;
    }

    public function setPoids(float $value): void
    {
        $this->poids = $value;
    }

    public function getRythmeCardiaque(): int
    {
        return $this->rythmeCardiaque;
    }

    public function setRythmeCardiaque(int $value): void
    {
        $this->rythmeCardiaque = $value;
    }

    public function getNiveauActitive(): string
    {
        return $this->niveauActitive;
    }

    public function setNiveauActitive(string $value): void
    {
        $this->niveauActitive = $value;
    }

    public function getEtatSante(): string
    {
        return $this->etatSante;
    }

    public function setEtatSante(string $value): void
    {
        $this->etatSante = $value;
    }

    public function getRemarque(): string
    {
        return $this->remarque;
    }

    public function setRemarque(string $value): void
    {
        $this->remarque = $value;
    }

    public function getNiveauActivite(): string
    {
        return $this->niveauActivite;
    }

    public function setNiveauActivite(string $value): void
    {
        $this->niveauActivite = $value;
    }
}
