<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\MaintenanceRepository;

#[ORM\Entity(repositoryClass: MaintenanceRepository::class)]
#[ORM\Table(name: 'maintenance')]
class Maintenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
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
    private ?string $type = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_declaration = null;

    public function getDate_declaration(): ?\DateTimeInterface
    {
        return $this->date_declaration;
    }

    public function setDate_declaration(\DateTimeInterface $date_declaration): self
    {
        $this->date_declaration = $date_declaration;
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
    private ?string $statut = null;

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $priorite = null;

    public function getPriorite(): ?string
    {
        return $this->priorite;
    }

    public function setPriorite(string $priorite): self
    {
        $this->priorite = $priorite;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $lieu = null;

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): self
    {
        $this->lieu = $lieu;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $equipement = null;

    public function getEquipement(): ?string
    {
        return $this->equipement;
    }

    public function setEquipement(string $equipement): self
    {
        $this->equipement = $equipement;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom_maintenance = null;

    public function getNom_maintenance(): ?string
    {
        return $this->nom_maintenance;
    }

    public function setNom_maintenance(string $nom_maintenance): self
    {
        $this->nom_maintenance = $nom_maintenance;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'maintenances')]
    #[ORM\JoinColumn(name: 'id_agriculteur', referencedColumnName: 'id')]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getIdMaintenance(): ?int
    {
        return $this->id_maintenance;
    }

    public function getDateDeclaration(): ?\DateTime
    {
        return $this->date_declaration;
    }

    public function setDateDeclaration(\DateTime $date_declaration): static
    {
        $this->date_declaration = $date_declaration;

        return $this;
    }

    public function getNomMaintenance(): ?string
    {
        return $this->nom_maintenance;
    }

    public function setNomMaintenance(string $nom_maintenance): static
    {
        $this->nom_maintenance = $nom_maintenance;

        return $this;
    }

}
