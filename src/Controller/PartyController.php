<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\PartyType;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/party', name: 'party_')]
class PartyController extends AbstractController
{
    #[Route('/add', name: 'add')]
    public function add(Request $request, EntityManagerInterface $entityManager, SiteRepository $siteRepository, ParticipantRepository $participantRepository, EtatRepository $etatRepository): Response
    {
        $party = new Sortie();
        $partyForm = $this->createForm(PartyType::class, $party);

        $partyForm->handleRequest($request);

        if ($partyForm->isSubmitted() && $partyForm->isValid()) {

            $organizer = $participantRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $site = $siteRepository->findOneBy(['id' => $organizer]);
            $state = $etatRepository->findOneBy(['label' => 'Créée']);

            $party->setOrganizer($organizer)
                ->setState($state)
                ->setSite($site);

            $entityManager->persist($party);
            $entityManager->flush();

            $this->addFlash('success', 'La sortie a bien été enregistrée.');

            return $this->redirectToRoute('party_add');
        }

        return $this->render('party/add.html.twig', [
            'partyForm' => $partyForm->createView()
        ]);
    }
}
