<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Form\ActivityType;
use App\Repository\ActivityRepository;
use App\Services\ActivityService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActivityController extends AbstractController
{

    public function __construct(private readonly ActivityService    $activityService,
                                private readonly ActivityRepository $activityRepository)
    {
    }

    #[Route('/activity/add', name: 'activity_add')]
    public function add(Request $request): Response
    {
        $activity = new activity();
        $activityForm = $this->saveActivity($activity, $request);

        if ($activityForm->isSubmitted() && $activityForm->isValid()) {
            return $this->redirectToRoute('activity_list');
        }

        return $this->render('activity/add.html.twig', [
            'activityForm' => $activityForm->createView()
        ]);
    }

    #[Route('/activity/edit/{id}', name: 'activity_edit')]
    public function edit(int $id, Request $request): Response
    {
        $activity = $this->activityRepository->find($id);

        if($activity->getState()->getLabel() != $this->getParameter('app.states')['created']) {
            $this->addFlash('warning', 'Une demande déja publiée ne peut être modifiée');
            return $this->redirectToRoute('activity_list');
        }

        $activityForm = $this->saveActivity($activity, $request);

        if ($activityForm->isSubmitted() && $activityForm->isValid()) {
            return $this->redirectToRoute('activity_list');
        }

        return $this->render('activity/edit.html.twig', [
            'activityForm' => $activityForm->createView(),
            'activity' => $activity
        ]);
    }

    /**
     * @param activity $activity
     * @param Request $request
     * @return FormInterface
     */
    public function saveActivity(activity $activity, Request $request) : FormInterface
    {
        $activityForm = $this->createForm(activityType::class, $activity);

        $activityForm->handleRequest($request);

        if ($activityForm->isSubmitted() && $activityForm->isValid()) {
            $response = $this->activityService->saveActivity($activity, $activityForm->get('publish')->isClicked());
            $this->addFlash($response['code'], $response['message']);
        }

        return $activityForm;
    }

    #[Route('activity/publish/{activityId}', name: 'activity_publish')]
    public function publish(int $activityId): Response
    {
        $activity = $this->activityRepository->find($activityId);

        if($activity->getState()->getLabel() != $this->getParameter('app.states')['created']) {
            $this->addFlash('warning', 'La activity à déja été publiée');
        }

        $response = $this->activityService->publish($activity);
        $this->addFlash($response['code'], $response['message']);

        return $this->redirectToRoute('activity_list');
    }

    #[Route('activity//delete/{activityId}', name: 'activity_delete')]
    public function delete(int $activityId): RedirectResponse
    {
        $activity = $this->activityRepository->find($activityId);

        $this->activityRepository->remove($activity, true);

        $this->addFlash('success', 'la activity a bien été supprimée');

        return $this->redirectToRoute('activity_list');
    }

    #[Route('/', name: 'activity_list')]
    public function list(): Response
    {
        $activities = $this->activityRepository->findAll();

        return $this->render('activity/list.html.twig', [
            'activities' => $activities,
        ]);
    }

    #[Route('activity/subscription/{activityId}', name: 'activity_subscription')]
    public function subscription(int $activityId): Response
    {
        $activity = $this->activityRepository->find($activityId);

        // If the subscription limit date is hit, participant can't sub to activity and the activity is set to closed
        if(date('d-m-y h:i:s') > $activity->getSubLimitDate()) {
            $this->activityService->CloseSubscription($activity);
        }

        if($activity->getState()->getLabel() !== $this->getParameter('app.states')['open']) {
            $this->addFlash('warning', 'Les inscriptions à cette activity ne sont plus ouverte');
            return $this->redirectToRoute('activity_list');
        }

        $response = $this->activityService->addParticipant($activity);
        $this->addFlash($response['code'], $response['message']);

        return $this->redirectToRoute('activity_list');
    }

    #[Route('/activity/detail/{id}', name: 'activity_detail', requirements: ['id' => '\d+'])]
    #[ParamConverter('activity', class: 'App\Entity\Activity')]
    public function show(Activity $activity): Response
    {

//        //sans paramConverter
//        $activity = $this->activityRepository->find($id);//
//        if(!$serie){
//            throw $this->createNotFoundException("Oops ! Serie not found !");
//        }

        return $this->render('activity/detail.html.twig', [
            'activity' => $activity
        ]);
    }


}
