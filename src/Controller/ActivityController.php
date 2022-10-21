<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Form\ActivityType;
use App\Repository\ActivityRepository;
use App\Repository\StateRepository;
use App\Repository\ParticipantRepository;
use App\Services\ActivityService;
use Exception;
use Doctrine\ORM\EntityManagerInterface;
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
                                private readonly ActivityRepository $activityRepository,
                                private readonly ParticipantRepository $participantRepository,
                                private readonly EntityManagerInterface $entityManager,
                                private readonly StateRepository        $stateRepository)
    {
    }

    #[Route('/activity/add', name: 'activity_add')]
    public function add(Request $request): Response
    {
        $activity = new activity();

        try {
            $activityForm = $this->saveActivity($activity, $request);
        } catch (Exception $e) {
            $this->addFlash('warning', 'impossible d\'enregistrer l\'activité :' . $e->getMessage());
            return $this->redirectToRoute('activity_list');
        }

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

        try {
            $activityForm = $this->saveActivity($activity, $request);
        } catch (Exception $e) {
            $this->addFlash('warning', 'impossible d\'enregistrer l\'activité :' . $e->getMessage());
            return $this->redirectToRoute('activity_list');
        }

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
     * @throws Exception
     */
    public function saveActivity(activity $activity, Request $request) : FormInterface
    {
        $activityForm = $this->createForm(activityType::class, $activity);

        $activityForm->handleRequest($request);

        if ($activityForm->isSubmitted() && $activityForm->isValid()) {
            $this->activityService->saveActivity($activity, $activityForm->get('publish')->isClicked());
            $this->addFlash('success', 'L\'activité a été enregistrée avec succès');
        }

        return $activityForm;
    }

    #[Route('activity/publish/{activityId}', name: 'activity_publish')]
    public function publish(int $activityId): Response
    {
        $activity = $this->activityRepository->find($activityId);

        if($activity->getState()->getLabel() != $this->getParameter('app.states')['created']) {
            $this->addFlash('warning', 'L\'activité a déja été publiée');
        }

        $this->activityService->publish($activity);
        $this->addFlash('success', 'L\'activité a été publiée avec succès');

        return $this->redirectToRoute('activity_list');
    }

    #[Route('activity/delete/{activityId}', name: 'activity_delete')]
    public function delete(int $activityId): RedirectResponse
    {
        $activity = $this->activityRepository->find($activityId);

        $this->activityRepository->remove($activity, true);

        $this->addFlash('success', 'La sortie a bien été supprimée');

        return $this->redirectToRoute('activity_list');
    }

    #[Route('/', name: 'activity_list')]
    public function list(): Response
    {
        $activities = $this->activityRepository->findActivityNotArchived();

        $activitiesStarted = $this->activityRepository->findActivitiesStarted();
        $stateA = $this->stateRepository->findOneBy(['label' => 'Activité en cours']);

        if ($activities == $activitiesStarted){
            return $this->redirectToRoute('activity_list');
        }

        foreach ($activitiesStarted as $activityStarted) {
            $activityStarted->setState($stateA);
            $this->entityManager->persist($activityStarted);
        }

        $this->entityManager->flush();

        $activitiesPassed = $this->activityRepository->findActivitiesPassed();

        $stateP = $this->stateRepository->findOneBy(['label' => 'Passée']);

        if ($activities == $activitiesPassed){
            return $this->redirectToRoute('activity_list');
        }

        foreach ($activitiesPassed as $activityPassed) {
            $activityPassed->setState($stateP);
            $this->entityManager->persist($activityPassed);
        }

        $this->entityManager->flush();

        return $this->render('activity/list.html.twig', [
            'activities' => $activities,
        ]);
    }

    #[Route('activity/subscription/{activityId}', name: 'activity_subscription')]
    public function subscription(int $activityId): Response
    {
        $activity = $this->activityRepository->find($activityId);

        try {
            $this->activityService->CloseSubscription($activity);

            if($activity->getState()->getLabel() !== $this->getParameter('app.states')['open']) {
                $this->addFlash('warning', 'Echec de l\'inscription : Les inscriptions à cette activité ne sont plus ouverte');
                return $this->redirectToRoute('activity_list');
            }

            $this->activityService->addParticipant($activity);
            $this->addFlash('success', 'Inscription effectée avec succès');
        } catch (Exception $e) {
            $this->addFlash('warning', 'Echec de l\'inscription : ' . $e->getMessage());
        }

        return $this->redirectToRoute('activity_list');
    }

    #[Route('activity/unsubscribe/{activityId}', name: 'activity_unsubscribe')]
    public function unsubscribe(int $activityId): Response
    {
        $activity = $this->activityRepository->find($activityId);

        try {
            $this->activityService->removeParticipant($activity);
            $this->addFlash('success', 'desistement effectué avec succès');
        } catch (Exception $e) {
            $this->addFlash('warning', 'Echec du desistement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('activity_list');
    }

    #[Route('/activity/detail/{id}', name: 'activity_detail', requirements: ['id' => '\d+'])]
    #[ParamConverter('activity', class: 'App\Entity\Activity')]
    public function show(Activity $activity): Response
    {
        return $this->render('activity/detail.html.twig', [
            'activity' => $activity
        ]);
    }

    #[Route('/activity/cancel/{id}', name: 'activity_cancel', requirements: ['id' =>'\d+'])]
    public function cancelActivity(ActivityRepository $activityRepository, int $id): Response
    {
        $activity = $activityRepository->find($id);
        $state = $this->stateRepository->findOneBy(['label' => 'Annulée']);

        $today = new \DateTime();

        if($activity->getActivityDate() <= $today){
            $this->addFlash('warning', 'Une sortie commencée ne peut être annulée');
            return $this->redirectToRoute('activity_list');
        }
        $activity->setState($state);
        $this->entityManager->persist($activity);
        $this->entityManager->flush();

        $this->addFlash('success', 'Sortie annulée avec succès');
        return $this->redirectToRoute('activity_list');
    }


}
