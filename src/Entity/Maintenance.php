<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use App\Entity\Tache;

#[ORM\Entity]
class Maintenance
{
#[ORM\Id]
#[ORM\GeneratedValue]      // <-- Ajouté
#[ORM\Column(type: "integer")]
private int $id_maintenance;

    #[ORM\Column(type: "string", length: 25)]
    private string $type;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_declaration;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "string", length: 25)]
    private string $statut;

    #[ORM\Column(type: "string", length: 20)]
    private string $priorite;

    #[ORM\Column(type: "string", length: 150)]
    private string $lieu;

    #[ORM\Column(type: "string", length: 150)]
    private string $equipement;

    #[ORM\Column(type: "string", length: 50)]
    private string $nom_maintenance;

        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "maintenances")]
    #[ORM\JoinColumn(name: 'id_agriculteur', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private User $id_agriculteur;

    public function getId_maintenance()
    {
        return $this->id_maintenance;
    }

    public function setId_maintenance($value)
    {
        $this->id_maintenance = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
    }

  public function getDateDeclaration(): \DateTimeInterface
{
    return $this->date_declaration;
}

public function setDateDeclaration(\DateTimeInterface $value): self
{
    $this->date_declaration = $value;
    return $this;
}

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
    }

    public function getPriorite()
    {
        return $this->priorite;
    }

    public function setPriorite($value)
    {
        $this->priorite = $value;
    }

    public function getLieu()
    {
        return $this->lieu;
    }

    public function setLieu($value)
    {
        $this->lieu = $value;
    }

    public function getEquipement()
    {
        return $this->equipement;
    }

    public function setEquipement($value)
    {
        $this->equipement = $value;
    }

 public function getNomMaintenance(): string
{
    return $this->nom_maintenance;
}

public function setNomMaintenance(string $value): self
{
    $this->nom_maintenance = $value;
    return $this;
}

    public function getId_agriculteur()
    {
        return $this->id_agriculteur;
    }

    public function setId_agriculteur($value)
    {
        $this->id_agriculteur = $value;
    }

    #[ORM\OneToMany(mappedBy: "id_maintenance", targetEntity: Tache::class)]
    private Collection $taches;

        public function getTaches(): Collection
        {
            return $this->taches;
        }
    
        public function addTache(Tache $tache): self
        {
            if (!$this->taches->contains($tache)) {
                $this->taches[] = $tache;
                $tache->setId_maintenance($this);
            }
    
            return $this;
        }
    
        public function removeTache(Tache $tache): self
        {
            if ($this->taches->removeElement($tache)) {
                // set the owning side to null (unless already changed)
                if ($tache->getId_maintenance() === $this) {
                    $tache->setId_maintenance(null);
                }
            }
    
            return $this;
        }
}
