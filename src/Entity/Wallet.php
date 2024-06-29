<?php

namespace App\Entity;

use App\Repository\WalletRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: WalletRepository::class)]
class Wallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'wallets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(name: "crypto_id", type: "integer")]
    private ?int $cryptoId = null;

    #[ORM\Column(nullable: true)]
    private ?float $quantity = null;

    #[ORM\Column(nullable: true)]
    private ?float $totalCost = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCryptoId(): ?int
    {
        return $this->cryptoId;
    }

    public function setCryptoId(int $cryptoId): static
    {
        $this->cryptoId = $cryptoId;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setQuantity(?float $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getTotalCost(): ?float
    {
        return $this->totalCost;
    }

    public function setTotalCost(?float $totalCost): static
    {
        $this->totalCost = $totalCost;

        return $this;
    }

    public function calculateProfitLoss(float $currentPrice): float
    {
        $currentValue = $this->quantity * $currentPrice;
        if ($currentValue - $this->totalCost === (-5.5511151231258E-17)){
            return 0;
        }
        else {
            return $currentValue - $this->totalCost;
        }    
    }

    public function updateTotalCostAfterSale(float $quantitySold): void
    {
        if ($this->quantity > 0) {
            $sub = ($this->quantity - $quantitySold) / $this->quantity;
            
            $this->totalCost = $this->totalCost * $sub;
        }
    }
}
