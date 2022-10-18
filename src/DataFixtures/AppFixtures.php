<?php

namespace App\DataFixtures;

use App\Entity\Participant;
use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Faker;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;



class AppFixtures extends Fixture
{

    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->generator = Factory::create('fr_FR');
    }



    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $faker = Faker\Factory::create('fr_FR');

        $site_name = ['RENNES', 'QUIMPER', 'NIORT', 'NANTES'];

        $site = Array();
        for ($i = 0; $i < 4; $i++) {
            $site[$i] = new Site();
            $site[$i]->setName($faker->randomElement($site_name));

            $manager->persist($site[$i]);
        }

        $participant = Array();
        for ($i = 0; $i < 4; $i++) {
            $participant[$i] = new Participant();
            $participant[$i]->setEmail($faker->email);
            $participant[$i]->setFirstname($faker->firstName);
            $participant[$i]->setLastname($faker->lastname);
            $participant[$i]->setPassword($this->userPasswordHasher->hashPassword($participant[$i], 'toto'));
            $participant[$i]->setPhone($faker->phoneNumber);
            $participant[$i]->setActive(1);
            $participant[$i]->setRoles(["ROLE_USER"]);
            $participant[$i]->setPseudo($faker->userName);
            $participant[$i]->setSite($site[$i]);

            $manager->persist($participant[$i]);
        }

        $manager->flush();
    }
}
