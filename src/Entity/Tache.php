<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\Maintenance;
// Indispensable pour les contrôles de saisie
use Symfony\Component\Validator\Constraints as Assert;

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
    #[Assert\NotBlank(message: "La date prévue est obligatoire.")]
    #[Assert\GreaterThanOrEqual("today", message: "La date ne peut pas être antérieure à aujourd'hui.")]
    private ?\DateTimeInterface $date_prevue = null;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Regex(
        // Force au moins 7 caractères ET interdit d'avoir UNIQUEMENT des chiffres
        pattern: "/^(?![0-9]*$)[a-zA-Z0-9\s\.,!?]{7,}$/",
        message: "La description doit faire au moins 7 caractères et ne pas contenir que des chiffres."
    )]
    private ?string $description = null;

    // Ajout de name: "cout_estimee"
    #[ORM\Column(name: "cout_estimee", type: "string", length: 25)]
    #[Assert\NotBlank(message: "Le coût estimé est obligatoire.")]
    #[Assert\Regex(
        pattern: "/^[0-9]+(\.[0-9]{1,2})?$/",
        message: "Le coût estimé ne doit contenir que des chiffres et éventuellement un point pour les décimales."
    )]
    private string $cout_estimee;

    #[ORM\ManyToOne(targetEntity: Maintenance::class, inversedBy: "taches")]
    #[ORM\JoinColumn(name: 'id_maintenance', referencedColumnName: 'id_maintenance', onDelete: 'CASCADE')]
    private Maintenance $id_maintenance;

    // FIX ICI : On force le nom exact de la colonne SQL
    #[ORM\Column(name: "nomTache", type: "string", length: 50)]
    #[Assert\NotBlank(message: "Le nom de la tâche est obligatoire.")]
    #[Assert\Length(min: 3, minMessage: "Le nom de la tâche doit contenir au moins 3 caractères.")]
    #[Assert\Regex(
        pattern: "/^[a-zA-Z\s]+$/",
        message: "Le nom de la tâche ne doit contenir que des lettres."
    )]
    private ?string $nomTache = null;

    #[ORM\Column(type: "integer")]
    #[Assert\NotBlank(message: "L'évaluation est obligatoire.")]
    #[Assert\Range(min: 0, max: 10, notInRangeMessage: "L'évaluation doit être entre 0 et 10.")]
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

    public function setDescription(?string $value): self // <-- Ajoute le ? ici
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

   public function setNomTache(?string $value): self // <-- Ajoute le ? ici
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