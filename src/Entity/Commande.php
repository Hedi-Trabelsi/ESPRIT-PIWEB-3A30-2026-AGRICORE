<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commande')]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'date_commande', type: 'datetime')]
    private ?\DateTimeInterface $dateCommande = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $total = null;

    #[ORM\Column(name: 'agriculteur_id', type: 'integer')]
    private ?int $agriculteurId = null;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: LigneCommande::class, cascade: ['persist', 'remove'])]
    private Collection $lignes;

    public function __construct()
    {
        $this->dateCommande = new \DateTime();
        $this->lignes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeInterface $dateCommande): self
    {
        $this->dateCommande = $dateCommande;
        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): self
    {
        $this->total = $total;
        return $this;
    }

    public function getAgriculteurId(): ?int
    {
        return $this->agriculteurId;
    }

    public function setAgriculteurId(int $agriculteurId): self
    {
        $this->agriculteurId = $agriculteurId;
        return $this;
    }

    /**
     * @return Collection<int, LigneCommande>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(LigneCommande $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }

        return $this;
    }

    public function removeLigne(LigneCommande $ligne): self
    {
        if ($this->lignes->removeElement($ligne) && $ligne->getCommande() === $this) {
            $ligne->setCommande(null);
        }

        return $this;
    }
}
