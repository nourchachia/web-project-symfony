<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class PasswordResetController extends AbstractController
{
    public function __construct(
        private readonly bool $showResetCode,
    ) {
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, PasswordResetService $passwordResetService): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $resetCode = $passwordResetService->requestResetCode((string) $form->get('email')->getData());
                $this->addFlash('success', 'If an account exists for that email, a reset code has been sent.');

                if ($this->showResetCode && $resetCode !== null) {
                    $this->addFlash('warning', 'Local reset code: '.$resetCode);
                }

                return $this->redirectToRoute('app_reset_password');
            } catch (TransportExceptionInterface) {
                $this->addFlash('danger', 'The reset email could not be sent right now. Please try again later.');
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'form' => $form->createView(),
        ], new Response(
            status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK
        ));
    }

    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, PasswordResetService $passwordResetService): Response
    {
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reset = $passwordResetService->resetPassword(
                (string) $form->get('email')->getData(),
                (string) $form->get('code')->getData(),
                (string) $form->get('plainPassword')->getData()
            );

            if ($reset) {
                $this->addFlash('success', 'Your password has been updated. You can now sign in.');

                return $this->redirectToRoute('app_login');
            }

            $form->addError(new FormError('Invalid or expired reset code. Please request a new one.'));
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
        ], new Response(
            status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK
        ));
    }
}
