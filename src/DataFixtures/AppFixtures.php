<?php

namespace App\DataFixtures;

use App\Entity\User;
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

        // Call to flush to save changes
        $manager->flush();
    }
}
