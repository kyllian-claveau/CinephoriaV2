<?php

namespace App\Controller;

use App\Entity\Reparation;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class APIController extends AbstractController
{
    private $jwtManager;

    private $encoderInterface;
    private $entityManager;
    private $security;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        EntityManagerInterface $entityManager,
        Security $security,
        JWTEncoderInterface $encoderInterface
    )
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->jwtManager = $jwtManager;
        $this->encoderInterface = $encoderInterface;
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

    #[Route('/api/rooms', name: 'api_get_rooms', methods: ['GET'])]
    public function getRooms(): JsonResponse
    {
        // Récupérer toutes les salles
        $rooms = $this->entityManager->getRepository(Room::class)->findAll();

        // Si aucune salle n'est trouvée
        if (!$rooms) {
            return new JsonResponse(['message' => 'No rooms found.'], 404);
        }

        // Préparer les données des salles
        $roomsData = [];
        foreach ($rooms as $room) {
            $roomsData[] = [
                'id' => $room->getId(),
                'number' => $room->getNumber(),
                'quality' => $room->getQuality(),
            ];
        }

        // Retourner la liste des salles
        return new JsonResponse($roomsData, 200);
    }

    #[Route('/api/repair', name: 'api_create_repair', methods: ['POST'])]
    public function createRepair(Request $request): JsonResponse
    {
        // Vérification du token
        $content = json_decode($request->getContent(), true);
        $token = $content['token'] ?? null;

        if (!$token) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        try {
            $decodedToken = $this->encoderInterface->decode($token);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid token.'], 401);
        }

        if (!$decodedToken) {
            return new JsonResponse(['message' => 'Invalid token.'], 401);
        }

        $userId = $decodedToken['id'];

        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        $roomId = $content['room_id'] ?? null;
        if (!$roomId) {
            return new JsonResponse(['message' => 'Room ID is required.'], 400);
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            return new JsonResponse(['message' => 'Room not found.'], 404);
        }

        // Récupérer les données de la réparation
        $description = $content['description'] ?? null;
        $statut = $content['statut'] ?? null;

        if (!$description || !$statut) {
            return new JsonResponse(['message' => 'Missing required fields.'], 400);
        }

        // Créer la nouvelle réparation
        $reparation = new Reparation();
        $reparation->setRoom($room);
        $reparation->setDescription($description);
        $reparation->setStatut($statut);

        // Enregistrer la réparation dans la base de données
        $this->entityManager->persist($reparation);
        $this->entityManager->flush();

        // Retourner une réponse avec les informations de la réparation créée
        return new JsonResponse([
            'message' => 'Repair created successfully.',
            'repair' => [
                'id' => $reparation->getId(),
                'room_id' => $room->getId(),
                'description' => $reparation->getDescription(),
                'statut' => $reparation->getStatut(),
                'date_creation' => $reparation->getDateCreation()->format('Y-m-d H:i:s'),
                'date_reparation' => $reparation->getDateReparation() ? $reparation->getDateReparation()->format('Y-m-d H:i:s') : null,
            ]
        ], 201);
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
            $decodedToken = $this->encoderInterface->decode($token);
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
                'url' => $reservation->getQrCodeUrl(),
                'start_date' => $reservation->getSession()->getStartDate()->format('Y-m-d'),
                'end_date' => $reservation->getSession()->getEndDate()->format('Y-m-d')
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