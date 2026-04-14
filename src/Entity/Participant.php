<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ParticipantRepository;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
#[ORM\Table(name: 'participant')]
class Participant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_participant = null;

    public function getId_participant(): ?int
    {
        return $this->id_participant;
    }

    public function setId_participant(int $id_participant): self
    {
        $this->id_participant = $id_participant;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_utilisateur = null;

    public function getId_utilisateur(): ?int
    {
        return $this->id_utilisateur;
    }

    public function setId_utilisateur(int $id_utilisateur): self
    {
        $this->id_utilisateur = $id_utilisateur;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $id_ev = null;

    public function getId_ev(): ?int
    {
        return $this->id_ev;
    }

    public function setId_ev(int $id_ev): self
    {
        $this->id_ev = $id_ev;
        return $this;
    }

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date_inscription = null;

    public function getDate_inscription(): ?\DateTimeInterface
    {
        return $this->date_inscription;
    }

    public function setDate_inscription(\DateTimeInterface $date_inscription): self
    {
        $this->date_inscription = $date_inscription;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $statut_participation = null;

    public function getStatut_participation(): ?string
    {
        return $this->statut_participation;
    }

    public function setStatut_participation(string $statut_participation): self
    {
        $this->statut_participation = $statut_participation;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $montant_payee = null;

    public function getMontant_payee(): ?string
    {
        return $this->montant_payee;
    }

    public function setMontant_payee(string $montant_payee): self
    {
        $this->montant_payee = $montant_payee;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $confirmation = null;

    public function getConfirmation(): ?string
    {
        return $this->confirmation;
    }

    public function setConfirmation(string $confirmation): self
    {
        $this->confirmation = $confirmation;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $nbr_places = null;

    public function getNbr_places(): ?int
    {
        return $this->nbr_places;
    }

    public function setNbr_places(int $nbr_places): self
    {
        $this->nbr_places = $nbr_places;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $nom_participant = null;

    public function getNom_participant(): ?string
    {
        return $this->nom_participant;
    }

    public function setNom_participant(string $nom_participant): self
    {
        $this->nom_participant = $nom_participant;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $entry_code = null;

    public function getEntry_code(): ?int
    {
        return $this->entry_code;
    }

    public function setEntry_code(int $entry_code): self
    {
        $this->entry_code = $entry_code;
        return $this;
    }

    public function getIdParticipant(): ?int
    {
        return $this->id_participant;
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->id_utilisateur;
    }

    public function setIdUtilisateur(int $id_utilisateur): static
    {
        $this->id_utilisateur = $id_utilisateur;

        return $this;
    }

    public function getIdEv(): ?int
    {
        return $this->id_ev;
    }

    public function setIdEv(int $id_ev): static
    {
        $this->id_ev = $id_ev;

        return $this;
    }

    public function getDateInscription(): ?\DateTime
    {
        return $this->date_inscription;
    }

    public function setDateInscription(\DateTime $date_inscription): static
    {
        $this->date_inscription = $date_inscription;

        return $this;
    }

    public function getStatutParticipation(): ?string
    {
        return $this->statut_participation;
    }

    public function setStatutParticipation(string $statut_participation): static
    {
        $this->statut_participation = $statut_participation;

        return $this;
    }

    public function getMontantPayee(): ?string
    {
        return $this->montant_payee;
    }

    public function setMontantPayee(string $montant_payee): static
    {
        $this->montant_payee = $montant_payee;

        return $this;
    }

    public function getNbrPlaces(): ?int
    {
        return $this->nbr_places;
    }

    public function setNbrPlaces(int $nbr_places): static
    {
        $this->nbr_places = $nbr_places;

        return $this;
    }

    public function getNomParticipant(): ?string
    {
        return $this->nom_participant;
    }

    public function setNomParticipant(string $nom_participant): static
    {
        $this->nom_participant = $nom_participant;

        return $this;
    }

    public function getEntryCode(): ?int
    {
        return $this->entry_code;
    }

    public function setEntryCode(int $entry_code): static
    {
        $this->entry_code = $entry_code;

        return $this;
    }

}
