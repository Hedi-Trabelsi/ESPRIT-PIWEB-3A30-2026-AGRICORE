<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\User;

#[ORM\Entity]
class Tache
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_tache;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_prevue;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "string", length: 25)]
    private string $cout_estimee;

        #[ORM\ManyToOne(targetEntity: Maintenance::class, inversedBy: "taches")]
    #[ORM\JoinColumn(name: 'id_maintenance', referencedColumnName: 'id_maintenance', onDelete: 'CASCADE')]
    private Maintenance $id_maintenance;

    #[ORM\Column(type: "string", length: 50)]
    private string $nomTache;

    #[ORM\Column(type: "integer")]
    private int $evaluation;

        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "taches")]
    #[ORM\JoinColumn(name: 'id_technicien', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $id_technicien;

    public function getId_tache()
    {
        return $this->id_tache;
    }

    public function setId_tache($value)
    {
        $this->id_tache = $value;
    }

    public function getDate_prevue()
    {
        return $this->date_prevue;
    }

    public function setDate_prevue($value)
    {
        $this->date_prevue = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getCout_estimee()
    {
        return $this->cout_estimee;
    }

    public function setCout_estimee($value)
    {
        $this->cout_estimee = $value;
    }

    public function getId_maintenance()
    {
        return $this->id_maintenance;
    }

    public function setId_maintenance($value)
    {
        $this->id_maintenance = $value;
    }

    public function getNomTache()
    {
        return $this->nomTache;
    }

    public function setNomTache($value)
    {
        $this->nomTache = $value;
    }

    public function getEvaluation()
    {
        return $this->evaluation;
    }

    public function setEvaluation($value)
    {
        $this->evaluation = $value;
    }

    public function getId_technicien()
    {
        return $this->id_technicien;
    }

    public function setId_technicien($value)
    {
        $this->id_technicien = $value;
    }
}
