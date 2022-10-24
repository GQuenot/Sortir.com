<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Participant;
use App\Entity\Site;
use App\Form\CityFilterType;
use App\Form\CityType;
use App\Form\ParticipantType;
use App\Form\SiteFilterType;
use App\Form\SiteType;
use App\Repository\CityRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use App\Service\ActivityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ImportParticipantType;
use App\Service\AdminService;
use Exception;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{

    public function __construct(private readonly SiteRepository $siteRepository,
                                private readonly CityRepository $cityRepository,
                                private readonly AdminService $adminService,
                                private readonly ParticipantRepository $participantRepository)
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

    #[Route('/import', name: 'import')]
    public function import(Request $request): Response
    {
        $importForm = $this->createForm(ImportParticipantType::class);

        $importForm->handleRequest($request);

        if ($importForm->isSubmitted() && $importForm->isValid()) {
            $participantsCsv = $importForm->get('importParticipant')->getData();

            try {
                $this->adminService->importParticipants($participantsCsv);
                $this->addFlash('success', 'Les nouveaux participants on été importées avec succès');
            } catch (Exception $e) {
                $this->addFlash('warning', 'Echec de l\'import des participants : '. $e->getMessage());
            }
        }

        return $this->render('admin/importParticipants.html.twig', [
            'importForm' => $importForm->createView()
        ]);
    }

    #[Route('/sites', name: 'sites')]
    public function get_sites(Request $request){

        $filterForm = $this->createForm(SiteFilterType::class);

        $filterForm->handleRequest($request);

        $sites = $this->siteRepository->findAll();

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $sites = $this->siteRepository->findByFilter( [$request->request->get('site_filter')]);
        }

        return $this->render('admin/sites.html.twig', [
            'sites' => $sites,
            'filterForm' => $filterForm->createView()
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

    #[Route('/users', name: 'users')]
    public function get_users(Request $request, ){

        $users = $this->participantRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
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
    public function get_places(Request $request){

        $filterForm = $this->createForm(CityFilterType::class);

        $filterForm->handleRequest($request);

        $places = $this->cityRepository->findAll();

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $places = $this->cityRepository->findByFilter( [$request->request->get('city_filter')]);
        }

        return $this->render('admin/places.html.twig', [
            'places' => $places,
            'filterForm' => $filterForm->createView()
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
