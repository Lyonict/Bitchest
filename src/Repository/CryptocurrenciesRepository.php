<?php

namespace App\Repository;

use App\Entity\Cryptocurrencies;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cryptocurrencies>
 *
 * @method Cryptocurrencies|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cryptocurrencies|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cryptocurrencies[]    findAll()
 * @method Cryptocurrencies[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CryptocurrenciesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cryptocurrencies::class);
    }

    public function findCryptoNameById(int $id): ?string
    {
        $crypto = $this->find($id);
        return $crypto ? $crypto->getCryptoName() : null;
    }
}
