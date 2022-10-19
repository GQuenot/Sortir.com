<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Form\PartyType;
use App\Repository\ActivityRepository;
use App\Services\PartyService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activity', name: 'activity_')]
class ActivityController extends AbstractController
{
    public function __construct(private readonly PartyService $partyService)
    {}

    #[Route('/add', name: 'add')]
    public function add(Request $request): Response
    {
        $activity = new Activity();
        return $this->saveParty($activity, $request);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(int $id, Request $request, ActivityRepository $activityRepository): Response
    {
        $activity = $activityRepository->find($id);

        if ($activity->getState()->getLabel() != 'Créée') {

            $this->addFlash('warning', 'Une demande déja publiée ne peut être modifiée');
            return $this->redirectToRoute('activity_add');
        }

        return $this->saveParty($activity, $request);
    }

    /**
     * @param Activity $activity
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function saveParty(Activity $activity, Request $request): Response|RedirectResponse
    {
        $activityForm = $this->createForm(PartyType::class, $activity);

        $activityForm->handleRequest($request);

        if ($activityForm->isSubmitted() && $activityForm->isValid()) {
            $this->partyService->saveParty($activity, $activityForm->get('save')->isClicked());
            $this->addFlash('success', 'La sortie a bien été enregistrée.');

            return $this->redirectToRoute('activity_add');
        }

        return $this->render('party/add.html.twig', [
            'activityForm' => $activityForm->createView()
        ]);
    }

    #[Route('/list', name: 'list')]
    public function list(ActivityRepository $sortieRepository): Response
    {
        $sorties = $sortieRepository->findAll();

        return $this->render('party/list.html.twig', [
            'sorties' => $sorties,
        ]);
    }

    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    #[ParamConverter('sortie', class: 'Activity')]
    public function show(Activity $sortie): Response
    {
        return $this->render('party/detail.html.twig', [
            'sortie' => $sortie
        ]);
    }


}
