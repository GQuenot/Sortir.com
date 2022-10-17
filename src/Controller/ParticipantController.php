<?php

namespace App\Controller;

use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/profile', name: 'participant_')]
class ParticipantController extends AbstractController
{
    #[Route('/', name: 'profile')]
    public function profile(Request $request, ParticipantRepository $participantRepository): Response
    {
        $participant = $participantRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $participantForm = $this->createForm(ParticipantType::class, $participant);

        $participantForm->handleRequest($request);

        if ($participantForm->isSubmitted() && $participantForm->isValid()) {

            $participantForm->add($participant, true);

            $this->addFlash('success', 'Your profile has been updated.');

            return $this->redirectToRoute('participant_profile');
        }

        return $this->render('user/profile.html.twig', [
            'participantForm' => $participantForm
        ]);
    }
}