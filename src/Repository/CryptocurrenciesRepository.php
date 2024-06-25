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

    //    /**
    //     * @return Cryptocurrencies[] Returns an array of Cryptocurrencies objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Cryptocurrencies
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
