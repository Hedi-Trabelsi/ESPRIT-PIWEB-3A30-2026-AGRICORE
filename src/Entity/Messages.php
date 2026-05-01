<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Messages
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $sender_id;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $receiver_id = null;

    #[ORM\Column(type: "text", columnDefinition: "MEDIUMTEXT")]
    private string $content;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $timestamp;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $event_id = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $value): self
    {
        $this->id = $value;
        return $this;
    }

    public function getSender_id(): int
    {
        return $this->sender_id;
    }

    public function setSender_id(int $value): self
    {
        $this->sender_id = $value;
        return $this;
    }

    public function getReceiver_id(): ?int
    {
        return $this->receiver_id;
    }

    public function setReceiver_id(?int $value): self
    {
        $this->receiver_id = $value;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $value): self
    {
        $this->content = $value;
        return $this;
    }

    public function getTimestamp(): \DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $value): self
    {
        $this->timestamp = $value;
        return $this;
    }

    public function getEventId(): ?int
    {
        return $this->event_id;
    }

    public function setEventId(?int $event_id): self
    {
        $this->event_id = $event_id;
        return $this;
    }
}
