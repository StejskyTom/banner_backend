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
            $widget->setLogos([
                "https://upload.wikimedia.org/wikipedia/commons/thumb/a/a7/React-icon.svg/1150px-React-icon.svg.png",
                "https://upload.wikimedia.org/wikipedia/commons/thumb/9/99/Unofficial_JavaScript_logo_2.svg/1200px-Unofficial_JavaScript_logo_2.svg.png",
                "https://w7.pngwing.com/pngs/585/802/png-transparent-symfony-full-logo-tech-companies-thumbnail.png"
            ]);
        }

        $manager->persist($user);
        $manager->flush();
    }
}
