<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Participant;
use App\Entity\Site;
use App\Form\CityType;
use App\Form\ParticipantType;
use App\Form\PlaceType;
use App\Form\SiteType;
use App\Repository\ActivityRepository;
use App\Repository\CityRepository;
use App\Repository\ParticipantRepository;
use App\Repository\PlaceRepository;
use App\Repository\SiteRepository;
use App\Repository\StateRepository;
use App\Services\ActivityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{

    public function __construct(private readonly SiteRepository $siteRepository,
                                private readonly PlaceRepository $placeRepository,
                                private readonly CityRepository $cityRepository)
    {
    }

    #[Route('/add', name: 'add')]
    public function add(Request $request,EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, ParticipantRepository $participantRepository, SiteRepository $siteRepository): Response
    {

        $participant = new Participant();
        $participantForm = $this->createForm(ParticipantType::class, $participant);

        $participantForm->handleRequest($request);

        if($participantForm->isSubmitted() && $participantForm->isValid() ){

            $participant->setRoles(["ROLE_USER"]);
            $participant->setActive(1);

            $participantRepository->upgradePassword($participant, $passwordHasher->hashPassword($participant, $request->request->get('participant')['plainPassword']['first']));

            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('sucess', 'Le participant a bien été ajouté !');
            return $this->redirectToRoute('activity_list');
        }

        return $this->render('user/add.html.twig', [
            'participantForm' => $participantForm->createView()
        ]);
    }

    #[Route('/sites', name: 'sites')]
    public function get_sites(){

        $sites = $this->siteRepository->findAll();
//        $sitesCantBeDeleted = $this->siteRepository->findSitesToNotDeleted($sites);

        return $this->render('admin/sites.html.twig', [
            'sites' => $sites,
//            'sitesCantBeDeleted' => $sitesCantBeDeleted
        ]);
    }

    #[Route('/sites/add', name: 'sites_add')]
    public function add_site(Request $request, EntityManagerInterface $entityManager){

        $site = new Site();
        $siteForm = $this->createForm(SiteType::class, $site);

        $siteForm->handleRequest($request);

        if($siteForm->isSubmitted() && $siteForm->isValid() ) {

            $entityManager->persist($site);
            $entityManager->flush();

            $this->addFlash('sucess', 'Le site a bien été ajouté !');
            return $this->redirectToRoute('admin_sites');
        }

        return $this->render('admin/add_site.html.twig', [
            'siteForm' => $siteForm->createView()
        ]);

    }

    #[Route('/sites/edit/{id}', name: 'site_edit', requirements: ['id' => '\d+'])]
    public function edit_site(int $id, Request $request, EntityManagerInterface $entityManager){

        $site = $this->siteRepository->find($id);
        $siteForm = $this->createForm(SiteType::class, $site);

        $siteForm->handleRequest($request);

        if ($siteForm->isSubmitted() && $siteForm->isValid()) {

            $entityManager->persist($site);
            $entityManager->flush();

            $this->addFlash('sucess', 'Le site a bien été modifié !');
            return $this->redirectToRoute('admin_sites');
        }

        return $this->render('admin/edit.html.twig', [
            'siteForm' => $siteForm->createView(),
            'site' => $site
        ]);

    }

    #[Route('/sites/delete/{id}', name: 'site_delete')]
    public function delete_site(int $id): RedirectResponse
    {
        $site = $this->siteRepository->find($id);

        $this->siteRepository->remove($site, true);

        $this->addFlash('success', 'Le site a bien été supprimé');

        return $this->redirectToRoute('admin_sites');
    }

    #[Route('/places', name: 'places')]
    public function get_places(){

        $places = $this->cityRepository->findAll();

        return $this->render('admin/places.html.twig', [
            'places' => $places,
        ]);
    }

    #[Route('/places/edit/{id}', name: 'edit_place', requirements: ['id' => '\d+'])]
    public function edit_place(int $id, Request $request, EntityManagerInterface $entityManager){

        $place = $this->cityRepository->find($id);
        $placeForm = $this->createForm(CityType::class, $place);

        $placeForm->handleRequest($request);

        if ($placeForm->isSubmitted() && $placeForm->isValid()) {

            $entityManager->persist($place);
            $entityManager->flush();

            $this->addFlash('sucess', 'Le lieu a bien été modifié !');
            return $this->redirectToRoute('admin_places');
        }

        return $this->render('admin/edit_place.html.twig', [
            'placeForm' => $placeForm->createView(),
            'place' => $place
        ]);

    }

    #[Route('/places/delete/{id}', name: 'place_delete')]
    public function place_delete(int $id): RedirectResponse
    {
        $place = $this->cityRepository->find($id);

        $this->cityRepository->remove($place, true);

        $this->addFlash('success', 'Le lieu a bien été supprimé');

        return $this->redirectToRoute('admin_places');
    }

    #[Route('/places/add', name: 'places_add')]
    public function add_place(Request $request, EntityManagerInterface $entityManager){

        $place = new City();
        $placeForm = $this->createForm(CityType::class, $place);

        $placeForm->handleRequest($request);

        if($placeForm->isSubmitted() && $placeForm->isValid() ) {

            $entityManager->persist($place);
            $entityManager->flush();

            $this->addFlash('sucess', 'Le lieu a bien été ajouté !');
            return $this->redirectToRoute('admin_places');
        }

        return $this->render('admin/add_place.html.twig', [
            'placeForm' => $placeForm->createView()
        ]);

    }

}
