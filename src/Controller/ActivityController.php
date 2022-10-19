<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Form\ActivityType;
use App\Repository\ActivityRepository;
use App\Repository\ParticipantRepository;
use App\Services\ActivityService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/activity', name: 'activity_')]
class ActivityController extends AbstractController
{

    public function __construct(private ActivityService $activityService,
                                private ActivityRepository $activityRepository,
                                private ParticipantRepository $participantRepository,
                                private EntityManagerInterface $entityManager)
    {}

    #[Route('/add', name: 'add')]
    public function add(Request $request): Response
    {
        $activity = new Activity();
        return $this->saveParty($request, $activity);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(Request $request, int $id): Response
    {
        $activity = $this->activityRepository->find($id);

        if ($activity->getState()->getLabel() != $this->getParameter('app.states')['created']) {

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

            return $this->redirectToRoute('activity_add');
        }

        return $this->render('activity/add.html.twig', [
            'activityForm' => $activityForm->createView()
        ]);
    }

    #[Route('/list', name: 'list')]
    public function list(): Response
    {
        $activities = $this->activityRepository->findAll();

        return $this->render('activity/list.html.twig', [
            'activities' => $activities,
        ]);
    }

    #[Route('/subscription/{partyId}', name: 'subscription')]
    public function subscription(int $activityId): Response
    {
        $activity = $this->activityRepository->find($activityId);

        // If the subscription limit date is hit, participant can't sub to party and the party is set to closed
        if(date('d-m-y h:i:s') > $activity->getSubLimitDate()) {
            $this->activityService->CloseSubscription($activity);
        }

        if ($activity->getState()->getLabel() !== $this->getParameter('app.states')['open']) {

            $this->addFlash('warning', 'Les inscriptions à cette sortie ne sont plus ouverte');
            return $this->redirectToRoute('activity_list');
        }

        // Add the participant to the party
        $participant = $this->participantRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        $activity->addParticipant($participant);

        $this->entityManager->persist($activity);
        $this->entityManager->flush();

        $this->addFlash('success', 'Inscription reussie');

        // If the max subscription is hit, the party is closed
        if($activity->getParticipants()->count() >= $activity->getPlaceLimit()) {
            $this->activityService->CloseSubscription($activity);
        }

        return $this->redirectToRoute('activity_list');
    }

    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    #[ParamConverter('activity', class: 'App\Entity\Activity')]
    public function show(Activity $activity): Response
    {
        return $this->render('activity/detail.html.twig', [
            'sortie' => $activity
        ]);
    }


}
