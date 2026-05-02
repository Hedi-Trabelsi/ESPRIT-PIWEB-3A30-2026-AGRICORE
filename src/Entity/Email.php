<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Email
{
    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $address = null;

    public function __construct(?string $address = null)
    {
        $this->address = $address;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function __toString(): string
    {
        return $this->address ?? '';
    }
}
