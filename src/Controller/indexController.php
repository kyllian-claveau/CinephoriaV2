<?php

namespace App\Controller;

use App\Entity\Film;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Attribute\Route;

class indexController extends AbstractController
{
    #[Route("/", name: "app_index")]
    public function index(EntityManagerInterface $entityManager, Security $security)
    {
        $user = $security->getUser();

        // Si l'utilisateur est connecté et a un mot de passe temporaire, redirige vers la page de changement de mot de passe
        if ($user && $user->getIsTemporaryPassword()) {
            return $this->redirectToRoute('app_change_password');
        }
        $films =$entityManager->getRepository(Film::class)->findAll();
        $filmsFromLastWednesday =$entityManager->getRepository(Film::class)->findFilmsFromLastWednesday();
        $filmsWithRatings = [];
        foreach ($films as $film) {
            $averageRating = $entityManager->getRepository(Review::class)->getAverageRatingForFilm($film->getId());
            $film->averageRating = $averageRating;
        }
        foreach ($filmsFromLastWednesday as $lastFilm) {
            $averageRating = $entityManager->getRepository(Review::class)->getAverageRatingForFilm($lastFilm->getId());
            $lastFilm->averageRating = $averageRating;
        }
        return $this->render('index.html.twig', [
            'lastFilms' => $filmsFromLastWednesday,
            'films' => $films,
            'filmsWithRatings' => $filmsWithRatings,
        ]);
    }
}