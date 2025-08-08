<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Widget;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('test@test.cz');
        $user->setRoles(['ROLE_USER']);
        $password = $this->hasher->hashPassword($user, 'test');
        $user->setPassword($password);

        for ($i = 0; $i < 5; $i++) {
            $widget = new Widget($user, 'Widget ' . $i);
            $manager->persist($widget);
        }

        $manager->persist($user);
        $manager->flush();
    }
}
