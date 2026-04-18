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

    #[ORM\Column(type: "string", length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: "string", length: 64, nullable: true)]
    private ?string $confirm_token = null;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
    private int $nbr_presents = 0;

    #[ORM\Column(type: "integer", options: ["default" => 0])]
    private int $used_coins = 0;

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

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getConfirmToken(): ?string { return $this->confirm_token; }
    public function setConfirmToken(?string $token): static { $this->confirm_token = $token; return $this; }

    public function getNbr_presents(): int { return $this->nbr_presents; }
    public function setNbr_presents(int $nbr_presents): self { $this->nbr_presents = $nbr_presents; return $this; }
    public function getNbrPresents(): int { return $this->nbr_presents; }
    public function setNbrPresents(int $nbr_presents): self { $this->nbr_presents = $nbr_presents; return $this; }

    public function getUsedCoins(): int { return $this->used_coins; }
    public function setUsedCoins(int $used_coins): self { $this->used_coins = $used_coins; return $this; }

    /**
     * Get presence per day as an associative array: [dayIndex => count]
     */
    public function getPresenceData(): array
    {
        if (!$this->confirm_token) {
            return [1 => $this->nbr_presents];
        }
        
        // Check if it's our serialized format (contains '|') or a standard token
        if (strpos($this->confirm_token, '|') !== false || is_numeric($this->confirm_token)) {
            $parts = explode('|', $this->confirm_token);
            $data = [];
            foreach ($parts as $idx => $val) {
                $data[$idx + 1] = (int)$val;
            }
            return $data;
        }

        // It's a real token (registration still pending), so no presence data yet
        return [1 => $this->nbr_presents];
    }

    public function setPresenceData(int $day, int $count): self
    {
        $data = $this->getPresenceData();
        $data[$day] = $count;
        
        // Keep Day 1 in nbr_presents for backward compatibility/main display
        if ($day === 1) {
            $this->nbr_presents = $count;
        }

        // Serialize to pipe-separated string
        // We need to ensure we have all days from 1 to max(day)
        $maxDay = max(array_keys($data));
        $parts = [];
        for ($i = 1; $i <= $maxDay; $i++) {
            $parts[] = $data[$i] ?? 0;
        }
        
        $this->confirm_token = implode('|', $parts);
        
        return $this;
    }

    /**
     * Get the presence for the last day recorded (highest day index)
     */
    public function getLastDayPresence(): array
    {
        $data = $this->getPresenceData();
        if (empty($data)) {
            return ['day' => 1, 'count' => 0];
        }
        
        $lastDay = max(array_keys($data));
        return ['day' => $lastDay, 'count' => $data[$lastDay]];
    }
}
