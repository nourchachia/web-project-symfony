<?php
namespace App\Controller;

use App\Service\MembersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/members')]
class MembersController extends AbstractController
{
    public function __construct(private MembersService $membersService) {}

    // ── Pages Twig ──────────────────────────────────────────────

    #[Route('/', name: 'members_list', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('members_list/members/index.html.twig');
    }

    #[Route('/profile/{id}', name: 'member_profile', methods: ['GET'])]
    public function profile(string $id): Response
    {
        $member = $this->membersService->getById($id);

        if (!$member) {
            throw $this->createNotFoundException();
        }

        return $this->render('members_list/profile/index.html.twig');
    }

    // ── API JSON ─────────────────────────────────────────────────

    #[Route('/api/list', name: 'members_api_list', methods: ['GET'])]
    public function apiList(): JsonResponse
    {
        $members = $this->membersService->getAll();

        $data = array_map(fn($m) => [
            'id'           => $m->getId(),
            'firstname'    => $m->getFirstname(),
            'lastname'     => $m->getLastname(),
            'department'   => $m->getDepartment(),
            'fieldofstudy' => $m->getFieldOfStudy(),
            'picture'      => $m->getPicture(),
        ], $members);

        return $this->json($data);
    }

    #[Route('/api/profile/{id}', name: 'members_api_profile', methods: ['GET'])]
    public function apiProfile(string $id): JsonResponse
    {
        $member = $this->membersService->getById($id);

        if (!$member) {
            return $this->json(['success' => false, 'message' => 'Member not found'], 404);
        }

        return $this->json([
            'id'           => $member->getId(),
            'firstname'    => $member->getFirstname(),
            'lastname'     => $member->getLastname(),
            'email'        => $member->getEmail(),
            'phone'        => $member->getPhone(),
            'birthdate'    => $member->getBirthdate()?->format('Y-m-d'),
            'department'   => $member->getDepartment(),
            'fieldofstudy' => $member->getFieldOfStudy(),
            'yearofstudy'  => $member->getYearOfStudy(),
            'picture'      => $member->getPicture(),
        ]);
    }

    #[Route('/api/filter', name: 'members_api_filter', methods: ['GET'])]
    public function apiFilter(Request $request): JsonResponse
    {
        $col = $request->query->get('filter-column', '');
        $val = $request->query->get('filter-value', '');

        $members = $this->membersService->getByFilter($col, $val);

        $data = array_map(fn($m) => [
            'id'           => $m->getId(),
            'firstname'    => $m->getFirstname(),
            'lastname'     => $m->getLastname(),
            'department'   => $m->getDepartment(),
            'fieldofstudy' => $m->getFieldOfStudy(),
            'picture'      => $m->getPicture(),
        ], $members);

        return $this->json($data);
    }

    #[Route('/api/delete/{id}', name: 'members_api_delete', methods: ['DELETE'])]
    public function apiDelete(string $id): JsonResponse
    {
        return $this->json($this->membersService->delete($id));
    }

    #[Route('/api/update/{id}', name: 'members_api_update', methods: ['POST'])]
    public function apiUpdate(string $id, Request $request): JsonResponse
    {
        return $this->json($this->membersService->update($id, $request->request->all()));
    }
}