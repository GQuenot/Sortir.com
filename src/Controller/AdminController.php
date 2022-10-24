<?php

namespace App\Controller;

use App\Form\ImportParticipantType;
use App\Service\AdminService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    public function __construct(private readonly AdminService $adminService)
    {}

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
}
