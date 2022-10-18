<?php

namespace App\Controller;

use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/profile', name: 'participant_')]
class ParticipantController extends AbstractController
{
    #[Route('/', name: 'profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ParticipantRepository $participantRepository, SiteRepository $siteRepository): Response
    {
        $participant = $participantRepository->findOneBy(['email' => 'nom.prenom@email.maileuh']);

        $participantForm = $this->createForm(ParticipantType::class, $participant);

        $participantForm->handleRequest($request);

        if ($participantForm->isSubmitted() && $participantForm->isValid()) {

            $participant->setPassword($passwordHasher->hashPassword($participant, $participant->getPassword()));

            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Your profile has been updated.');

            return $this->redirectToRoute('participant_profile');
        }

        return $this->render('user/profile.html.twig', [
            'participantForm' => $participantForm->createView()
        ]);
    }
}