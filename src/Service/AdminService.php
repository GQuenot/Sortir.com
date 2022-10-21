<?php

namespace App\Service;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\ByteString;

class AdminService
{
    public function __construct(private readonly CsvReader $csvreader,
                                private readonly MailService $mailService,
//                                private readonly EntityManager $entityManager,
                                private readonly SiteRepository $siteRepository,
                                private readonly ParticipantRepository $participantRepository,
                                private readonly UserPasswordHasherInterface $passwordHasher)
    {}

    /**
     * @throws TransportExceptionInterface
     */
    public function importParticipants(mixed $participantsCsv): void
    {
        $participantsData = $this->csvreader->getData($participantsCsv);

        foreach ($participantsData as $participantData) {
            // Si le participant existe déja, on passe ce participant
            $participant = $this->participantRepository->findOneBy(['email' => $participantData[0]]);
            if($participant) {
                continue;
            }

            $participant = new Participant();
            $site = $this->siteRepository->findOneBy(['name' => $participantData[5]]);
            $generatedPassword = ByteString::fromRandom(32)->toString();

            $participant->setEmail($participantData[0])
            ->setPseudo($participantData[1])
            ->setFirstname($participantData[2])
            ->setLastname($participantData[3])
            ->setPhone($participantData[4])
            ->setActive(1)
            ->setRoles(["ROLE_USER"])
            ->setSite($site)
            ->setPassword($this->passwordHasher->hashPassword($participant, $generatedPassword));

            $this->participantRepository->save($participant, true);

            // Send the creation mail to the participant with his password
            $this->mailService->sendMail($participant->getEmail(),
                'Creation de votre compte Sortir.com',
                'La création de votre compte Sortir.com a été effectué, voici votre mot de passe : '. $generatedPassword
            );
        }
    }
}
