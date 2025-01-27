<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class APIController
{
    private $jwtManager;
    private $entityManager;
    private $security;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        EntityManagerInterface $entityManager,
        Security $security
    )
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
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

    #[Route('/api/user/{id}/reservations', name: 'api_user_reservation_list', methods: ['POST'])]
    public function list(int $id, Request $request, UserRepository $userRepository): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        $token = $content['token'] ?? null;

        if (!$token) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        try {
            $decodedToken = $this->jwtManager->decode($token);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid token.'], 401);
        }

        if (!$decodedToken) {
            return new JsonResponse(['message' => 'Invalid token.'], 401);
        }

        $userId = $decodedToken['id'];

        $user = $userRepository->find($userId);

        if (!$user) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        if ($user->getId() !== $id) {
            return new JsonResponse(['message' => 'You do not have access to this resource.'], 403);
        }

        $reservations = $this->entityManager->getRepository(Reservation::class)->findBy(['user' => $id]);

        $reservationData = [];
        foreach ($reservations as $reservation) {
            $reservationData[] = [
                'id' => $reservation->getId(),
                'user_id' => $reservation->getUser()->getId(),
                'user_firstname' => $reservation->getUser()->getFirstname(),
                'user_lastname' => $reservation->getUser()->getLastname(),
                'total_price' => $reservation->getTotalPrice(),
                'film' => $reservation->getSession()->getFilm()->getTitle(),
                'room' => $reservation->getSession()->getRoom()->getNumber(),
                'cinema' => $reservation->getSession()->getCinema()->getName(),
                'start_date' => $reservation->getSession()->getStartDate()->format('Y-m-d'),
                'end_date' => $reservation->getSession()->getEndDate() ? $reservation->getEndDate()->format('Y-m-d') : null,
            ];
        }
        return new JsonResponse($reservationData, 200);
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