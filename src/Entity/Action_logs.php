<?php

namespace App\Entity;

use App\Enum\ActionType;
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

    #[ORM\Column(type: "string", enumType: ActionType::class, length: 50)]
    private ActionType $action_type = ActionType::CREATE;

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
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "created_by_id", referencedColumnName: "id", nullable: true)]
    private ?User $created_by = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "updated_by_id", referencedColumnName: "id", nullable: true)]
    private ?User $updated_by = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $value): self
    {
        $this->id = $value;
        return $this;
    }

    public function getUpdated_at(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdated_at(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getCreated_by(): ?User
    {
        return $this->created_by;
    }

    public function setCreated_by(?User $created_by): self
    {
        $this->created_by = $created_by;
        return $this;
    }

    public function getUpdated_by(): ?User
    {
        return $this->updated_by;
    }

    public function setUpdated_by(?User $updated_by): self
    {
        $this->updated_by = $updated_by;
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

    public function getAction_type(): ActionType
    {
        return $this->action_type;
    }

    public function setAction_type(ActionType $value): self
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

    public function setCreated_at(\DateTimeImmutable $value): self
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

    public function getActionType(): ActionType
    {
        return $this->action_type;
    }

    public function setActionType(ActionType $action_type): static
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

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at instanceof \DateTimeImmutable
            ? $created_at
            : \DateTimeImmutable::createFromInterface($created_at);
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->created_by;
    }

    public function setCreatedBy(?User $created_by): static
    {
        $this->created_by = $created_by;
        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updated_by;
    }

    public function setUpdatedBy(?User $updated_by): static
    {
        $this->updated_by = $updated_by;
        return $this;
    }

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }
}
