<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ActionLogRepository;

#[ORM\Entity(repositoryClass: ActionLogRepository::class)]
#[ORM\Table(name: 'action_logs')]
class ActionLog
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

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $user_id = null;

    public function getUser_id(): ?int
    {
        return $this->user_id;
    }

    public function setUser_id(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $action_type = null;

    public function getAction_type(): ?string
    {
        return $this->action_type;
    }

    public function setAction_type(string $action_type): self
    {
        $this->action_type = $action_type;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $target_table = null;

    public function getTarget_table(): ?string
    {
        return $this->target_table;
    }

    public function setTarget_table(string $target_table): self
    {
        $this->target_table = $target_table;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $target_id = null;

    public function getTarget_id(): ?int
    {
        return $this->target_id;
    }

    public function setTarget_id(int $target_id): self
    {
        $this->target_id = $target_id;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: false)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $old_value = null;

    public function getOld_value(): ?string
    {
        return $this->old_value;
    }

    public function setOld_value(string $old_value): self
    {
        $this->old_value = $old_value;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $new_value = null;

    public function getNew_value(): ?string
    {
        return $this->new_value;
    }

    public function setNew_value(string $new_value): self
    {
        $this->new_value = $new_value;
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

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

}
