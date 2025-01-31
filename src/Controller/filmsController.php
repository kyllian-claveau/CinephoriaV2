<?php

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\Film;
use App\Entity\Genre;
use App\Entity\Review;
use App\Repository\FilmRepository;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class filmsController extends AbstractController
{
    #[Route("/films", name: "app_films")]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $cinemaId = $request->query->get('cinema');
        $genreId = $request->query->get('genre');
        $selectedDate = $request->query->get('date');

        $cinemaId = $cinemaId ? intval($cinemaId) : null;
        $genreId = $genreId ? intval($genreId) : null;

        $dateFilter = null;
        if ($selectedDate) {
            $dateFilter = \DateTime::createFromFormat('Y-m-d', $selectedDate);
            if ($dateFilter->format('Y-m-d') < (new \DateTime())->format('Y-m-d')) {
                return $this->redirectToRoute('app_films', [
                    'cinema' => $cinemaId,
                    'genre' => $genreId,
                    'date' => (new \DateTime())->format('Y-m-d')
                ]);
            }

        }

        $filmsFilter = $entityManager->getRepository(Film::class)
            ->findFilmsByFilters($cinemaId, $genreId, $dateFilter);

        $films = $entityManager->getRepository(Film::class)->findAll();
        $cinemas = $entityManager->getRepository(Cinema::class)->findAll();
        $genres = $entityManager->getRepository(Genre::class)->findAll();

        $filmsWithRatings = [];
        foreach ($films as $film) {
            $averageRating = $entityManager->getRepository(Review::class)->getAverageRatingForFilm($film->getId());
            $film->averageRating = $averageRating;
        }

        return $this->render('films.html.twig', [
            'films' => $films,
            'filmsFilter' => $filmsFilter,
            'genres' => $genres,
            'filmsWithRatings' => $filmsWithRatings,
            'cinemas' => $cinemas,
        ]);
    }

    #[Route("/film/{id}", name: "app_film_show")]
    public function show(int $id, FilmRepository $filmRepository, SessionRepository $sessionRepository): Response
    {
        $film = $filmRepository->find($id);
        if (!$film) {
            throw $this->createNotFoundException('Film non trouvé');
        }

        $sessions = $sessionRepository->findByFilm($film);

        $cinemas = [];
        foreach ($sessions as $session) {
            $cinema = $session->getCinema();
            if (!in_array($cinema, $cinemas)) {
                $cinemas[] = $cinema;
            }
        }

        return $this->render('show_film.html.twig', [
            'film' => $film,
            'sessions' => $sessions,
            'cinemas' => $cinemas,
        ]);
    }
}