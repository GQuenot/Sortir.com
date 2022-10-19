<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\PartyType;
use App\Repository\SortieRepository;
use App\Services\PartyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        return $this->saveParty($party, $request);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(int $id, Request $request, SortieRepository $sortieRepository): Response
    {
        $party = $sortieRepository->find($id);

        if($party->getState()->getLabel() != 'Créée') {
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
    public function list(SortieRepository $sortieRepository): Response
    {

        $sorties = $sortieRepository->findAll();

        return $this->render('party/list.html.twig', [
            'sorties' => $sorties,
        ]);
    }

    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    #[ParamConverter('sortie', class: 'App\Entity\Sortie')]
    public function show(Sortie $sortie, SortieRepository $sortieRepository): Response
    {

//        //sans paramConverter
//        $sortie = $sortieRepository->find($id);//
//        if(!$serie){
//            throw $this->createNotFoundException("Oops ! Serie not found !");
//        }

        return $this->render('party/detail.html.twig', [
            'sortie' => $sortie
        ]);
    }


}
