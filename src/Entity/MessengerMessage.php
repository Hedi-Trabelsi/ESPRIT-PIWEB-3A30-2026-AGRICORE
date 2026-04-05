<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\MessengerMessageRepository;

#[ORM\Entity(repositoryClass: MessengerMessageRepository::class)]
#[ORM\Table(name: 'messenger_messages')]
class MessengerMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $body = null;

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $headers = null;

    public function getHeaders(): ?string
    {
        return $this->headers;
    }

    public function setHeaders(string $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $queue_name = null;

    public function getQueue_name(): ?string
    {
        return $this->queue_name;
    }

    public function setQueue_name(string $queue_name): self
    {
        $this->queue_name = $queue_name;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $available_at = null;

    public function getAvailable_at(): ?\DateTimeInterface
    {
        return $this->available_at;
    }

    public function setAvailable_at(\DateTimeInterface $available_at): self
    {
        $this->available_at = $available_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $delivered_at = null;

    public function getDelivered_at(): ?\DateTimeInterface
    {
        return $this->delivered_at;
    }

    public function setDelivered_at(?\DateTimeInterface $delivered_at): self
    {
        $this->delivered_at = $delivered_at;
        return $this;
    }

    public function getQueueName(): ?string
    {
        return $this->queue_name;
    }

    public function setQueueName(string $queue_name): static
    {
        $this->queue_name = $queue_name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getAvailableAt(): ?\DateTime
    {
        return $this->available_at;
    }

    public function setAvailableAt(\DateTime $available_at): static
    {
        $this->available_at = $available_at;

        return $this;
    }

    public function getDeliveredAt(): ?\DateTime
    {
        return $this->delivered_at;
    }

    public function setDeliveredAt(?\DateTime $delivered_at): static
    {
        $this->delivered_at = $delivered_at;

        return $this;
    }

}
