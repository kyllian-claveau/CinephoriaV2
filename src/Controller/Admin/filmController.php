<?php

namespace App\Controller\Admin;

use App\Controller\APIController;
use App\Entity\Film;
use App\Form\FilmType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class filmController extends AbstractController
{
    #[Route('/film/create', name: 'app_admin_film_create')]
    public function createRequest(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_admin_not_active');
        }

        $currentDay = (new \DateTime())->format('l');
        if ($currentDay !== 'Wednesday') {
            $this->addFlash('error', 'Les films ne peuvent être créés que le mercredi.');
            return $this->redirectToRoute('app_admin_film');
        }

        $film = new Film();
        $form = $this->createForm(FilmType::class, $film);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($film->getFilmFile() !== null) {
                $filename = uniqid() . '.' . $film->getFilmFile()->guessClientExtension();

                $path = $this->getParameter('kernel.project_dir') . '/public/images/film/' . $filename;
                $content = $film->getFilmFile()->getContent();
                file_put_contents($path, $content);
                $film->setFilmFilename($filename);
                $film->setFilmFile(null);
            }
            $entityManager->persist($film);
            $entityManager->flush();

            $this->addFlash('success', 'Le film a été créé avec succès.');

            return $this->redirectToRoute('app_admin_film');
        }

        return $this->render('admin/Film/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/film', name: 'app_admin_film')]
    public function list(EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_admin_not_active');
        }

        $films = $entityManager->getRepository(Film::class)->findAll();
        return $this->render('admin/Film/list.html.twig', [
            'films' => $films,
        ]);
    }

    #[Route('/film/{id}', name: 'app_admin_film_edit')]
    public function editRequest(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_admin_not_active');
        }

        $film = $entityManager->getRepository(Film::class)->find($id);
        if (!$film) {
            throw $this->createNotFoundException('Le film n\'existe pas.');
        }

        $form = $this->createForm(FilmType::class, $film);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($film->getFilmFile() !== null) {
                $filename = uniqid() . '.' . $film->getFilmFile()->guessClientExtension();

                $path = $this->getParameter('kernel.project_dir') . '/public/images/film/' . $filename;
                $content = $film->getFilmFile()->getContent();
                file_put_contents($path, $content);
                $film->setFilmFilename($filename);
                $film->setFilmFile(null);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le film a été modifié avec succès.');

            return $this->redirectToRoute('app_admin_film');
        }

        return $this->render('admin/Film/edit.html.twig', [
            'form' => $form->createView(),
            'film' => $film,
        ]);
    }

    #[Route('/film/delete/{id}', name: 'app_admin_film_delete')]
    public function deleteRequest(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_admin_not_active');
        }

        $film = $entityManager->getRepository(Film::class)->find($id);
        if (!$film) {
            throw $this->createNotFoundException('Le film n\'existe pas.');
        }

        if ($film->getFilmFilename()) {
            $filePath = $this->getParameter('kernel.project_dir') . '/public/images/film/' . $film->getFilmFilename();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $entityManager->remove($film);
        $entityManager->flush();

        $this->addFlash('success', 'Le film a été supprimé avec succès.');

        return $this->redirectToRoute('app_admin_film');
    }
}
