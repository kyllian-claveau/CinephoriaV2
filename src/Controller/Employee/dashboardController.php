<?php

namespace App\Controller\Employee;

use App\Repository\ReservationRepository;
use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path:'/employee')]
class dashboardController extends AbstractController
{
    #[Route(path:'/not-active', name:'app_employee_not_active', methods: ['GET'])]
    public function notActive(): Response
    {
        return $this->render('employee/active_account.html.twig');
    }

    #[Route(path: '/dashboard', name:'app_employee_dashboard', methods: ["GET"])]
    public function list(ReservationRepository $reservationRepository, SessionRepository $sessionRepository): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_employee_not_active');
        }

        $reservationsCount = $reservationRepository->countReservationsToday();
        $sessionsCount = $sessionRepository->countUpcomingSessions();
        $revenue = $reservationRepository->getTodayRevenue();
        $nextSessions = $sessionRepository->findNextSessions();

        return $this->render('employee/dashboard.html.twig', [
            'reservationsCount' => $reservationsCount,
            'sessionsCount' => $sessionsCount,
            'revenue' => $revenue,
            'nextSessions' => $nextSessions,
        ]);
        }
}