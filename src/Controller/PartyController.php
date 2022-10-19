<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\PartyType;
use App\Repository\SortieRepository;
use App\Services\PartyService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/party', name: 'party_')]
class PartyController extends AbstractController
{

    public function __construct(private PartyService $partyService,
                                private SortieRepository $sortieRepository)
    {
    }

    #[Route('/add', name: 'add')]
    public function add(Request $request): Response
    {
        $party = new Sortie();
        $partyForm = $this->saveParty($party, $request);

        if ($partyForm->isSubmitted() && $partyForm->isValid()) {
            return $this->redirectToRoute('party_list');
        }

        return $this->render('party/add.html.twig', [
            'partyForm' => $partyForm->createView()
        ]);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(int $id, Request $request): Response
    {
        $party = $this->sortieRepository->find($id);

        if($party->getState()->getLabel() != $this->getParameter('app.states')['created']) {
            $this->addFlash('warning', 'Une demande déja publiée ne peut être modifiée');
            return $this->redirectToRoute('party_list');
        }

        $partyForm = $this->saveParty($party, $request);

        if ($partyForm->isSubmitted() && $partyForm->isValid()) {
            return $this->redirectToRoute('party_list');
        }

        return $this->render('party/edit.html.twig', [
            'partyForm' => $partyForm->createView(),
            'party' => $party
        ]);
    }

    /**
     * @param Sortie $party
     * @param Request $request
     * @return RedirectResponse
     */
    public function saveParty(Sortie $party, Request $request) : FormInterface|RedirectResponse
    {
        $partyForm = $this->createForm(PartyType::class, $party);

        $partyForm->handleRequest($request);

        if ($partyForm->isSubmitted() && $partyForm->isValid()) {
            $response = $this->partyService->saveParty($party, $partyForm->get('publish')->isClicked());
            $this->addFlash($response['code'], $response['message']);
        }

        return $partyForm;
    }

    #[Route('/publish/{partyId}', name: 'publish')]
    public function publish(int $partyId, Request $request): Response
    {
        $party = $this->sortieRepository->find($partyId);

        if($party->getState()->getLabel() != $this->getParameter('app.states')['created']) {
            $this->addFlash('warning', 'La sortie à déja été publiée');
        }

        $response = $this->partyService->publish($party);
        $this->addFlash($response['code'], $response['message']);

        return $this->redirectToRoute('party_list');
    }

    #[Route('/delete/{activityId}', name: 'delete')]
    public function delete(int $activityId): RedirectResponse
    {
        $activity = $this->sortieRepository->find($activityId);

        $this->sortieRepository->remove($activity, true);

        $this->addFlash('success', 'la sortie a bien été supprimée');

        return $this->redirectToRoute('party_list');
    }

    #[Route('/list', name: 'list')]
    public function list(): Response
    {
        $sorties = $this->sortieRepository->findAll();

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

        $response = $this->partyService->addParticipant($party);
        $this->addFlash($response['code'], $response['message']);

        return $this->redirectToRoute('party_list');
    }

    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    #[ParamConverter('sortie', class: 'App\Entity\Sortie')]
    public function show(Sortie $sortie): Response
    {

//        //sans paramConverter
//        $sortie = $this->sortieRepository->find($id);//
//        if(!$serie){
//            throw $this->createNotFoundException("Oops ! Serie not found !");
//        }

        return $this->render('party/detail.html.twig', [
            'sortie' => $sortie
        ]);
    }


}
