<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class APIController
{
    private $jwtManager;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
    )
    {
        $this->jwtManager = $jwtManager;
    }

    #[Route(path: '/api/login', name: 'api_login', methods: ['POST'])]
    public function apiLogin(Request $request, APIController $APIController, UserRepository $userRepository, AuthenticationUtils $authenticationUtils): JsonResponse
    {
        // Utilisation de la méthode pour obtenir l'utilisateur à partir du token
        $user = $APIController->getUserFromToken($request, $userRepository);

        if ($user instanceof User) {
            $token = $this->jwtManager->create($user);
            return new JsonResponse([
                'message' => 'Authentification réussie',
                'token' => $token
            ], JsonResponse::HTTP_OK);

        }

        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error instanceof AuthenticationException) {
            return new JsonResponse([
                'message' => 'Identifiants invalides',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'message' => 'Erreur lors de la connexion',
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    public function getUserFromToken(Request $request, UserRepository $userRepository)
    {
        $user = null;
        $token = $request->cookies->get('authToken');
        if ($token) {
            $payload = $this->decodeToken($token);
            if ($payload && isset($payload['id'])) {
                $user = $userRepository->find($payload['id']);
            }
        }
        if (!$user instanceof User) {
            $user = new User(); // Crée un utilisateur vide
            $user->setRoles(['']);
        }
        return $user;
    }

    private function decodeToken($token)
    {
        $parts = explode('.', $token);
        if (count($parts) != 3) {
            return null;
        }

        $payload = json_decode(base64_decode($parts[1]), true);

        if (!$payload) {
            return null;
        }

        // Check if token is expired
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }
}