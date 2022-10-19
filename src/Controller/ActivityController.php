<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Form\ActivityType;
use App\Repository\ActivityRepository;
use App\Services\ActivityService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activity', name: 'activity_')]
class ActivityController extends AbstractController
{
    public function __construct(private readonly ActivityService $activityService)
    {}

    #[Route('/add', name: 'add')]
    public function add(Request $request): Response
    {
        $activity = new Activity();
        return $this->saveParty($request, $activity);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(Request $request, ActivityRepository $activityRepository, int $id): Response
    {
        $activity = $activityRepository->find($id);

        if ($activity->getState()->getLabel() != 'Créée') {

            $this->addFlash('warning', 'Une demande déja publiée ne peut être modifiée');
            return $this->redirectToRoute('activity_add');
        }

        return $this->saveParty($request, $activity);
    }

    /**
     * @param Activity $activity
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function saveParty(Request $request, Activity $activity): Response|RedirectResponse
    {
        $activityForm = $this->createForm(ActivityType::class, $activity);

        $activityForm->handleRequest($request);

        if ($activityForm->isSubmitted() && $activityForm->isValid()) {

            $this->activityService->saveParty($activity, $activityForm->get('save')->isClicked());

            $this->addFlash('success', 'La sortie a bien été enregistrée.');

            return $this->redirectToRoute('activity_list');
        }

        return $this->render('activity/add.html.twig', [
            'activityForm' => $activityForm->createView()
        ]);
    }

    #[Route('/list', name: 'list')]
    public function list(ActivityRepository $sortieRepository): Response
    {
        $activities = $sortieRepository->findAll();

        return $this->render('activity/list.html.twig', [
            'activities' => $activities,
        ]);
    }

    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    #[ParamConverter('sortie', class: 'Activity')]
    public function show(Activity $activity): Response
    {
        return $this->render('activity/detail.html.twig', [
            'activity' => $activity
        ]);
    }


}
