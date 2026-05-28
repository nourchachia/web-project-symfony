<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\PendingRegistrationRepository;
use App\Service\ApprovalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ApprovalController extends AbstractController
{
    #[Route('/approvals', name: 'app_approvals', methods: ['GET'])]
    public function index(PendingRegistrationRepository $repo): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_access_denied');
        }

        return $this->render('approvals/index.html.twig', [
            'rows' => $repo->findAll(),
        ]);
    }

    #[Route('/approvals/handle', name: 'app_approvals_handle', methods: ['POST'])]
    public function handle(Request $request, ApprovalService $approvalService): JsonResponse
    {
        $data   = json_decode($request->getContent(), true) ?? [];
        $id     = $data['id'] ?? null;
        $action = $data['action'] ?? null;

        if (!$id || !$action) {
            return $this->json(['success' => false, 'message' => 'Missing data'], 400);
        }

        try {
            $result = match($action) {
                'accept'  => $approvalService->accept($id),
                'decline' => $approvalService->decline($id),
                default   => ['success' => false, 'message' => 'Invalid action'],
            };
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return $this->json($result);
    }
}