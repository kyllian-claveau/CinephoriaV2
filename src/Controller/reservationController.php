<?php

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\Reservation;
use App\Entity\Session;
use App\Repository\SessionRepository;
use App\Service\FilmStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class reservationController extends AbstractController
{
    #[Route("/reservation", name: "app_reservation")]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cinemaId = $request->query->get('cinema');
        $numberOfPeople = $request->query->get('number_of_people', 1);

        $cinemaId = $cinemaId ? intval($cinemaId) : null;

        $sessionsFilter = $entityManager->getRepository(Session::class)
            ->findSessionsByCinemas($cinemaId);

        $filmsSessions = [];

        foreach ($sessionsFilter as $session) {
            $film = $session->getFilm();

            $room = $session->getRoom();
            $totalSeats = $room ? $room->getTotalSeats() : 0;

            $reservedSeats = $session->getReservedSeats();
            $reservedSeatsCount = count($reservedSeats);

            $availableSeats = $totalSeats - $reservedSeatsCount;

            if (!isset($filmsSessions[$film->getId()])) {
                $filmsSessions[$film->getId()] = [
                    'film' => $film,
                    'sessions' => [],
                ];
            }

            $filmsSessions[$film->getId()]['sessions'][] = $session;
        }

        // Récupération des cinémas
        $cinemas = $entityManager->getRepository(Cinema::class)->findAll();

        return $this->render('reservation.html.twig', [
            'filmsSessions' => $filmsSessions,
            'cinemas' => $cinemas,
            'number_of_people' => $numberOfPeople,
            'available_seats' => $availableSeats,
        ]);
    }

    #[Route('/reservation/confirm', name: 'app_confirm_reservation', methods: ['POST'])]
    public function confirmReservation(
        Request $request,
        EntityManagerInterface $em,
        SessionRepository $sessionRepository,
        FilmStatsService $statsService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Récupérer la session et la salle
        $session = $sessionRepository->find($data['sessionId']);

        // Vérifier si les sièges sont disponibles
        $reservedSeats = $session->getReservedSeats();
        $selectedSeats = $data['seats'];
        $conflictSeats = array_intersect($reservedSeats, $selectedSeats);

        if (!empty($conflictSeats)) {
            return new JsonResponse(['error' => 'Certaines places sont déjà réservées.'], 400);
        }

        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], 404);
        }

        $pricePerSeat = $session->getPrice(); // Prix d'une place pour cette séance
        $totalPrice = $pricePerSeat * count($selectedSeats);

        // Créer la réservation
        $reservation = new Reservation();
        $reservation->setSession($session);
        $reservation->setUser($user);
        $reservation->setSeats($selectedSeats);
        $reservation->setTotalPrice($totalPrice);

        // Sauvegarder la réservation
        $em->persist($reservation);

        // Mettre à jour la salle avec les sièges réservés
        $reservedSeats = array_merge($reservedSeats, $selectedSeats);
        $session->setReservedSeats($reservedSeats);
        $em->flush();

        $statsService->updateStatsForReservation($reservation);

        return new JsonResponse(['message' => 'Réservation confirmée.']);
    }

    #[Route("/reservation/{sessionId}", name: "app_seats_reservation")]
    public function sessionReservation(Request $request, int $sessionId, SessionRepository $sessionRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $session = $sessionRepository->find($sessionId);
        if (!$session) {
            throw $this->createNotFoundException('La séance n\'existe pas.');
        }

        $room = $session->getRoom();
        if (!$room) {
            throw $this->createNotFoundException('La salle de cinéma n\'est pas associée à cette séance.');
        }

        $stairs = $room->getStairs();
        $accessibleSeats = $room->getAccessibleSeats(); // Récupérer les sièges accessibles

        // Si la requête est soumise pour réserver des sièges
        if ($request->isMethod('POST')) {
            $selectedSeats = $request->get('seats'); // Récupérer les sièges sélectionnés

            // Vérifier les sièges sélectionnés par rapport aux sièges accessibles et aux escaliers
            $invalidSeats = [];
            foreach ($selectedSeats as $seat) {
                if (in_array($seat, $stairs)) {
                    $invalidSeats[] = $seat; // Si le siège est un escalier, il est invalide
                }
                if (in_array($seat, $accessibleSeats)) {
                    $invalidSeats[] = $seat; // Si le siège est accessible, il est invalide
                }
            }

            // Si des sièges invalides sont sélectionnés, afficher un message d'erreur
            if (!empty($invalidSeats)) {
                $this->addFlash('error', 'Certains sièges sélectionnés sont invalides : ' . implode(', ', $invalidSeats));
            } else {
                // Effectuer la réservation ici (par exemple, ajouter ces sièges à un utilisateur ou une session)
                // Code pour enregistrer la réservation des sièges dans la base de données.

                $this->addFlash('success', 'Réservation réussie.');
            }

            // Rediriger ou afficher à nouveau la page de réservation avec les messages appropriés
            return $this->redirectToRoute('app_seats_reservation', ['sessionId' => $sessionId]);
        }

        // Passer les informations à la vue
        return $this->render('employee/Room/show.html.twig', [
            'session' => $session,
            'room' => $room,
            'stairs' => $stairs,
            'accessibleSeats' => $accessibleSeats,
            'user' => $user,
        ]);
    }

    #[Route("/seat", name: "app_seat")]
    public function seat(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('seat.html.twig');
    }
}