<?php

namespace App\Services;

use App\Entity\Sortie;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class PartyService
{
    public function __construct(private EntityManagerInterface $entityManager,
                                private SiteRepository $siteRepository,
                                private ParticipantRepository $participantRepository,
                                private EtatRepository $etatRepository,
                                private Security $security,
                                private Array $states) {
    }

    public function saveParty(Sortie $party, Bool $save) {
        $organizer = $this->participantRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);
        $site = $this->siteRepository->find($organizer);

        $stateLabel = $this->states['open'];
        if($save) {
            $stateLabel = $this->states['created'];
        }

        $state = $this->etatRepository->findOneBy(['label' => $stateLabel]);

        $party->setOrganizer($organizer)
            ->setState($state)
            ->setSite($site);

        $this->entityManager->persist($party);
        $this->entityManager->flush();
    }

    public function CloseSubscription(Sortie $party): void {
        $state = $this->etatRepository->findOneBy(['label' => $this->states['closed']]);
        $party->setState($state);

        $this->entityManager->persist($party);
        $this->entityManager->flush();
    }
}