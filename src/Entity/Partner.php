<?php

namespace App\Entity;

use App\Repository\PartnerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A user's partnership tier. One row per user: switching tiers updates this row.
 */
#[ORM\Entity(repositoryClass: PartnerRepository::class)]
#[ORM\Table(name: 'partners')]
class Partner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private ?string $tier = null;

    /** Whole pesos; 0 for the free Authorized tier. */
    #[ORM\Column]
    private ?int $pricePaid = null;

    /** When this tier was bought (updated whenever the user switches tiers). */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $purchasedAt;

    public function __construct()
    {
        $this->purchasedAt = new \DateTimeImmutable();
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

    public function getTier(): ?string
    {
        return $this->tier;
    }

    public function getPricePaid(): ?int
    {
        return $this->pricePaid;
    }

    public function getPurchasedAt(): \DateTimeImmutable
    {
        return $this->purchasedAt;
    }

    /** Sets (or replaces) the tier and restarts the purchase date. */
    public function switchTo(string $tier, int $pricePaid): static
    {
        $this->tier = $tier;
        $this->pricePaid = $pricePaid;
        $this->purchasedAt = new \DateTimeImmutable();

        return $this;
    }
}
