<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Participants
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_participant;

    
    #[ORM\Column(type: "integer")]
    private int $id_ev;

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

    public function getId_ev()
    {
        return $this->id_ev;
    }

    public function setId_ev($value)
    {
        $this->id_ev = $value;
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
}
