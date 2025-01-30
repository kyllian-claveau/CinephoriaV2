<?php

namespace App\Tests\Functionnal;

use App\Entity\Cinema;
use App\Entity\Film;
use App\Entity\Genre;
use App\Entity\Room;
use App\Entity\Session;
use App\Entity\User;
use App\Entity\Reservation;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReservationControllerFunctionnalTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        // Effectuer une requête GET sur la page de réservation
        $client->request('GET', '/reservation');

        // Vérifier que la page se charge correctement
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Réservation');

        // Vérifier que certains éléments existent dans la réponse
        $this->assertSelectorExists('.film-session');
    }

    public function testViewReservationJsonResponse()
    {
        $client = static::createClient();

        // Créer une réservation pour l'utilisateur
        $reservation = $this->createReservation();

        // Effectuer une requête GET pour visualiser la réservation en JSON
        $client->request('GET', '/reservation/view/'.$reservation->getId(), [], [], ['HTTP_ACCEPT' => 'application/json']);

        // Décoder la réponse JSON en tableau PHP
        $data = json_decode($client->getResponse()->getContent(), true);

        // Vérifier que les valeurs attendues sont présentes dans la réponse
        $this->assertArrayHasKey('film', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertEquals($reservation->getSession()->getFilm()->getTitle(), $data['film']);
        $this->assertEquals($reservation->getTotalPrice(), $data['total']);
    }

    private function createReservation(): Reservation
    {
        // Créer un utilisateur
        $user = new User();
        $user->setEmail('test@example3.com');
        $user->setPassword('password');
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setRoles(['ROLE_USER']);

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $entityManager->persist($user);

        // Créer un cinéma
        $cinema = new Cinema();
        $cinema->setName('Cinéma de test');
        $cinema->setLocation('Test');
        $entityManager->persist($cinema);

        // Créer un genre
        $genre = new Genre();
        $genre->setName('Action');
        $entityManager->persist($genre);

        // Créer une salle
        $room = new Room();
        $room->setNumber(1);
        $room->setRowsRoom(10);
        $room->setColumnsRoom(15);
        $room->setTotalSeats(120);
        $room->setAccessibleSeats([]);
        $room->setQuality('3D');
        $entityManager->persist($room);

        // Créer un film
        $film = new Film();
        $film->setTitle('Test Film');
        $film->setDescription('Test Description');
        $film->setFilmFilename('/images/test.jpg');
        $film->setDuration(120);
        $film->addGenre($genre);
        $film->addCinema($cinema);
        $film->setAgeMin(10);
        $film->setIsFavorite(false);
        $entityManager->persist($film);

        // Créer une session
        $session = new Session();
        $session->setStartDate(new \DateTime());
        $session->setEndDate(new \DateTime());
        $session->setFilm($film);
        $session->setRoom($room);
        $session->setPrice(10.0);
        $session->setCinema($cinema);
        $entityManager->persist($session);

        // Créer une réservation
        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setSession($session);
        $reservation->setCreatedAt(new \DateTime());
        $reservation->setTotalPrice(20.0);
        $reservation->setSeats(['77', '78']);
        $entityManager->persist($reservation);

        // Sauvegarder les données dans la base de données
        $entityManager->flush();

        return $reservation;
    }
}
