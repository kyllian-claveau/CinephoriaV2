<?php

namespace App\Controller\Admin;

use App\Service\FilmStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/admin')]
class dashboardController extends AbstractController
{
    #[Route(path:'/not-active', name:'app_admin_not_active', methods: ['GET'])]
    public function notActive(): Response
    {
        return $this->render('admin/active_account.html.twig');
    }
    #[Route(path: '/dashboard', name: 'app_admin_dashboard', methods: ["GET"])]
    public function list(FilmStatsService $statsService): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_admin_not_active');
        }

        $stats = $statsService->getWeeklyStats();

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats
        ]);
    }
}