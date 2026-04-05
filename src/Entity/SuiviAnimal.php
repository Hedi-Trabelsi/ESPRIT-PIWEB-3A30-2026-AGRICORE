<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\SuiviAnimalRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Animal;

#[ORM\Entity(repositoryClass: SuiviAnimalRepository::class)]
class SuiviAnimal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "idSuivi")]
    private ?int $idSuivi = null;

    #[ORM\ManyToOne(targetEntity: Animal::class, inversedBy: "suivis", fetch: "EAGER")]
    #[ORM\JoinColumn(name: "idAnimal", referencedColumnName: "idAnimal", nullable: true, onDelete: "SET NULL")]
    #[Assert\NotNull(message: "L'animal est obligatoire")]
    private ?Animal $animal = null;

    #[ORM\Column(name: "dateSuivi", type: "datetime")]
    #[Assert\NotNull(message: "La date du suivi est obligatoire")]
    #[Assert\LessThanOrEqual("today", message: "La date du suivi ne peut pas être dans le futur")]
    private ?\DateTimeInterface $dateSuivi = null;

    #[ORM\Column(name: "temperature")]
    #[Assert\NotNull(message: "La température est obligatoire")]
    #[Assert\Range(min: 30, max: 45, notInRangeMessage: "La température doit être entre {{ min }}°C et {{ max }}°C")]
    private ?float $temperature = null;

    #[ORM\Column(name: "poids")]
    #[Assert\NotNull(message: "Le poids est obligatoire")]
    #[Assert\Positive(message: "Le poids doit être un nombre positif")]
    private ?float $poids = null;

    #[ORM\Column(name: "rythmeCardiaque")]
    #[Assert\NotNull(message: "Le rythme cardiaque est obligatoire")]
    #[Assert\Range(min: 20, max: 300, notInRangeMessage: "Le rythme cardiaque doit être entre {{ min }} et {{ max }} bpm")]
    private ?int $rythmeCardiaque = null;

    #[ORM\Column(name: "niveauActivite", length: 50)]
    #[Assert\NotBlank(message: "Le niveau d'activité est obligatoire")]
    #[Assert\Choice(choices: ["Faible", "Modéré", "Élevé"], message: "Le niveau d'activité doit être 'Faible', 'Modéré' ou 'Élevé'")]
    private ?string $niveauActivite = null;

    #[ORM\Column(name: "etatSante", length: 50)]
    #[Assert\NotBlank(message: "L'état de santé est obligatoire")]
    #[Assert\Choice(choices: ["Bon", "Moyen", "Mauvais"], message: "L'état de santé doit être 'Bon', 'Moyen' ou 'Mauvais'")]
    private ?string $etatSante = null;

    #[ORM\Column(name: "remarque", type: "text")]
    #[Assert\NotBlank(message: "La remarque est obligatoire")]
    #[Assert\Length(min: 5, minMessage: "La remarque doit contenir au moins {{ limit }} caractères")]
    private ?string $remarque = null;

    // ===== GETTERS / SETTERS =====

    public function getIdSuivi(): ?int { return $this->idSuivi; }

    public function getAnimal(): ?Animal
    {
        return $this->animal;
    }

    public function setAnimal(?Animal $animal): self
    {
        $this->animal = $animal;
        return $this;
    }

    public function getDateSuivi(): ?\DateTimeInterface { return $this->dateSuivi; }
    public function setDateSuivi(\DateTimeInterface $dateSuivi): self { $this->dateSuivi = $dateSuivi; return $this; }

    public function getTemperature(): ?float { return $this->temperature; }
    public function setTemperature(float $temperature): self { $this->temperature = $temperature; return $this; }

    public function getPoids(): ?float { return $this->poids; }
    public function setPoids(float $poids): self { $this->poids = $poids; return $this; }

    public function getRythmeCardiaque(): ?int { return $this->rythmeCardiaque; }
    public function setRythmeCardiaque(int $rythmeCardiaque): self { $this->rythmeCardiaque = $rythmeCardiaque; return $this; }

    public function getNiveauActivite(): ?string { return $this->niveauActivite; }
    public function setNiveauActivite(?string $niveauActivite): self { $this->niveauActivite = $niveauActivite; return $this; }

    public function getEtatSante(): ?string { return $this->etatSante; }
    public function setEtatSante(?string $etatSante): self { $this->etatSante = $etatSante; return $this; }

    public function getRemarque(): ?string { return $this->remarque; }
    public function setRemarque(?string $remarque): self { $this->remarque = $remarque; return $this; }
}