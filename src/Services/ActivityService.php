<?php

namespace App\Services;

use App\Entity\Activity;
use App\Repository\StateRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
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

    public function saveActivity(activity $activity, Bool $publish) : array {
        $response = ['code' => 'success', 'message' => 'La activity a bien été enregistrée'];

        $organizer = $this->participantRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);
        $site = $this->siteRepository->find($organizer);

        $activity->setOrganizer($organizer)
            ->setSite($site);

        // Publish or create the activity
        if($publish) {
            $response = $this->publish($activity);
        } else {
            $activity->setState($this->stateRepository->findOneBy(['label' => $this->states['created']]));
            $this->addParticipant($activity);
        }

        try {
            $this->entityManager->persist($activity);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $response = ['code' => 'danger', 'message' => "La sortie n'a pas pu être enregistrée : $e"];
        }

        return $response;
    }

    public function publish(activity $activity) : array {
        $state = $this->stateRepository->findOneBy(['label' => $this->states['open']]);
        $activity->setState($state);


        try {
            $this->entityManager->persist($activity);
            $this->entityManager->flush();
        } catch (Exception $e) {
            return ['code' => 'danger', 'message' => "La sortie n'a pas pu être enregistrée : $e"];
        }

        return ['code' => 'success', 'message' => 'La sprtie à bien été publiée'];
    }

    public function closeSubscription(activity $activity): void {
        $state = $this->stateRepository->findOneBy(['label' => $this->states['closed']]);
        $activity->setState($state);

        $this->entityManager->persist($activity);
        $this->entityManager->flush();
    }

    public function addParticipant(activity $activity) {
        $participant = $this->participantRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);

        $activity->addParticipant($participant);

        try {
            $this->entityManager->persist($activity);

            // If the max subscription is hit, the activity is closed
            if($activity->getParticipants()->count() >= $activity->getPlaceLimit()) {
                $this->closeSubscription($activity);
            }

            $this->entityManager->flush();
        } catch (Exception $e) {
            return ['code' => 'danger', 'message' => "Une erreur est survenue lors de l'inscription : $e"];
        }

        return ['code' => 'success', 'message' => 'Inscription réussie'];
    }
}