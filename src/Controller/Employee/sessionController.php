<?php

namespace App\Controller\Employee;

use App\Controller\APIController;
use App\Entity\Film;
use App\Entity\Session;
use App\Form\SessionType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employee')]
class sessionController extends AbstractController
{
    #[Route('/session/create', name: 'app_employee_session_create')]
    public function createRequest (Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_employee_not_active');
        }

        $session = new Session();
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($session);
            $entityManager->flush();

            $this->addFlash('success', 'La séance a été créée avec succès.');

            return $this->redirectToRoute('app_employee_session');
        }

        return $this->render('employee/Session/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/api/films/{id}/cinemas', methods: ['GET'])]
    public function getCinemasByFilm(Film $film): JsonResponse
    {
        $cinemas = $film->getCinemas();
        return $this->json($cinemas);
    }

    #[Route('/session', name: 'app_employee_session')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_employee_not_active');
        }

        $sessions = $entityManager->getRepository(Session::class)->findAll();
        return $this->render('employee/Session/list.html.twig', [
            'sessions' => $sessions
        ]);
    }

    #[Route('/session/{id}', name: 'app_employee_session_edit')]
    public function editRequest(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_employee_not_active');
        }

        $session = $entityManager->getRepository(Session::class)->find($id);
        if (!$session) {
            throw $this->createNotFoundException('La séance n\'existe pas.');
        }

        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            $this->addFlash('success', 'La séance a été modifiée avec succès.');

            return $this->redirectToRoute('app_employee_session');
        }

        return $this->render('employee/Session/edit.html.twig', [
            'form' => $form->createView(),
            'session' => $session,
        ]);
    }

    #[Route('/session/delete/{id}', name: 'app_employee_session_delete')]
    public function deleteRequest(int $id, EntityManagerInterface $entityManager): Response
    {
        $session = $entityManager->getRepository(Session::class)->find($id);
        if (!$session) {
            throw $this->createNotFoundException('La séance n\'existe pas.');
        }

        $entityManager->remove($session);
        $entityManager->flush();

        $this->addFlash('success', 'La séance a été supprimée avec succès.');

        return $this->redirectToRoute('app_employee_session');
    }
}
