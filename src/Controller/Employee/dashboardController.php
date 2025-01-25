<?php

namespace App\Controller\Employee;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path:'/employee')]
class dashboardController extends AbstractController
{
    #[Route(path: '/dashboard', name:'app_employee_dashboard', methods: ["GET"])]
    public function list(): Response
    {
            return $this->render('employee/dashboard.html.twig');
        }
}