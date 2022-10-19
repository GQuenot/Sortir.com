<?php

namespace App\Services;

use App\Entity\Activity;
use App\Repository\StateRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class ActivityService
{
    public function __construct(private EntityManagerInterface $entityManager,
                                private SiteRepository         $siteRepository,
                                private ParticipantRepository  $participantRepository,
                                private StateRepository        $stateRepository,
                                private Security               $security)
    {}

    public function saveParty(Activity $activity, Bool $save) {
        $organizer = $this->participantRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);
        $site = $this->siteRepository->find($organizer);

        $stateLabel = 'Ouverte';
        if($save) {
            $stateLabel = 'Créée';
        }

        $state = $this->stateRepository->findOneBy(['label' => $stateLabel]);

        $activity->setOrganizer($organizer)
            ->setState($state)
            ->setSite($site);

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
    }
}