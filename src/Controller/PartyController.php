<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\PartyType;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use App\Services\PartyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/party', name: 'party_')]
class PartyController extends AbstractController
{

    public function __construct(private PartyService $partyService,
                                private SortieRepository $sortieRepository,
                                private ParticipantRepository $participantRepository,
                                private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/add', name: 'add')]
    public function add(Request $request): Response
    {
        $party = new Sortie();
        return $this->saveParty($party, $request);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(int $id, Request $request): Response
    {
        $party = $this->sortieRepository->find($id);

        if($party->getState()->getLabel() != $this->getParameter('app.states')['created']) {
            $this->addFlash('warning', 'Une demande déja publiée ne peut être modifiée');
            return $this->redirectToRoute('party_add');

            //throw new Exception('Une demande déjà publiée ne peut être modifiée');
        }

        return $this->saveParty($party, $request);
    }

    /**
     * @param Sortie $party
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function saveParty(Sortie $party, Request $request): Response|RedirectResponse
    {
        $partyForm = $this->createForm(PartyType::class, $party);

        $partyForm->handleRequest($request);

        if ($partyForm->isSubmitted() && $partyForm->isValid()) {
            $this->partyService->saveParty($party, $partyForm->get('save')->isClicked());
            $this->addFlash('success', 'La sortie a bien été enregistrée.');

            return $this->redirectToRoute('party_add');
        }

        return $this->render('party/add.html.twig', [
            'partyForm' => $partyForm->createView()
        ]);
    }

    #[Route('/list', name: 'list')]
    public function list(): Response
    {
        $sorties = $this->sortieRepository->findPartiesNotArchived();

        return $this->render('party/list.html.twig', [
            'sorties' => $sorties,
        ]);
    }

    #[Route('/subscription/{partyId}', name: 'subscription')]
    public function subscription(int $partyId): Response
    {
        $party = $this->sortieRepository->find($partyId);

        // If the subscription limit date is hit, participant can't sub to party and the party is set to closed
        if(date('d-m-y h:i:s') > $party->getSubLimitDate()) {
            $this->partyService->CloseSubscription($party);
        }

        if($party->getState()->getLabel() !== $this->getParameter('app.states')['open']) {
            $this->addFlash('warning', 'Les inscriptions à cette sortie ne sont plus ouverte');
            return $this->redirectToRoute('party_list');
        }

        // Add the participant to the party
        $participant = $this->participantRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        $party->addParticipant($participant);

        $this->entityManager->persist($party);
        $this->entityManager->flush();

        $this->addFlash('success', 'Inscription reussie');

        // If the max subscription is hit, the party is closed
        if($party->getParticipants()->count() >= $party->getMaxSubscription()) {
            $this->partyService->CloseSubscription($party);
        }

        return $this->redirectToRoute('party_list');
    }

    #[Route('/unsubscription/{partyId}', name: 'unsubscription')]
    public function unsubscription(int $partyId): Response
    {
        $party = $this->sortieRepository->find($partyId);

        // Delete the participant to the party
        $participant = $this->participantRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        $party->removeParticipant($participant);

        $this->entityManager->persist($party);
        $this->entityManager->flush();

        $this->addFlash('success', 'Désistement reussi');

        return $this->redirectToRoute('party_list');
    }

    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    #[ParamConverter('sortie', class: 'App\Entity\Sortie')]
    public function show(Sortie $sortie): Response
    {
        return $this->render('party/detail.html.twig', [
            'sortie' => $sortie
        ]);
    }


}
