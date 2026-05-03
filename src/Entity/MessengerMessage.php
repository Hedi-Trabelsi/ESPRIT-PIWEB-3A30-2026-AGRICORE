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
    private string $body = '';

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
    private string $headers = '';

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
    private string $queue_name = '';

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
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeImmutable $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdated_at(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdated_at(?\DateTimeImmutable $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private \DateTimeImmutable $available_at;

    public function getAvailable_at(): ?\DateTimeInterface
    {
        return $this->available_at;
    }

    public function setAvailable_at(\DateTimeImmutable $available_at): self
    {
        $this->available_at = $available_at;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeImmutable $delivered_at = null;

    public function getDelivered_at(): ?\DateTimeInterface
    {
        return $this->delivered_at;
    }

    public function setDelivered_at(?\DateTimeImmutable $delivered_at): self
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getAvailableAt(): ?\DateTimeImmutable
    {
        return $this->available_at;
    }

    public function setAvailableAt(\DateTimeImmutable $available_at): static
    {
        $this->available_at = $available_at;

        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->delivered_at;
    }

    public function setDeliveredAt(?\DateTimeImmutable $delivered_at): static
    {
        $this->delivered_at = $delivered_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->available_at = new \DateTimeImmutable();
    }
}
