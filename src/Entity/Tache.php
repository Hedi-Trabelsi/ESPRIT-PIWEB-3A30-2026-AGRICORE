<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TacheRepository;

#[ORM\Entity(repositoryClass: TacheRepository::class)]
#[ORM\Table(name: 'tache')]
class Tache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_tache = null;

    public function getId_tache(): ?int
    {
        return $this->id_tache;
    }

    public function setId_tache(int $id_tache): self
    {
        $this->id_tache = $id_tache;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_prevue = null;

    public function getDate_prevue(): ?\DateTimeInterface
    {
        return $this->date_prevue;
    }

    public function setDate_prevue(\DateTimeInterface $date_prevue): self
    {
        $this->date_prevue = $date_prevue;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $cout_estimee = null;

    public function getCout_estimee(): ?string
    {
        return $this->cout_estimee;
    }

    public function setCout_estimee(string $cout_estimee): self
    {
        $this->cout_estimee = $cout_estimee;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_maintenance = null;

    public function getId_maintenance(): ?int
    {
        return $this->id_maintenance;
    }

    public function setId_maintenance(int $id_maintenance): self
    {
        $this->id_maintenance = $id_maintenance;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nomTache = null;

    public function getNomTache(): ?string
    {
        return $this->nomTache;
    }

    public function setNomTache(string $nomTache): self
    {
        $this->nomTache = $nomTache;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $evaluation = null;

    public function getEvaluation(): ?int
    {
        return $this->evaluation;
    }

    public function setEvaluation(int $evaluation): self
    {
        $this->evaluation = $evaluation;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id_technicien = null;

    public function getId_technicien(): ?int
    {
        return $this->id_technicien;
    }

    public function setId_technicien(?int $id_technicien): self
    {
        $this->id_technicien = $id_technicien;
        return $this;
    }

    public function getIdTache(): ?int
    {
        return $this->id_tache;
    }

    public function getDatePrevue(): ?\DateTime
    {
        return $this->date_prevue;
    }

    public function setDatePrevue(\DateTime $date_prevue): static
    {
        $this->date_prevue = $date_prevue;

        return $this;
    }

    public function getCoutEstimee(): ?string
    {
        return $this->cout_estimee;
    }

    public function setCoutEstimee(string $cout_estimee): static
    {
        $this->cout_estimee = $cout_estimee;

        return $this;
    }

    public function getIdMaintenance(): ?int
    {
        return $this->id_maintenance;
    }

    public function setIdMaintenance(int $id_maintenance): static
    {
        $this->id_maintenance = $id_maintenance;

        return $this;
    }

    public function getIdTechnicien(): ?int
    {
        return $this->id_technicien;
    }

    public function setIdTechnicien(?int $id_technicien): static
    {
        $this->id_technicien = $id_technicien;

        return $this;
    }

}
