<?php

namespace App\DataFixtures;

use App\Entity\City;
use App\Entity\Participant;
use App\Entity\Place;
use App\Entity\Site;
use App\Entity\State;
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

        $this->addStates();
        $this->addCitiesAndPlaces();
        $this->addUsersWithSites();
    }

    public function addStates()
    {
        $states = ['Créée', 'Ouverte', 'Clôturée', 'Activité en cours', 'Passée', 'Annulée'];

        for ($i = 0; $i < count($states); $i++) {

            $state = new State();
            $state->setLabel($states[$i]);
            $this->manager->persist($state);
        }

        $this->manager->flush();
    }

    public function addCitiesAndPlaces()
    {
        $cities = ['RENNES' => 35000, 'QUIMPER' => 29000, 'NIORT' => 79000, 'NANTES' => 44000];

        foreach ($cities as $cityKey => $cityValue) {

            $city = new City();
            $city->setName($cityKey)
                ->setPostalCode($cityValue);

            $this->manager->persist($city);

            $place = new Place();
            $place->setName($this->generator->company)
                ->setCity($city)
                ->setStreet($this->generator->streetName)
                ->setLatitude($this->generator->latitude)
                ->setLongitude($this->generator->longitude);
            $this->manager->persist($place);
        }

        $this->manager->flush();
    }

    public function addUsersWithSites()
    {
        $sites = ['RENNES', 'QUIMPER', 'NIORT', 'NANTES'];

        foreach($sites as $siteArray) {

            $site = new Site();
            $site->setName($siteArray);
            $this->manager->persist($site);

            $user = new Participant();
            $user->setRoles(['ROLE_USER'])
                ->setEmail($this->generator->email)
                ->setFirstname($this->generator->firstName)
                ->setLastname($this->generator->lastName)
                ->setPassword($this->userPasswordHasher->hashPassword($user, 'toto'))
                ->setActive(1)
                ->setPhone($this->generator->phoneNumber)
                ->setPseudo($this->generator->userName)
                ->setSite($site);

            $this->manager->persist($user);
        }

        $this->manager->flush();
    }


}