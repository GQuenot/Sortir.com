<?php

namespace App\Utils;

use App\Repository\ActivityRepository;
use App\Repository\StateRepository;
use Doctrine\ORM\EntityManagerInterface;

class UpdateStates
{
    public function __construct(private readonly ActivityRepository     $activityRepository,
                                private readonly StateRepository        $stateRepository,
                                private readonly EntityManagerInterface $entityManager)
    {}

    public function hasActivitiesStarted(): void
    {
        $activitiesStarted = $this->activityRepository->findActivitiesStarted();
        $stateA = $this->stateRepository->findOneBy(['label' => 'Activité en cours']);

        foreach ($activitiesStarted as $activityStarted) {
            $activityStarted->setState($stateA);
            $this->entityManager->persist($activityStarted);
        }

        $this->entityManager->flush();
    }

    public function hasActivitiesPassed(): void
    {
        $activitiesPassed = $this->activityRepository->findActivitiesPassed();
        $stateP = $this->stateRepository->findOneBy(['label' => 'Passée']);

        foreach ($activitiesPassed as $activityPassed) {
            $activityPassed->setState($stateP);
            $this->entityManager->persist($activityPassed);
        }

        $this->entityManager->flush();
    }

    public function hasActivitiesClosed(): void
    {
        $inscriptionsClosed = $this->activityRepository->findInscriptionClosed();
        $stateC = $this->stateRepository->findOneBy(['label' => 'Clôturée']);

        foreach ($inscriptionsClosed as $inscriptionClosed) {
            $inscriptionClosed->setState($stateC);
            $this->entityManager->persist($inscriptionClosed);
        }

        $this->entityManager->flush();
    }
}