<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\AnimalRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\SuiviAnimal;


#[ORM\Entity(repositoryClass: AnimalRepository::class)]
class Animal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "idAnimal")]
    private ?int $idAnimal = null;

    #[ORM\Column(name: "idAgriculteur", nullable: true)]
    private ?int $idAgriculteur = null;

    #[ORM\Column(name: "codeAnimal", length: 50)]
    #[Assert\NotBlank(message: "Le code animal est obligatoire")]
    #[Assert\Length(max: 50, maxMessage: "Le code animal ne peut pas dépasser {{ limit }} caractères")]
    private ?string $codeAnimal = null;

    #[ORM\Column(name: "espece", length: 50)]
    #[Assert\NotBlank(message: "L'espèce est obligatoire")]
    #[Assert\Length(max: 50, maxMessage: "L'espèce ne peut pas dépasser {{ limit }} caractères")]
    private ?string $espece = null;

    #[ORM\Column(name: "race", length: 50)]
    #[Assert\NotBlank(message: "La race est obligatoire")]
    #[Assert\Length(max: 50, maxMessage: "La race ne peut pas dépasser {{ limit }} caractères")]
    private ?string $race = null;

    #[ORM\Column(name: "sexe", length: 50)]
    #[Assert\NotBlank(message: "Le sexe est obligatoire")]
    #[Assert\Choice(choices: ["Mâle", "Femelle"], message: "Le sexe doit être 'Mâle' ou 'Femelle'")]
    private ?string $sexe = null;

    #[ORM\Column(name: "dateNaissance", type: "date")]
    #[Assert\NotNull(message: "La date de naissance est obligatoire")]
    #[Assert\LessThanOrEqual("today", message: "La date de naissance ne peut pas être dans le futur")]
    private ?\DateTimeInterface $dateNaissance = null;
    #[ORM\OneToMany(mappedBy: "animal", targetEntity: SuiviAnimal::class)]
    private Collection $suivis;
    // ===== ID =====
    public function getIdAnimal(): ?int
    {
        return $this->idAnimal;
    }

    // ===== AGRICULTEUR =====
    public function getIdAgriculteur(): ?int
    {
        return $this->idAgriculteur;
    }

    public function setIdAgriculteur(int $idAgriculteur): self
    {
        $this->idAgriculteur = $idAgriculteur;
        return $this;
    }

    // ===== CODE =====
    public function getCodeAnimal(): ?string
    {
        return $this->codeAnimal;
    }

    public function setCodeAnimal(string $codeAnimal): self
    {
        $this->codeAnimal = $codeAnimal;
        return $this;
    }

    // ===== ESPECE =====
    public function getEspece(): ?string
    {
        return $this->espece;
    }

    public function setEspece(string $espece): self
    {
        $this->espece = $espece;
        return $this;
    }

    // ===== RACE =====
    public function getRace(): ?string
    {
        return $this->race;
    }

    public function setRace(string $race): self
    {
        $this->race = $race;
        return $this;
    }

    // ===== SEXE =====
    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(string $sexe): self
    {
        $this->sexe = $sexe;
        return $this;
    }

    // ===== DATE =====
    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }
    public function getSuivis(): Collection
    {
        return $this->suivis;
    }
    public function __construct()
    {
        $this->suivis = new ArrayCollection();
    }
}