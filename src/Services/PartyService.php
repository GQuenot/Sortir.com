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
    private EntityManagerInterface $entityManager;
    private SiteRepository $siteRepository;
    private ParticipantRepository $participantRepository;
    private EtatRepository $etatRepository;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, SiteRepository $siteRepository, ParticipantRepository $participantRepository, EtatRepository $etatRepository, Security $security) {
        $this->entityManager = $entityManager;
        $this->siteRepository = $siteRepository;
        $this->participantRepository = $participantRepository;
        $this->etatRepository = $etatRepository;
        $this->security = $security;
    }

    public function saveParty(Sortie $party, Bool $save) {
        $organizer = $this->participantRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);
        $site = $this->siteRepository->find($organizer);

        $stateLabel = 'Ouverte';
        if($save) {
            $stateLabel = 'Créée';
        }

        $state = $this->etatRepository->findOneBy(['label' => $stateLabel]);

        $party->setOrganizer($organizer)
            ->setState($state)
            ->setSite($site);

        $this->entityManager->persist($party);
        $this->entityManager->flush();
    }
}