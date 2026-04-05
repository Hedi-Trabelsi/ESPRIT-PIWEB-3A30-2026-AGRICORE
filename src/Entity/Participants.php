<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Participants
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_participant;

    #[ORM\Column(type: "integer")]
    private int $id_utilisateur;

    #[ORM\ManyToOne(targetEntity: Evennementagricole::class)]
    #[ORM\JoinColumn(name: 'id_ev', referencedColumnName: 'id_ev')]
    private ?Evennementagricole $evenement = null;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_inscription;

    #[ORM\Column(type: "string", length: 25)]
    private string $statut_participation;

    #[ORM\Column(type: "string", length: 25)]
    private string $montant_payee;

    #[ORM\Column(type: "string", length: 25)]
    private string $confirmation;

    #[ORM\Column(type: "integer")]
    private int $nbr_places;

    #[ORM\Column(type: "string", length: 55)]
    private string $nom_participant;

    #[ORM\Column(type: "integer")]
    private int $entry_code;

    public function getId_participant()
    {
        return $this->id_participant;
    }

    public function setId_participant($value)
    {
        $this->id_participant = $value;
    }

    public function getId_utilisateur()
    {
        return $this->id_utilisateur;
    }

    public function setId_utilisateur($value)
    {
        $this->id_utilisateur = $value;
    }

    public function getEvenement(): ?Evennementagricole
    {
        return $this->evenement;
    }

    public function setEvenement(?Evennementagricole $evenement): static
    {
        $this->evenement = $evenement;
        return $this;
    }

    public function getDate_inscription()
    {
        return $this->date_inscription;
    }

    public function setDate_inscription($value)
    {
        $this->date_inscription = $value;
    }

    public function getStatut_participation()
    {
        return $this->statut_participation;
    }

    public function setStatut_participation($value)
    {
        $this->statut_participation = $value;
    }

    public function getMontant_payee()
    {
        return $this->montant_payee;
    }

    public function setMontant_payee($value)
    {
        $this->montant_payee = $value;
    }

    public function getConfirmation()
    {
        return $this->confirmation;
    }

    public function setConfirmation($value)
    {
        $this->confirmation = $value;
    }

    public function getNbr_places()
    {
        return $this->nbr_places;
    }

    public function setNbr_places($value)
    {
        $this->nbr_places = $value;
    }

    public function getNom_participant()
    {
        return $this->nom_participant;
    }

    public function setNom_participant($value)
    {
        $this->nom_participant = $value;
    }

    public function getEntry_code()
    {
        return $this->entry_code;
    }

    public function setEntry_code($value)
    {
        $this->entry_code = $value;
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
        return $this->evenement?->getId_ev();
    }

    public function setIdEv(int $id_ev): static
    {
        // This setter should now set the evenement instead
        // Note: This assumes the evenement is already loaded
        // In practice, you should use setEvenement() instead
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
