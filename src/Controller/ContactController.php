<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = (new Email())
                ->from('no-reply@cinephoria.com')
                ->to('kyllian.claveau@gmail.com')
                ->subject('Demande de contact - ' . $data['title'])
                ->html(
                    $this->renderView('emails/contact.html.twig', [
                        'username' => $data['username'] ?? 'Anonyme',
                        'title' => $data['title'],
                        'description' => $data['description']
                    ])
                );

            $mailer->send($email);
            $this->addFlash('success', 'Votre demande a été envoyée avec succès !');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
