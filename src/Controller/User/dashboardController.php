<?php

namespace App\Controller\User;

use App\Controller\APIController;
use App\Entity\Film;
use App\Entity\Reservation;
use App\Entity\Review;
use App\Entity\Session;
use App\Form\ReviewType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/user')]
class dashboardController extends AbstractController
{
    #[Route(path:'/not-active', name:'app_user_not_active', methods: ['GET'])]
    public function notActive(): Response
    {
        return $this->render('user/active_account.html.twig');
    }
    #[Route(path: '/dashboard', name: 'app_user_dashboard', methods: ["GET"])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_user_not_active');
        }

        $reservations = $entityManager->getRepository(Reservation::class)
            ->findBy(['user' => $user], ['createdAt' => 'DESC'], 2);

        $films = $entityManager->getRepository(Film::class)
            ->findBy([], ['createdAt' => 'DESC'], 2);

        $allReservations = $entityManager->getRepository(Reservation::class)
            ->findBy(['user' => $user]);

        $watchedFilms = [];
        foreach ($allReservations as $reservation) {
            $film = $reservation->getSession()->getFilm();
            $watchedFilms[$film->getId()] = $film;
        }
        $distinctFilmsCount = count($watchedFilms);

        return $this->render('user/dashboard.html.twig', [
            'reservations' => $reservations,
            'films' => $films,
            'totalReservations' => count($allReservations),
            'distinctFilmsCount' => $distinctFilmsCount,
        ]);
    }

    #[Route(path: '/orders', name: 'app_user_orders', methods: ["GET"])]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_user_not_active');
        }
        $reservations = $entityManager->getRepository(Reservation::class)->findAll();

        return $this->render('user/orders.html.twig', [
            'reservations' => $reservations
        ]);
    }

    #[Route(path: '/order/{id}', name: 'app_user_order', methods: ["GET"])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_user_not_active');
        }
        $reservation = $entityManager->getRepository(Reservation::class)->find($id);
        if (!$reservation) {
            throw $this->createNotFoundException('La réservation n\'existe pas.');
        }

        return $this->render('user/show_order.html.twig', [
            'reservation' => $reservation
        ]);
    }

    #[Route(path: '/reviews', name: 'app_user_reviews', methods: ["GET"])]
    public function list_reviews(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_user_not_active');
        }
        $reviews = $entityManager->getRepository(Review::class)->findAll();


        return $this->render('user/reviews.html.twig', [
            'reviews' => $reviews
        ]);
    }

    #[Route(path: '/review/create/{reservationId}', name: 'app_user_review')]
    public function show_review(int $reservationId, Request $request, UserRepository $userRepository, APIController $apiController, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_user_not_active');
        }
        $user = $this->getUser();

        $reservation = $entityManager->getRepository(Reservation::class)->find($reservationId);
        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        $session = $reservation->getSession();
        if (!$session) {
            throw $this->createNotFoundException('Séance non trouvée');
        }

        if (!$session->isFinished()) {
            $this->addFlash('error', 'Vous ne pouvez laisser un avis que lorsque la séance est terminée.');
            return $this->redirectToRoute('app_user_reviews');
        }

        if ($reservation->getReviews()->count() > 0) {
            $this->addFlash('error', 'Vous avez déjà laissé un avis pour cette réservation.');
            return $this->redirectToRoute('app_user_reviews');
        }

        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $review->setUser($user);
            $review->setFilm($session->getFilm());
            $review->setReservation($reservation);
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'L\'avis a été créé avec succès.');

            return $this->redirectToRoute('app_user_reviews');
        }

        return $this->render('user/create_review.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}