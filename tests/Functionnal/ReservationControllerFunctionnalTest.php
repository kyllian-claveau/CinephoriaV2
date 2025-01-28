<?php

namespace App\Tests\Functionnal;

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
        $reservation = $this->createReservation(); // Méthode fictive pour créer une réservation

        // Effectuer une requête GET pour visualiser la réservation en JSON
        $client->request('GET', '/reservation/view/'.$reservation->getId(), [], [], ['HTTP_ACCEPT' => 'application/json']);

        // Vérifier la réponse
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'film' => $reservation->getSession()->getFilm()->getTitle(),
            'total' => $reservation->getTotalPrice(),
        ]);
    }
}
