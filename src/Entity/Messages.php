<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Messages
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $sender_id;

    #[ORM\Column(type: "integer")]
    private int $receiver_id;

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $timestamp;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getSender_id()
    {
        return $this->sender_id;
    }

    public function setSender_id($value)
    {
        $this->sender_id = $value;
    }

    public function getReceiver_id()
    {
        return $this->receiver_id;
    }

    public function setReceiver_id($value)
    {
        $this->receiver_id = $value;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($value)
    {
        $this->content = $value;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setTimestamp($value)
    {
        $this->timestamp = $value;
    }
}
