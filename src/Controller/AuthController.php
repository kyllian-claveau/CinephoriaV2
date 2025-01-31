<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\LoginType;
use App\Form\RegisterType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthController extends AbstractController
{
    private $mailer;
    private $userRepository;
    private $passwordHasher;

    private $security;

    public function __construct(
        MailerInterface $mailer,
        UserRepository $userRepository,
        Security $security,
        UserPasswordHasherInterface $passwordHasher
    )
    {
        $this->mailer = $mailer;
        $this->security = $security;
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordHasher;
    }

    #[Route(path:'/login', name: 'app_login')]
    public function login(){
        if ($this->security->getUser()) {
            return $this->redirectToRoute('app_index');
        }
        $loginForm = $this->createForm(LoginType::class);
        return $this->render('auth/login.html.twig', [
            'loginForm' => $loginForm->createView()
        ]);
    }

    #[Route(path: '/change-password', name: 'app_change_password')]
    public function changePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $user = $this->getUser();

        // Vérification que l'utilisateur est bien connecté
        if (!$user || !$user->getIsTemporaryPassword()) {
            return $this->redirectToRoute('app_login');
        }

        // Création du formulaire de changement de mot de passe
        $form = $this->createFormBuilder()
            ->add('new_password', PasswordType::class, [
                'label' => 'Nouveau mot de passe',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Changer le mot de passe',
                'attr' => ['class' => 'btn btn-primary'],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('new_password')->getData());
            $user->setPassword($hashedPassword);
            $user->setIsTemporaryPassword(false);
            $entityManager->flush();

            return $this->redirectToRoute('app_index');
        }

        return $this->render('auth/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): Response
    {
        $response = $this->redirectToRoute('app_index');
        $response->headers->clearCookie('authToken');
        return $response;
    }

    #[Route(path: '/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request                     $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface      $entityManager,
        MailerInterface             $mailer
    ): Response {
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé. Veuillez en choisir un autre.');
                return $this->render('auth/register.html.twig', [
                    'form' => $form->createView()
                ]);
            }

            try {
                $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
                $user->setPassword($hashedPassword);
                $user->setRoles(['ROLE_USER']);
                $user->setIsActive(false); // Account will remain inactive until confirmed

                $confirmationToken = bin2hex(random_bytes(32));
                $user->setConfirmationToken($confirmationToken);

                $entityManager->persist($user);
                $entityManager->flush();

                $confirmationUrl = $this->generateUrl(
                    'app_confirm_account',
                    ['token' => $confirmationToken],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $email = (new Email())
                    ->from('no-reply@votresite.com')
                    ->to($user->getEmail())
                    ->subject('Confirmez votre inscription')
                    ->html($this->renderView('emails/confirmation.html.twig', [
                        'user' => $user,
                        'confirmationUrl' => $confirmationUrl,
                    ]));

                $mailer->send($email);

                $this->addFlash('success', 'Un e-mail de confirmation a été envoyé à votre adresse.');

                return $this->redirectToRoute('app_login', ['confirmation_sent' => true]);

            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue. Veuillez réessayer plus tard.');
                return $this->render('auth/register.html.twig', [
                    'form' => $form->createView()
                ]);
            }
        }

        return $this->render('auth/register.html.twig', [
            'form' => $form->createView()
        ]);
    }



    #[Route(path: '/reset-password', name: 'app_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $email = $request->get('email');
        if (!$email) {
            return new JsonResponse(['message' => 'Email manquant'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['message' => 'Email invalide'], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }

        $temporaryPassword = bin2hex(random_bytes(5)); // 10 caractères de mot de passe temporaire
        $encodedPassword = $this->passwordHasher->hashPassword($user, $temporaryPassword);

        $user->setPassword($encodedPassword);
        $user->setIsTemporaryPassword(true);
        $entityManager->flush();

        $emailMessage = (new Email())
            ->from('noreply@votresite.com')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html("<p>Voici votre mot de passe temporaire : <strong>$temporaryPassword</strong></p>");

        try {
            $this->mailer->send($emailMessage);
            return new JsonResponse(['message' => 'Un mot de passe temporaire a été envoyé à votre email.']);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur lors de l\'envoi de l\'email.'], 500);
        }
    }

    #[Route('/confirm/{token}', name: 'app_confirm_account', methods: ['GET'])]
    public function confirmAccount(
        string                 $token,
        UserRepository         $userRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $userRepository->findOneBy(['confirmationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Token invalide ou utilisateur introuvable.');
            return $this->redirectToRoute('app_register');
        }

        $user->setConfirmationToken(null); // Remove the token
        $user->setIsActive(true); // Activate the account
        $entityManager->flush();

        $this->addFlash('success', 'Votre compte a été activé avec succès. Vous pouvez maintenant vous connecter.');

        return new JsonResponse(['message' => 'Compte activé !']);
    }
}