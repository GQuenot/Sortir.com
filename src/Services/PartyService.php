<?php

namespace App\Services;

use App\Entity\Sortie;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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

    public function saveParty(Sortie $party, Bool $publish) : array {
        $response = ['code' => 'success', 'message' => 'La sortie a bien été enregistrée'];

        $organizer = $this->participantRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);
        $site = $this->siteRepository->find($organizer);

        $party->setOrganizer($organizer)
            ->setSite($site);

        // Publish or create the activity
        if($publish) {
            $response = $this->publish($party);
        } else {
            $party->setState($this->etatRepository->findOneBy(['label' => $this->states['created']]));
            $this->addParticipant($party);
        }

        try {
            $this->entityManager->persist($party);
            $this->entityManager->flush();
        } catch (Exception $e) {
            $response = ['code' => 'danger', 'message' => "La sortie n'a pas pu être enregistrée : $e"];
        }

        return $response;
    }

    public function publish(Sortie $party) : array {
        $state = $this->etatRepository->findOneBy(['label' => $this->states['open']]);
        $party->setState($state);


        try {
            $this->entityManager->persist($party);
            $this->entityManager->flush();
        } catch (Exception $e) {
            return ['code' => 'danger', 'message' => "La sortie n'a pas pu être enregistrée : $e"];
        }

        return ['code' => 'success', 'message' => 'La sortie à bien été publiée'];
    }

    public function closeSubscription(Sortie $party): void {
        $state = $this->etatRepository->findOneBy(['label' => $this->states['closed']]);
        $party->setState($state);

        $this->entityManager->persist($party);
        $this->entityManager->flush();
    }

    public function addParticipant(Sortie $party) {
        $participant = $this->participantRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);

        $party->addParticipant($participant);

        try {
            $this->entityManager->persist($party);

            // If the max subscription is hit, the party is closed
            if($party->getParticipants()->count() >= $party->getMaxSubscription()) {
                $this->closeSubscription($party);
            }

            $this->entityManager->flush();
        } catch (Exception $e) {
            return ['code' => 'danger', 'message' => "Une erreur est survenue lors de l'inscription : $e"];
        }

        return ['code' => 'success', 'message' => 'Inscription reussie'];
    }
}