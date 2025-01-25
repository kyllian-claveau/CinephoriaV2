<?php

namespace App\Controller;

use App\Entity\Film;
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
        $filmsFromLastWednesday =$entityManager->getRepository(Film::class)->findFilmsFromLastWednesday();
        return $this->render('index.html.twig', [
            'films' => $filmsFromLastWednesday,
        ]);
    }
}