<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\PartyType;
use App\Repository\SortieRepository;
use App\Services\PartyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/party', name: 'party_')]
class PartyController extends AbstractController
{
    private PartyService $partyService;

    public function __construct(PartyService $partyService) {
        $this->partyService = $partyService;
    }

    #[Route('/add', name: 'add')]
    public function add(Request $request): Response
    {
        $party = new Sortie();
        $partyForm = $this->createForm(PartyType::class, $party);

        $partyForm->handleRequest($request);

        if ($partyForm->isSubmitted() && $partyForm->isValid()) {
            $this->partyService->saveParty($party);
            $this->addFlash('success', 'La sortie a bien été enregistrée.');

            return $this->redirectToRoute('party_add');
        }

        return $this->render('party/add.html.twig', [
            'partyForm' => $partyForm->createView()
        ]);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(int $id, Request $request, SortieRepository $sortieRepository): Response
    {
        $party = $sortieRepository->find($id);
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
}
