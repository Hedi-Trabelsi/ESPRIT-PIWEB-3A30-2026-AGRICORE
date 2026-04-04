<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Evennementagricole
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_ev;

    #[ORM\Column(type: "string", length: 25)]
    private string $titre;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_debut;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $date_fin;

    #[ORM\Column(type: "string", length: 25)]
    private string $lieu;

    #[ORM\Column(type: "integer")]
    private int $capacite_max;

    #[ORM\Column(type: "integer")]
    private int $frais_inscription;

    #[ORM\Column(type: "string", length: 25)]
    private string $statut;

    public function getId_ev()
    {
        return $this->id_ev;
    }

    public function setId_ev($value)
    {
        $this->id_ev = $value;
    }

    public function getTitre()
    {
        return $this->titre;
    }

    public function setTitre($value)
    {
        $this->titre = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getDate_debut()
    {
        return $this->date_debut;
    }

    public function setDate_debut($value)
    {
        $this->date_debut = $value;
    }

    public function getDate_fin()
    {
        return $this->date_fin;
    }

    public function setDate_fin($value)
    {
        $this->date_fin = $value;
    }

    public function getLieu()
    {
        return $this->lieu;
    }

    public function setLieu($value)
    {
        $this->lieu = $value;
    }

    public function getCapacite_max()
    {
        return $this->capacite_max;
    }

    public function setCapacite_max($value)
    {
        $this->capacite_max = $value;
    }

    public function getFrais_inscription()
    {
        return $this->frais_inscription;
    }

    public function setFrais_inscription($value)
    {
        $this->frais_inscription = $value;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
    }
}
