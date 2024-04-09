<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('bidule@gmail.com');
        $user->setUsername('BiduleMachindu75');
        $user->setPassword('Bidule123');
        $user->setBank(500.00);
        $user->setWallet([
            "Bitcoin" => 2,
            "Ethereum" => 12
        ]);

        $manager->persist($user);
        $manager->flush();

        $manager->flush();
    }
}
