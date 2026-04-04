<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Maintenance;

#[ORM\Entity]
#[ORM\Table(name: "tache")] // On s'assure que la table s'appelle bien tache
class Tache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_tache", type: "integer")]
    private int $id_tache;

    // Ajout de name: "date_prevue" pour être sûr
    #[ORM\Column(name: "date_prevue", type: "date")]
    private ?\DateTimeInterface $date_prevue = null;

    #[ORM\Column(type: "text")]
    private string $description;

    // Ajout de name: "cout_estimee"
    #[ORM\Column(name: "cout_estimee", type: "string", length: 25)]
    private string $cout_estimee;

    #[ORM\ManyToOne(targetEntity: Maintenance::class, inversedBy: "taches")]
    #[ORM\JoinColumn(name: 'id_maintenance', referencedColumnName: 'id_maintenance', onDelete: 'CASCADE')]
    private Maintenance $id_maintenance;

    // FIX ICI : On force le nom exact de la colonne SQL
    #[ORM\Column(name: "nomTache", type: "string", length: 50)]
    private string $nomTache;

    #[ORM\Column(type: "integer")]
    private int $evaluation;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "taches")]
    #[ORM\JoinColumn(name: 'id_technicien', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $id_technicien;

    // --- GETTERS & SETTERS ---

    public function getId_tache(): int
    {
        return $this->id_tache;
    }

    public function getDatePrevue(): ?\DateTimeInterface
    {
        return $this->date_prevue;
    }

    public function setDatePrevue(?\DateTimeInterface $date_prevue): self
    {
        $this->date_prevue = $date_prevue;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $value): self
    {
        $this->description = $value;
        return $this;
    }

    public function getCoutEstimee(): ?string
    {
        return $this->cout_estimee;
    }

    public function setCoutEstimee(string $cout_estimee): self
    {
        $this->cout_estimee = $cout_estimee;
        return $this;
    }

    public function getIdMaintenance(): ?Maintenance
    {
        return $this->id_maintenance;
    }

    public function setIdMaintenance(?Maintenance $id_maintenance): self
    {
        $this->id_maintenance = $id_maintenance;
        return $this;
    }

    public function getNomTache(): string
    {
        return $this->nomTache;
    }

    public function setNomTache(string $value): self
    {
        $this->nomTache = $value;
        return $this;
    }

    public function getEvaluation(): int
    {
        return $this->evaluation;
    }

    public function setEvaluation(int $value): self
    {
        $this->evaluation = $value;
        return $this;
    }

    public function getIdTechnicien(): ?User
    {
        return $this->id_technicien;
    }

    public function setIdTechnicien(?User $id_technicien): self
    {
        $this->id_technicien = $id_technicien;
        return $this;
    }
}