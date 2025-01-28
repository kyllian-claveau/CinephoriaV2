<?php

namespace App\Tests\Controller;

use App\Entity\Cinema;
use App\Entity\Film;
use App\Entity\Genre;
use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\Session;
use App\Entity\User;
use App\Service\FilmStatsService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class ReservationControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $filmStatsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->filmStatsService = $this->createMock(FilmStatsService::class);

        static::getContainer()->set(FilmStatsService::class, $this->filmStatsService);
    }

    public function testIndex(): void
    {
        $cinema = new Cinema();
        $cinema->setName('Cinéma de test');
        $cinema->setLocation('Test');
        $this->entityManager->persist($cinema);
        $this->entityManager->flush();

        $genre = new Genre();
        $genre->setName('Action');
        $this->entityManager->persist($genre);
        $this->entityManager->flush();

        $room = new Room();
        $room->setNumber(1);
        $room->setRowsRoom(10);
        $room->setColumnsRoom(15);
        $room->setTotalSeats(120);
        $room->setAccessibleSeats([["col" => 6, "row" => 9], ["col" => 5, "row" => 9], ["col" => 7, "row" => 9], ["col" => 8, "row" => 9], ["col" => 9, "row" => 9], ["col" => 5, "row" => 8], ["col" => 6, "row" => 8], ["col" => 7, "row" => 8], ["col" => 8, "row" => 8], ["col" => 9, "row" => 8]]);
        $room->setStairs([["col" => 10, "row" => 0], ["col" => 10, "row" => 1], ["col" => 10, "row" => 2], ["col" => 10, "row" => 3], ["col" => 10, "row" => 4], ["col" => 10, "row" => 5], ["col" => 10, "row" => 6], ["col" => 10, "row" => 7], ["col" => 10, "row" => 8], ["col" => 10, "row" => 9], ["col" => 4, "row" => 0], ["col" => 4, "row" => 1], ["col" => 4, "row" => 2], ["col" => 4, "row" => 3], ["col" => 4, "row" => 4], ["col" => 4, "row" => 5], ["col" => 4, "row" => 6], ["col" => 4, "row" => 7], ["col" => 4, "row" => 8], ["col" => 4, "row" => 9]]);
        $room->setQuality("4K");
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        $film = new Film();
        $film->setTitle('Test Film');
        $film->setDescription('Test Description');
        $film->setDuration(120);
        $film->setCreatedAt(new \DateTime());
        $film->setFilmFilename("6797c776efe71.jpg");
        $film->setAgeMin(10);
        $film->setIsFavorite(1);
        $film->addCinema($cinema);
        $cinema->addFilm($film);
        $film->addGenre($genre);
        $this->entityManager->persist($film);
        $this->entityManager->flush();

        $session = new Session();
        $session->setStartDate(new \DateTime());
        $session->setEndDate(new \DateTime());
        $session->setFilm($film);
        $session->setRoom($room);
        $session->setPrice(10.0);
        $session->setReservedSeats([]);
        $session->setCinema($cinema);
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        // Test de la page d'index
        $this->client->request('GET', '/reservation', ['cinema' => $cinema->getId()]);

        $this->assertResponseIsSuccessful();
    }

    public function testConfirmReservation(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $cinema = new Cinema();
        $cinema->setName('Cinema de test');
        $cinema->setLocation('Test');
        $this->entityManager->persist($cinema);
        $this->entityManager->flush();

        $genre = new Genre();
        $genre->setName('Action');
        $this->entityManager->persist($genre);
        $this->entityManager->flush();

        $room = new Room();
        $room->setNumber(1);
        $room->setRowsRoom(10);
        $room->setColumnsRoom(15);
        $room->setTotalSeats(120);
        $room->setAccessibleSeats([["col" => 6, "row" => 9], ["col" => 5, "row" => 9], ["col" => 7, "row" => 9], ["col" => 8, "row" => 9], ["col" => 9, "row" => 9], ["col" => 5, "row" => 8], ["col" => 6, "row" => 8], ["col" => 7, "row" => 8], ["col" => 8, "row" => 8], ["col" => 9, "row" => 8]]);
        $room->setStairs([["col" => 10, "row" => 0], ["col" => 10, "row" => 1], ["col" => 10, "row" => 2], ["col" => 10, "row" => 3], ["col" => 10, "row" => 4], ["col" => 10, "row" => 5], ["col" => 10, "row" => 6], ["col" => 10, "row" => 7], ["col" => 10, "row" => 8], ["col" => 10, "row" => 9], ["col" => 4, "row" => 0], ["col" => 4, "row" => 1], ["col" => 4, "row" => 2], ["col" => 4, "row" => 3], ["col" => 4, "row" => 4], ["col" => 4, "row" => 5], ["col" => 4, "row" => 6], ["col" => 4, "row" => 7], ["col" => 4, "row" => 8], ["col" => 4, "row" => 9]]);
        $room->setQuality("4K");
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        $film = new Film();
        $film->setTitle('Test Film');
        $film->setDescription('Test Description');
        $film->setDuration(120);
        $film->setCreatedAt(new \DateTime());
        $film->setFilmFilename("6797c776efe71.jpg");
        $film->setAgeMin(10);
        $film->setIsFavorite(1);
        $film->addCinema($cinema);
        $cinema->addFilm($film);
        $film->addGenre($genre);
        $this->entityManager->persist($film);
        $this->entityManager->flush();

        $session = new Session();
        $session->setStartDate(new \DateTime());
        $session->setEndDate(new \DateTime());
        $session->setFilm($film);
        $session->setRoom($room);
        $session->setPrice(10.0);
        $session->setReservedSeats([]);
        $session->setCinema($cinema);
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        // Simuler une connexion utilisateur
        $this->client->loginUser($user);

        // Données de la réservation
        $reservationData = [
            'sessionId' => $session->getId(),
            'seats' => ['77', '78']
        ];

        // Envoi de la requête de réservation
        $this->client->request(
            'POST',
            '/reservation/confirm',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($reservationData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testViewReservation(): void
    {
        $cinema = new Cinema();
        $cinema->setName('Cinema de test');
        $cinema->setLocation('Test');
        $this->entityManager->persist($cinema);
        $this->entityManager->flush();

        $genre = new Genre();
        $genre->setName('Action');
        $this->entityManager->persist($genre);
        $this->entityManager->flush();

        $room = new Room();
        $room->setNumber(1);
        $room->setRowsRoom(10);
        $room->setColumnsRoom(15);
        $room->setTotalSeats(120);
        $room->setAccessibleSeats([["col" => 6, "row" => 9], ["col" => 5, "row" => 9], ["col" => 7, "row" => 9], ["col" => 8, "row" => 9], ["col" => 9, "row" => 9], ["col" => 5, "row" => 8], ["col" => 6, "row" => 8], ["col" => 7, "row" => 8], ["col" => 8, "row" => 8], ["col" => 9, "row" => 8]]);
        $room->setStairs([["col" => 10, "row" => 0], ["col" => 10, "row" => 1], ["col" => 10, "row" => 2], ["col" => 10, "row" => 3], ["col" => 10, "row" => 4], ["col" => 10, "row" => 5], ["col" => 10, "row" => 6], ["col" => 10, "row" => 7], ["col" => 10, "row" => 8], ["col" => 10, "row" => 9], ["col" => 4, "row" => 0], ["col" => 4, "row" => 1], ["col" => 4, "row" => 2], ["col" => 4, "row" => 3], ["col" => 4, "row" => 4], ["col" => 4, "row" => 5], ["col" => 4, "row" => 6], ["col" => 4, "row" => 7], ["col" => 4, "row" => 8], ["col" => 4, "row" => 9]]);
        $room->setQuality("4K");
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        $film = new Film();
        $film->setTitle('Test Film');
        $film->setDescription('Test Description');
        $film->setDuration(120);
        $film->setCreatedAt(new \DateTime());
        $film->setFilmFilename("6797c776efe71.jpg");
        $film->setAgeMin(10);
        $film->setIsFavorite(1);
        $film->addCinema($cinema);
        $film->addGenre($genre);
        $cinema->addFilm($film);
        $this->entityManager->persist($film);
        $this->entityManager->flush();

        $session = new Session();
        $session->setStartDate(new \DateTime());
        $session->setEndDate(new \DateTime());
        $session->setFilm($film);
        $session->setRoom($room);
        $session->setPrice(10.0);
        $session->setReservedSeats([]);
        $session->setCinema($cinema);
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        $user = new User();
        $user->setEmail('testView@example.com');
        $user->setPassword('password');
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setSession($session);
        $reservation->setCreatedAt(new \DateTime());
        $reservation->setTotalPrice(20.0);
        $reservation->setSeats(['79', '81']);
        $reservation->setQrCodeUrl('/path/to/qrcode.png');
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        // Test format JSON
        $this->client->request(
            'GET',
            '/reservation/view/' . $reservation->getId(),
            ['_format' => 'json']
        );

        $this->assertResponseIsSuccessful();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Nettoyage de la base de données
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}