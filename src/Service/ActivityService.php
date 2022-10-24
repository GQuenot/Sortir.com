<?php

namespace App\Service;

use App\Entity\Activity;
use App\Repository\StateRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Core\Security;

class ActivityService
{
    public function __construct(private readonly EntityManagerInterface $entityManager,
                                private readonly SiteRepository         $siteRepository,
                                private readonly ParticipantRepository  $participantRepository,
                                private readonly StateRepository        $stateRepository,
                                private readonly Security               $security,
                                private readonly Array                  $states)
    {}

    /**
     * Save the activity, can be published at the same time with $public = true,
     * this will add the organizer as a participant too
     *
     * @param Activity $activity
     * @param bool $publish
     * @return void
     * @throws Exception
     */
    public function saveActivity(activity $activity, Bool $publish) : void {
        $organizer = $this->participantRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);
        $site = $this->siteRepository->find($organizer);

        $activity->setOrganizer($organizer)
            ->setSite($site);

        // Publish or create the activity
        if($publish) {
            $this->publish($activity);
        } else {
            $activity->setState($this->stateRepository->findOneBy(['label' => $this->states['created']]));
        }

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
    }

    public function publish(activity $activity) : void {
        $state = $this->stateRepository->findOneBy(['label' => $this->states['open']]);
        $activity->setState($state);

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
    }

    /**
     * // If the subscription limit date is hit, participant can't sub to activity and the activity is set to closed
     *
     * @param Activity $activity
     * @return void
     * @throws Exception
     */
    public function closeSubscription(activity $activity): void {
        if($activity->getSubLimitDate() < new DateTime()) {
            $state = $this->stateRepository->findOneBy(['label' => $this->states['closed']]);
            $activity->setState($state);
            $this->addParticipant($activity);

            $this->entityManager->persist($activity);
            $this->entityManager->flush();
        }
    }

    /**
     * @throws Exception
     */
    public function addParticipant(activity $activity): void
    {
        $participant = $this->participantRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);

        $activity->addParticipant($participant);

        $this->entityManager->persist($activity);

        // If the max subscription is hit, the activity is closed
        if($activity->getParticipants()->count() >= $activity->getPlaceLimit()) {
            $this->closeSubscription($activity);
        }

        $this->entityManager->flush();
    }

    /**
     * remove a participant from the list of an activity and change the state if necessary
     *
     * If the state of the activity does not allow it or the subLimitDate is hit, remove is rejected
     *
     * @param Activity $activity
     * @return void
     * @throws Exception
     */
    public function removeParticipant(activity $activity): void
    {
        $currentTime = new DateTime();

        if (!($activity->getState()->getLabel() === $this->states['open'] ||
            ($activity->getState()->getLabel() === $this->states['closed']
                && $activity->getSubLimitDate() > $currentTime)))
        {
            throw new Exception('Il est trop tard pour se désister');
        }

        $participant = $this->participantRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);

        $activity->removeParticipant($participant);
        if ($activity->getSubLimitDate() < $currentTime) {
            $activity->setState($this->stateRepository->findOneBy(['label' => $this->states['open']]));
        }

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
    }
}