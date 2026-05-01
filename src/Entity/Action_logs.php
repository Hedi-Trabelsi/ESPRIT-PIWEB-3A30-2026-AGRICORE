<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'action_logs_legacy')]
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

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $value): self
    {
        $this->id = $value;
        return $this;
    }

    public function getUser_id(): int
    {
        return $this->user_id;
    }

    public function setUser_id(int $value): self
    {
        $this->user_id = $value;
        return $this;
    }

    public function getAction_type(): string
    {
        return $this->action_type;
    }

    public function setAction_type(string $value): self
    {
        $this->action_type = $value;
        return $this;
    }

    public function getTarget_table(): string
    {
        return $this->target_table;
    }

    public function setTarget_table(string $value): self
    {
        $this->target_table = $value;
        return $this;
    }

    public function getTarget_id(): int
    {
        return $this->target_id;
    }

    public function setTarget_id(int $value): self
    {
        $this->target_id = $value;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $value): self
    {
        $this->description = $value;
        return $this;
    }

    public function getOld_value(): string
    {
        return $this->old_value;
    }

    public function setOld_value(string $value): self
    {
        $this->old_value = $value;
        return $this;
    }

    public function getNew_value(): string
    {
        return $this->new_value;
    }

    public function setNew_value(string $value): self
    {
        $this->new_value = $value;
        return $this;
    }

    public function getCreated_at(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $value): self
    {
        $this->created_at = $value;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getActionType(): ?string
    {
        return $this->action_type;
    }

    public function setActionType(string $action_type): static
    {
        $this->action_type = $action_type;
        return $this;
    }

    public function getTargetTable(): ?string
    {
        return $this->target_table;
    }

    public function setTargetTable(string $target_table): static
    {
        $this->target_table = $target_table;
        return $this;
    }

    public function getTargetId(): ?int
    {
        return $this->target_id;
    }

    public function setTargetId(int $target_id): static
    {
        $this->target_id = $target_id;
        return $this;
    }

    public function getOldValue(): ?string
    {
        return $this->old_value;
    }

    public function setOldValue(string $old_value): static
    {
        $this->old_value = $old_value;
        return $this;
    }

    public function getNewValue(): ?string
    {
        return $this->new_value;
    }

    public function setNewValue(string $new_value): static
    {
        $this->new_value = $new_value;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }
}
