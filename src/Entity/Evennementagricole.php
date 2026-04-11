<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\EvennementagricoleRepository;

#[ORM\Entity(repositoryClass: EvennementagricoleRepository::class)]
#[ORM\Table(name: 'evennementagricole')]
class Evennementagricole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_ev = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_debut = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_fin = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $lieu = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $capacite_max = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $frais_inscription = null;

    public function getIdEv(): ?int { return $this->id_ev; }
    public function getId_ev(): ?int { return $this->id_ev; }
    public function setId_ev(int $id_ev): self { $this->id_ev = $id_ev; return $this; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $titre): self { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getDate_debut(): ?\DateTimeInterface { return $this->date_debut; }
    public function setDate_debut(?\DateTimeInterface $date_debut): self { $this->date_debut = $date_debut; return $this; }
    public function getDateDebut(): ?\DateTimeInterface { return $this->date_debut; }
    public function setDateDebut(?\DateTimeInterface $date_debut): self { $this->date_debut = $date_debut; return $this; }

    public function getDate_fin(): ?\DateTimeInterface { return $this->date_fin; }
    public function setDate_fin(?\DateTimeInterface $date_fin): self { $this->date_fin = $date_fin; return $this; }
    public function getDateFin(): ?\DateTimeInterface { return $this->date_fin; }
    public function setDateFin(?\DateTimeInterface $date_fin): self { $this->date_fin = $date_fin; return $this; }

    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(?string $lieu): self { $this->lieu = $lieu; return $this; }

    public function getCapacite_max(): ?int { return $this->capacite_max; }
    public function setCapacite_max(?int $capacite_max): self { $this->capacite_max = $capacite_max; return $this; }
    public function getCapaciteMax(): ?int { return $this->capacite_max; }
    public function setCapaciteMax(?int $capacite_max): self { $this->capacite_max = $capacite_max; return $this; }

    public function getFrais_inscription(): ?int { return $this->frais_inscription; }
    public function setFrais_inscription(?int $frais_inscription): self { $this->frais_inscription = $frais_inscription; return $this; }
    public function getFraisInscription(): ?int { return $this->frais_inscription; }
    public function setFraisInscription(?int $frais_inscription): self { $this->frais_inscription = $frais_inscription; return $this; }

    public function getStatut(): string
    {
        $now = new \DateTime();
        if ($this->date_fin && $this->date_fin < $now) {
            return 'HISTORIQUE';
        }
        if ($this->date_debut && $this->date_debut > $now) {
            return 'COMING';
        }
        return 'EN_COURS';
    }

    public function setStatut(string $statut): self { return $this; }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $image = null;

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): self { $this->image = $image; return $this; }
}
