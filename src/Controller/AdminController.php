<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Participant;
use App\Entity\Place;
use App\Entity\Site;
use App\Form\AddParticipantType;
use App\Form\CityFilterType;
use App\Form\CityType;
use App\Form\ParticipantType;
use App\Form\PlaceType;
use App\Form\SiteFilterType;
use App\Form\SiteType;
use App\Repository\ActivityRepository;
use App\Repository\CityRepository;
use App\Repository\ParticipantRepository;
use App\Repository\PlaceRepository;
use App\Repository\SiteRepository;
use App\Service\MailService;
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
use Symfony\Component\String\ByteString;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    public function __construct(private readonly SiteRepository $siteRepository,
                                private readonly CityRepository $cityRepository,
                                private readonly AdminService $adminService,
                                private readonly PlaceRepository $placeRepository,
                                private readonly ParticipantRepository $participantRepository,
                                private readonly ActivityRepository $activityRepository,
                                private readonly MailService $mailService)
    {
    }

    #[Route('/add', name: 'add')]
    public function add(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $participant = new Participant();

        $participantForm = $this->createForm(AddParticipantType::class, $participant);
        $participantForm->handleRequest($request);

        if($participantForm->isSubmitted() && $participantForm->isValid() ){
            $generatedPassword = ByteString::fromRandom(32)->toString();

            $participant->setRoles(["ROLE_USER"]);
            $participant->setActive(1);
            $participant->setPassword($passwordHasher->hashPassword($participant, $generatedPassword));

            $this->participantRepository->save($participant, true);

            // Send the creation mail to the participant with his password
            $this->mailService->sendMail($participant->getEmail(),
                'Creation de votre compte Sortir.com',
                'La cr??ation de votre compte Sortir.com a ??t?? effectu??, voici votre mot de passe : '. $generatedPassword
            );

            $this->addFlash('success', 'Le participant a bien ??t?? ajout?? !');
            return $this->redirectToRoute('activity_list');
        }

        return $this->render('admin/add_participant.html.twig', [
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
                $this->addFlash('success', 'Les nouveaux participants on ??t?? import??es avec succ??s');
            } catch (Exception $e) {
                $this->addFlash('warning', 'Echec de l\'import des participants : '. $e->getMessage());
            }
        }

        return $this->render('admin/import_participants.html.twig', [
            'importForm' => $importForm->createView()
        ]);
    }

    #[Route('/sites', name: 'sites')]
    public function get_sites(Request $request): Response
    {
        $filterForm = $this->createForm(SiteFilterType::class);
        $filterForm->handleRequest($request);

        $sites = $this->siteRepository->findAll();

        $siteParticipant = $this->participantRepository->findAll();

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $sites = $this->siteRepository->findByFilter( [$request->request->get('site_filter')]);
        }

        return $this->render('admin/sites.html.twig', [
            'sites' => $sites,
            'participant' => $siteParticipant,
            'filterForm' => $filterForm->createView()
        ]);
    }

    #[Route('/users', name: 'users')]
    public function get_users(): Response
    {
        $users = $this->participantRepository->findAll();
        $activities = $this->activityRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'activities' => $activities
        ]);
    }

    #[Route('/users/delete/{id}', name: 'participant_delete')]
    public function delete_user(int $id): RedirectResponse
    {
        $participant = $this->participantRepository->find($id);

        $this->participantRepository->remove($participant, true);

        $this->addFlash('success', 'Le participant a ??t?? supprim?? avec succ??s');

        return $this->redirectToRoute('admin_users');
    }


    #[Route('/sites/add', name: 'sites_add')]
    #[Route('/sites/edit/{id}', name: 'site_edit', requirements: ['id' => '\d+'])]
    public function addOrEdit_site(Request $request, $id = null): RedirectResponse|Response
    {
        if ($id) {
            $site = $this->siteRepository->find($id);
        } else {
            $site = new Site();
        }

        $siteForm = $this->createForm(SiteType::class, $site, ['data' => $site]);
        $siteForm->handleRequest($request);

        if ($siteForm->isSubmitted() && $siteForm->isValid()) {
            $this->siteRepository->save($site, true);

            $this->addFlash('success', 'Le site a bien ??t?? modifi?? !');
            return $this->redirectToRoute('admin_sites');
        }

        if ($id) {
            return $this->render('admin/edit.html.twig', [
                'siteForm' => $siteForm->createView(),
                'site' => $site
            ]);

        } else {
            return $this->render('admin/add_site.html.twig', [
                'siteForm' => $siteForm->createView()
            ]);
        }

    }

    #[Route('/sites/delete/{id}', name: 'site_delete')]
    public function delete_site(int $id): RedirectResponse
    {
        $site = $this->siteRepository->find($id);

        $this->siteRepository->remove($site, true);

        $this->addFlash('success', 'Le site a bien ??t?? supprim??');

        return $this->redirectToRoute('admin_sites');
    }

    #[Route('/cities', name: 'cities')]
    public function get_cities(Request $request): Response
    {
        $filterForm = $this->createForm(CityFilterType::class);
        $filterForm->handleRequest($request);

        $cities = $this->cityRepository->findAll();

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $cities = $this->cityRepository->findByFilter( [$request->request->get('city_filter')]);
        }

        return $this->render('admin/cities.html.twig', [
            'cities' => $cities,
            'filterForm' => $filterForm->createView()
        ]);
    }

    #[Route('/cities/add', name: 'add_city')]
    #[Route('/cities/edit/{id}', name: 'edit_city', requirements: ['id' => '\d+'])]
    public function addOrEdit_city(Request $request, $id = null): RedirectResponse|Response
    {
        if($id){
            $city = $this->cityRepository->find($id);
        } else {
            $city = new City();
        }

        $cityForm = $this->createForm(CityType::class, $city, ['data' => $city]);

        $cityForm->handleRequest($request);

        if ($cityForm->isSubmitted() && $cityForm->isValid()) {
            $this->cityRepository->save($city, true);

            $this->addFlash('success', 'Le lieu a bien ??t?? modifi?? !');
            return $this->redirectToRoute('admin_cities');
        }

        if ($id) {
            return $this->render('admin/edit_city.html.twig', [
                'cityForm' => $cityForm->createView(),
                'city' => $city
            ]);

        } else {
            return $this->render('admin/add_city.html.twig', [
                'cityForm' => $cityForm->createView()
            ]);
        }

    }

    #[Route('/cities/delete/{id}', name: 'delete_city')]
    public function delete_city(int $id): RedirectResponse
    {
        $city = $this->cityRepository->find($id);

        $places = $this->placeRepository->findBy(['city' => $city]);

        if($places == null){
            $this->cityRepository->remove($city, true);
            $this->addFlash('success', 'La ville a bien ??t?? supprim??e');
        } else {
            $this->addFlash('danger', 'La ville ne peut pas ??tre supprim??e. Elle est li??e ?? un ou plusieurs lieux.');
        }

        return $this->redirectToRoute('admin_cities');
    }


    #[Route('/places', name: 'places')]
    public function get_places(Request $request): Response
    {
        $filterForm = $this->createForm(CityFilterType::class);
        $filterForm->handleRequest($request);

        $places = $this->placeRepository->findAll();
        $activity = $this->activityRepository->findAll();
        $city = $this->cityRepository->findAll();

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $places = $this->placeRepository->findByFilter([$request->request->get('city_filter')]);
        }

        return $this->render('admin/places.html.twig', [
            'places' => $places,
            'activity' => $activity,
            'city' => $city,
            'filterForm' => $filterForm->createView()
        ]);
    }

    #[Route('/places/add', name: 'add_place')]
    #[Route('/places/edit/{id}', name: 'edit_place', requirements: ['id' => '\d+'])]
    public function addOrEdit_place(Request $request, $id = null): RedirectResponse|Response
    {
        if ($id) {
            $place = $this->placeRepository->find($id);
        } else {
            $place = new Place();
        }

        $placeForm = $this->createForm(PlaceType::class, $place, ['data' => $place]);
        $placeForm->handleRequest($request);

        if ($placeForm->isSubmitted() && $placeForm->isValid()) {
            $this->placeRepository->save($place, true);

            $this->addFlash('success', 'Le lieu a bien ??t?? modifi?? !');
            return $this->redirectToRoute('admin_places');
        }

        if ($id) {
            return $this->render('admin/edit_place.html.twig', [
                'placeForm' => $placeForm->createView(),
                'place' => $place
            ]);

        } else {
            return $this->render('admin/add_place.html.twig', [
                'placeForm' => $placeForm->createView()
            ]);
        }

    }

    #[Route('/places/delete/{id}', name: 'delete_place')]
    public function delete_place(int $id): RedirectResponse
    {
        $place = $this->placeRepository->find($id);

        $this->placeRepository->remove($place, true);

        $this->addFlash('success', 'Le lieu a bien ??t?? supprim??');

        return $this->redirectToRoute('admin_places');
    }


    #[Route('/users/setActive/{id}', name: 'users_setActive', requirements: ['id' =>'\d+'])]
    public function SetParticipantActive(int $id): Response
    {
        $participant = $this->participantRepository->find($id);

        if($participant->isActive()){
            $participant->setActive(false);
            $participant->setRoles(["ROLE_INACTIVE"]);
        } else {
            $participant->setActive(true);
            $participant->setRoles(["ROLE_USER"]);
        }

        $this->participantRepository->save($participant, true);

        $this->addFlash('success', 'L\'utilisateur a bien ??t?? modifi??');
        return $this->redirectToRoute('admin_users');
    }

}
