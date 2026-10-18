<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// The table is called "orders" because "order" is a reserved word in SQL.
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    private ?string $planName = null;

    #[ORM\Column(length: 20)]
    private ?string $planSection = null;

    /** Whole pesos, e.g. 1499. (The plan catalog keeps "1,499" for display only.) */
    #[ORM\Column]
    private ?int $pricePaid = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPlanName(): ?string
    {
        return $this->planName;
    }

    public function setPlanName(string $planName): static
    {
        $this->planName = $planName;

        return $this;
    }

    public function getPlanSection(): ?string
    {
        return $this->planSection;
    }

    public function setPlanSection(string $planSection): static
    {
        $this->planSection = $planSection;

        return $this;
    }

    public function getPricePaid(): ?int
    {
        return $this->pricePaid;
    }

    public function setPricePaid(int $pricePaid): static
    {
        $this->pricePaid = $pricePaid;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
