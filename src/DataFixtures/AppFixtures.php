<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Users
        $users = [];

        $admin = new User();
        $admin->setEmail('admin@bitchest.com')
            ->setPassword('Bidule123')
            ->setRoles(['ROLE_ADMIN, ROLE_USER'])
            ->setEuros(500);

        $users[] = $admin;
        $manager->persist($admin);
    }
}
