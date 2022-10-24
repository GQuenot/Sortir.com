<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/profile', name: 'participant_')]
class ParticipantController extends AbstractController
{

    public function __construct(private readonly EntityManagerInterface      $entityManager,
                                private readonly UserPasswordHasherInterface $passwordHasher,
                                private readonly ParticipantRepository $participantRepository,
                                private readonly FileUploader $fileUploader)
    {}

    #[Route('/', name: 'profile')]
    public function profile(Request $request): Response
    {
        $participant = $this->participantRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);

        $participantForm = $this->createForm(ParticipantType::class, $participant);

        $participantForm->handleRequest($request);

        if ($participantForm->isSubmitted() && $participantForm->isValid()) {
            if ($request->request->get('participant')['plainPassword']['first'] !== '') {
                $this->participantRepository->upgradePassword($participant, $this->passwordHasher->hashPassword($participant, $request->request->get('participant')['plainPassword']['first']));
            }

            $this->entityManager->persist($participant);
            $this->entityManager->flush();

            // Upload the profile picture
            $profilePicture = $participantForm->get('profilePicture')->getData();
            if ($profilePicture) {
                $this->fileUploader->upload($profilePicture, $participant->getId() . '.jpg');
                $this->addFlash('success', 'L\'image a été enregistrée avec succès');
            }

            $this->addFlash('success', 'Votre profile a été mis à jour.');

            return $this->redirectToRoute('participant_profile');
        }

        return $this->render('user/profile.html.twig', [
            'participantForm' => $participantForm->createView()
        ]);
    }

    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    #[ParamConverter('participant', class: 'App\Entity\Participant')]
    public function show(Participant $participant): Response
    {

        return $this->render('user/detail.html.twig', [
            'participant' => $participant
        ]);
    }


}