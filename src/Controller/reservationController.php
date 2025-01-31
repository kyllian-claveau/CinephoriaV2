<?php

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\Reservation;
use App\Entity\Session;
use App\Repository\SessionRepository;
use App\Service\FilmStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    #[Route('/reservation/view/{id}', name: 'app_view_reservation', methods: ['GET'])]
    public function viewReservation(
        Reservation $reservation,
        Request $request
    ): Response {
        // Vérifier si la requête demande du JSON
        if ($request->getPreferredFormat() === 'json') {
            return new JsonResponse([
                'film' => $reservation->getSession()->getFilm()->getTitle(),
                'date' => $reservation->getSession()->getStartDate()->format('d/m/Y H:i'),
                'salle' => $reservation->getSession()->getRoom()->getNumber(),
                'places' => $reservation->getSeats(),
                'total' => $reservation->getTotalPrice(),
                'client' => [
                    'prenom' => $reservation->getUser()->getFirstname(),
                    'nom' => $reservation->getUser()->getLastname()
                ],
                'reservation_id' => $reservation->getId()
            ]);
        }

        // Si on veut une page HTML, rediriger vers la vue appropriée
        return $this->render('view.html.twig', [
            'reservation' => $reservation
        ]);
    }

    #[Route('/reservation/confirm', name: 'app_confirm_reservation', methods: ['POST'])]
    public function confirmReservation(
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        EntityManagerInterface $em,
        SessionRepository $sessionRepository,
        FilmStatsService $statsService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Vérifier les données reçues
        if (!isset($data['sessionId'], $data['seats']) || !is_array($data['seats'])) {
            return new JsonResponse(['error' => 'Données invalides.'], 400);
        }

        // Récupérer la session
        $session = $sessionRepository->find($data['sessionId']);
        if (!$session) {
            return new JsonResponse(['error' => 'Session non trouvée.'], 404);
        }

        // Vérifier si les sièges sont disponibles
        $reservedSeats = $session->getReservedSeats();
        $selectedSeats = $data['seats'];
        $conflictSeats = array_intersect($reservedSeats, $selectedSeats);

        if (!empty($conflictSeats)) {
            return new JsonResponse(['error' => 'Certaines places sont déjà réservées.'], 400);
        }

        // Vérifier si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], 404);
        }

        // Calculer le prix total
        $pricePerSeat = $session->getPrice();
        $totalPrice = $pricePerSeat * count($selectedSeats);

        // Créer la réservation
        $reservation = new Reservation();
        $reservation->setSession($session);
        $reservation->setCreatedAt(new \DateTime());
        $reservation->setUser($user);
        $reservation->setSeats($selectedSeats);
        $reservation->setTotalPrice($totalPrice);

        // Sauvegarder la réservation et les sièges réservés
        $em->persist($reservation);
        $reservedSeats = array_merge($reservedSeats, $selectedSeats);
        $session->setReservedSeats($reservedSeats);

        // Valider les changements pour générer l'ID de la réservation
        $em->flush();

        // Générer l'URL de la réservation
        $reservationUrl = $urlGenerator->generate('app_view_reservation', [
            'id' => $reservation->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        // Générer le QR Code
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $reservationUrl,  // On utilise l'URL ici
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            labelText: $session->getFilm()->getTitle(),
            labelFont: new OpenSans(20),
            labelAlignment: LabelAlignment::Center
        );

        $result = $builder->build();

        // Créer un identifiant unique pour le fichier
        $uniqueId = uniqid('qrcode_', true);
        $qrCodeFilePath = $this->getParameter('kernel.project_dir') . '/public/images/qrcode/' . $uniqueId . '.png';

        // Sauvegarder le QR Code
        $result->saveToFile($qrCodeFilePath);

        // Définir l'URL du QR Code dans la réservation
        $qrCodeUrl = '/images/qrcode/' . $uniqueId . '.png';
        $reservation->setQrCodeUrl($qrCodeUrl);

        // Mettre à jour la réservation avec le QR Code
        $em->flush();

        // Mettre à jour les statistiques
        $statsService->updateStatsForReservation($reservation);

        return new JsonResponse([
            'message' => 'Réservation confirmée.',
            'qrcode_url' => $qrCodeUrl,
        ]);
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
        return $this->render('reservation_room.html.twig', [
            'session' => $session,
            'room' => $room,
            'stairs' => $stairs,
            'accessibleSeats' => $accessibleSeats,
            'user' => $user,
        ]);
    }
}