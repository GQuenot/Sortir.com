<?php

namespace App\DataFixtures;

use App\Entity\Participant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $faker = Faker\Factory::create('fr_FR');

        $participant = Array();
        for ($i = 0; $i < 4; $i++) {
            $participant[$i] = new Participant();
            $participant[$i]->setEmail($faker->email);
            $participant[$i]->setFirstname($faker->firstName);
            $participant[$i]->setLastname($faker->lastname);
            $participant[$i]->setPassword('123456');
            $participant[$i]->setPhone($faker->phoneNumber);
            $participant[$i]->setActive(1);
            $participant[$i]->setRoles(["ROLE_USER"]);
            $participant[$i]->setPseudo($faker->userName);

            $manager->persist($participant[$i]);
        }

        $manager->flush();
    }
}
