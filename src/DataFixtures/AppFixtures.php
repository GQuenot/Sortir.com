<?php

namespace App\DataFixtures;

use App\Entity\Participant;
use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;
    private Generator $generator;
    private ObjectManager $manager;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->generator = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $this->addUsers();
        // $product = new Product();
        // $manager->persist($product);

        //$manager->flush();
    }

    public function addUsers()
    {
        $siteName = ['RENNES', 'QUIMPER', 'NIORT', 'NANTES'];

        for($i = 0; $i < 4; $i++){
            $site[$i] = new Site();
            $site[$i]->setName($this->generator->randomElement($siteName));
            $this->manager->persist($site[$i]);
        }


        for($i = 0; $i < 3; $i++){

            $user = new Participant();
            $user->setRoles(['ROLE_USER'])
                ->setEmail($this->generator->email)
                ->setFirstname($this->generator->firstName)
                ->setLastname($this->generator->lastName)
                ->setPassword($this->userPasswordHasher->hashPassword($user, 'toto'))
                ->setActive(1)
                ->setPhone($this->generator->phoneNumber)
                ->setPseudo($this->generator->userName)
                ->setSite($site[$i]);

            $this->manager->persist($user);
        }
        $this->manager->flush();
    }


}