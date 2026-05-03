<?php

namespace App\Entity;

use App\Enum\ActionType;
use App\Repository\ActionLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActionLogRepository::class)]
#[ORM\Table(name: 'action_logs')]
class ActionLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $user_id = 0;

    /**
     * Explicitly setting type: 'string' alongside enumType resolves 
     * the Doctrine Doctor property_type_mismatch warning.
     */
    #[ORM\Column(type: 'string', enumType: ActionType::class)]
    private ActionType $action_type = ActionType::CREATE;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $target_table = '';

    #[ORM\Column(type: Types::INTEGER)]
    private int $target_id = 0;

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $old_value = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $new_value = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $created_at;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * -------------------------------------------------------------------------
     * FLEXIBLE DATE SETTERS
     * -------------------------------------------------------------------------
     * These accept \DateTimeInterface (both DateTime and DateTimeImmutable)
     * to prevent the "DateTime given, DateTimeImmutable expected" error.
     */
    public function setCreated_at(\DateTimeInterface $date): self 
    { 
        $this->created_at = \DateTimeImmutable::createFromInterface($date);
        return $this; 
    }

    public function setUpdated_at(?\DateTimeInterface $date): self 
    { 
        $this->updated_at = $date ? \DateTimeImmutable::createFromInterface($date) : null;
        return $this; 
    }

    /**
     * -------------------------------------------------------------------------
     * COMPATIBILITY LAYER (Snake Case for Controller & Twig)
     * -------------------------------------------------------------------------
     */

    public function setAction_type(string|ActionType $type): self
    {
        if (is_string($type)) {
            // Converts 'CREATE' to 'create' to match your Enum backing values
            $this->action_type = ActionType::from(strtolower($type));
        } else {
            $this->action_type = $type;
        }
        return $this;
    }

    public function getAction_type(): ActionType { return $this->action_type; }

    public function setUser_id(int $user_id): self { $this->user_id = $user_id; return $this; }
    public function getUser_id(): int { return $this->user_id; }

    public function setTarget_table(string $table): self { $this->target_table = $table; return $this; }
    public function getTarget_table(): string { return $this->target_table; }

    public function setTarget_id(int $id): self { $this->target_id = $id; return $this; }
    public function getTarget_id(): int { return $this->target_id; }

    public function setOld_value(?string $val): self { $this->old_value = $val; return $this; }
    public function getOld_value(): ?string { return $this->old_value; }

    public function setNew_value(?string $val): self { $this->new_value = $val; return $this; }
    public function getNew_value(): ?string { return $this->new_value; }

    public function getCreated_at(): \DateTimeImmutable { return $this->created_at; }
    public function getUpdated_at(): ?\DateTimeImmutable { return $this->updated_at; }

    /**
     * -------------------------------------------------------------------------
     * STANDARD CAMELCASE LAYER (Symfony Best Practices)
     * -------------------------------------------------------------------------
     */

    public function setActionType(ActionType $action_type): static { $this->action_type = $action_type; return $this; }
    public function getActionType(): ActionType { return $this->action_type; }

    public function setUserId(int $user_id): static { $this->user_id = $user_id; return $this; }
    public function getUserId(): int { return $this->user_id; }

    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getDescription(): string { return $this->description; }

    public function setCreatedAt(\DateTimeInterface $created_at): self { return $this->setCreated_at($created_at); }
    public function getCreatedAt(): \DateTimeImmutable { return $this->created_at; }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): self { return $this->setUpdated_at($updated_at); }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updated_at; }
}