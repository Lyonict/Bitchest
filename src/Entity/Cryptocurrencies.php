<?php

namespace App\Entity;

use App\Repository\CryptocurrenciesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CryptocurrenciesRepository::class)]
class Cryptocurrencies
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $crypto_id = null;

    #[ORM\Column(length: 255)]
    private ?string $crypto_name = null;

    #[ORM\Column(length: 10)]
    private ?string $crypto_symbol = null;

    public function getCryptoId(): ?int
    {
        return $this->crypto_id;
    }

    public function getCryptoName(): ?string
    {
        return $this->crypto_name;
    }

    public function setCryptoName(string $crypto_name): static
    {
        $this->crypto_name = $crypto_name;

        return $this;
    }

    public function getCryptoSymbol(): ?string
    {
        return $this->crypto_symbol;
    }

    public function setCryptoSymbol(string $crypto_symbol): static
    {
        $this->crypto_symbol = $crypto_symbol;

        return $this;
    }
}
