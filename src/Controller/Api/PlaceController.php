<?php

namespace App\Controller\Api;

use App\Repository\PlaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/place', name: 'api_place_')]
class PlaceController extends AbstractController
{
    #[Route('/', name: 'get_all', methods: ['GET'])]
    public function getAll(PlaceRepository $lieuRepository): Response
    {
        $places = $lieuRepository->findAll();
        return $this->json($places, 200, [], ['groups' => 'place_group']);
    }

    #[Route('/{id}', name: 'get_place', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getPlace(PlaceRepository $lieuRepository, int $id): Response
    {
        $places = $lieuRepository->find($id);
        return $this->json($places, 200, [], ['groups' => 'place_group']);
    }
}
