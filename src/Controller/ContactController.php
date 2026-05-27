<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ContactType;
use App\Service\ContactMailer;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact_index', methods: ['GET', 'POST'])]
    public function index(Request $request, ContactMailer $contactMailer): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $contactMailer->send(
                    $data['fullname'],
                    $data['email'],
                    $data['message'],
                    new DateTimeImmutable()
                );

                $this->addFlash('success', 'Your message has been sent! We will get back to you soon.');

                return $this->redirectToRoute('app_contact_index');
            } catch (TransportExceptionInterface) {
                $this->addFlash('error', 'Your message could not be sent right now. Please try again later.');

                return $this->redirectToRoute('app_contact_index');
            }
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ], new Response(
            status: $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK
        ));
    }
}
