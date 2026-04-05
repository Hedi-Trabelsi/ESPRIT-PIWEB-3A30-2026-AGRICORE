<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use App\Entity\Animal;

#[ORM\Entity]
#[ORM\Table(name: 'suivi_animal_legacy')]
class Suivi_animal
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $idSuivi;

        #[ORM\ManyToOne(targetEntity: Animal::class, inversedBy: "suivi_animals")]
    #[ORM\JoinColumn(name: 'idAnimal', referencedColumnName: 'idAnimal', onDelete: 'CASCADE')]
    private Animal $idAnimal;

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

    public function getIdSuivi()
    {
        return $this->idSuivi;
    }

    public function setIdSuivi($value)
    {
        $this->idSuivi = $value;
    }

    public function getIdAnimal()
    {
        return $this->idAnimal;
    }

    public function setIdAnimal($value)
    {
        $this->idAnimal = $value;
    }

    public function getDateSuivi()
    {
        return $this->dateSuivi;
    }

    public function setDateSuivi($value)
    {
        $this->dateSuivi = $value;
    }

    public function getTemperature()
    {
        return $this->temperature;
    }

    public function setTemperature($value)
    {
        $this->temperature = $value;
    }

    public function getPoids()
    {
        return $this->poids;
    }

    public function setPoids($value)
    {
        $this->poids = $value;
    }

    public function getRythmeCardiaque()
    {
        return $this->rythmeCardiaque;
    }

    public function setRythmeCardiaque($value)
    {
        $this->rythmeCardiaque = $value;
    }

    public function getNiveauActitive()
    {
        return $this->niveauActitive;
    }

    public function setNiveauActitive($value)
    {
        $this->niveauActitive = $value;
    }

    public function getEtatSante()
    {
        return $this->etatSante;
    }

    public function setEtatSante($value)
    {
        $this->etatSante = $value;
    }

    public function getRemarque()
    {
        return $this->remarque;
    }

    public function setRemarque($value)
    {
        $this->remarque = $value;
    }

    public function getNiveauActivite()
    {
        return $this->niveauActivite;
    }

    public function setNiveauActivite($value)
    {
        $this->niveauActivite = $value;
    }
}
