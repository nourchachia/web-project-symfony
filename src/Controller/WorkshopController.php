<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class WorkshopController extends AbstractController
{
    #[Route('/workshops', name: 'app_workshop_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('workshop/index.html.twig');
    }
}
