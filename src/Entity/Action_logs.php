<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Action_logs
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $user_id;

    #[ORM\Column(type: "string", length: 50)]
    private string $action_type;

    #[ORM\Column(type: "string", length: 50)]
    private string $target_table;

    #[ORM\Column(type: "integer")]
    private int $target_id;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "string")]
    private string $old_value;

    #[ORM\Column(type: "string")]
    private string $new_value;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getAction_type()
    {
        return $this->action_type;
    }

    public function setAction_type($value)
    {
        $this->action_type = $value;
    }

    public function getTarget_table()
    {
        return $this->target_table;
    }

    public function setTarget_table($value)
    {
        $this->target_table = $value;
    }

    public function getTarget_id()
    {
        return $this->target_id;
    }

    public function setTarget_id($value)
    {
        $this->target_id = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getOld_value()
    {
        return $this->old_value;
    }

    public function setOld_value($value)
    {
        $this->old_value = $value;
    }

    public function getNew_value()
    {
        return $this->new_value;
    }

    public function setNew_value($value)
    {
        $this->new_value = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }
}
