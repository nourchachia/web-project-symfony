<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PendingRegistration;
use App\Entity\User;
use App\Form\PendingRegistrationType;
use App\Repository\PendingRegistrationRepository;
use App\Repository\UserRepository;
use App\Service\UploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/join-us', name: 'app_registration_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserRepository $userRepository,
        PendingRegistrationRepository $pendingRegistrationRepository,
        PasswordHasherFactoryInterface $passwordHasherFactory,
        UploadService $uploadService,
        EntityManagerInterface $entityManager,
    ): Response {
        $pendingRegistration = new PendingRegistration();
        $form = $this->createForm(PendingRegistrationType::class, $pendingRegistration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (string) $pendingRegistration->getEmail();

            if ($userRepository->emailExists($email)) {
                $form->get('email')->addError(new FormError('An account with this email address already exists.'));
            } elseif ($pendingRegistrationRepository->emailExists($email)) {
                $form->get('email')->addError(new FormError('An application with this email address is already pending review.'));
            } else {
                $plainPassword = (string) $form->get('plainPassword')->getData();
                $passwordHasher = $passwordHasherFactory->getPasswordHasher(User::class);
                $pendingRegistration->setPassword($passwordHasher->hash($plainPassword));

                $picture = $form->get('picture')->getData();
                if ($picture instanceof UploadedFile) {
                    $pendingRegistration->setPicture($uploadService->savePendingProfilePicture($picture));
                }

                $entityManager->persist($pendingRegistration);
                $entityManager->flush();

                $this->addFlash('success', 'Application submitted! Your registration is pending team approval.');

                return $this->redirectToRoute('app_registration_new');
            }
        }

        return $this->render('registration/new.html.twig', [
            'form' => $form->createView(),
        ], new Response(
            status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK
        ));
    }
}
