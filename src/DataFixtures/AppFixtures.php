<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Cryptocurrencies;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Users
        $users = [];

        $admin = new User();
        $admin->setEmail('admin@bitchest.com')
              ->setPassword($this->passwordHasher->hashPassword($admin, 'Bidule123'))
              ->setRoles(['ROLE_ADMIN', 'ROLE_USER'])
              ->setEuros(500);

        $users[] = $admin;
        $manager->persist($admin);

        // Load cryptocurrencies
        $cryptocurrencies = [
            ['id' => 1, 'name' => 'Bitcoin', 'symbol' => 'BTC'],
            ['id' => 2, 'name' => 'Ethereum', 'symbol' => 'ETH'],
            ['id' => 3, 'name' => 'Ripple', 'symbol' => 'XRP'],
            ['id' => 4, 'name' => 'NEM', 'symbol' => 'XEM'],
            ['id' => 5, 'name' => 'Bitcoin Cash', 'symbol' => 'BCH'],
            ['id' => 6, 'name' => 'Cardano', 'symbol' => 'ADA'],
            ['id' => 7, 'name' => 'Litecoin', 'symbol' => 'LTC'],
            ['id' => 8, 'name' => 'Stellar', 'symbol' => 'XLM'],
            ['id' => 9, 'name' => 'IOTA', 'symbol' => 'IOTA'],
            ['id' => 10, 'name' => 'Dash', 'symbol' => 'DASH'],
        ];

        foreach ($cryptocurrencies as $cryptoData) {
            $cryptocurrency = new Cryptocurrencies();
            $cryptocurrency->setCryptoName($cryptoData['name']);
            $cryptocurrency->setCryptoSymbol($cryptoData['symbol']);
            $manager->persist($cryptocurrency);
        }

        // Call to flush to save changes
        $manager->flush();
    }
}
