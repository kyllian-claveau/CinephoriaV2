<?php

namespace App\Controller\Employee;

use App\Entity\Room;
use App\Form\RoomType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employee')]
class roomController extends AbstractController
{
    #[Route('/room/create', name: 'app_employee_room_create')]
    public function createRequest(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_employee_not_active');
        }

        $room = new Room();
        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stairsData = $form->get('stairs')->getData();
            $accessibleSeatsData = $form->get('accessibleSeats')->getData();

            // Gestion des escaliers
            if ($stairsData) {
                // S'assurer que les données sont sous forme de tableau
                if (is_array($stairsData)) {
                    $room->setStairs($stairsData);
                } else {
                    $form->get('stairs')->addError(new FormError('Les données des escaliers sont invalides.'));
                }
            }

            // Gestion des sièges accessibles
            if ($accessibleSeatsData) {
                // Assurer que accessibleSeats est bien un tableau
                if (is_string($accessibleSeatsData)) {
                    $accessibleSeatsData = json_decode($accessibleSeatsData, true);
                }
                if (is_array($accessibleSeatsData)) {
                    $room->setAccessibleSeats($accessibleSeatsData);
                } else {
                    $form->get('accessibleSeats')->addError(new FormError('Les données des sièges accessibles sont invalides.'));
                }
            }

            // Calcul du nombre total de sièges
            $rows = $room->getRowsRoom();
            $columns = $room->getColumnsRoom();
            $totalSeats = $rows * $columns;

            // Soustraction des sièges occupés par les escaliers et les sièges accessibles
            $totalSeats -= count($stairsData); // Soustraire le nombre d'escaliers
            $totalSeats -= count($accessibleSeatsData); // Soustraire le nombre de sièges accessibles

            $room->setTotalSeats($totalSeats);

            // Sauvegarde de la salle
            $entityManager->persist($room);
            $entityManager->flush();

            return $this->redirectToRoute('app_employee_room');
        }

        return $this->render('employee/Room/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/room', name: 'app_employee_room')]
    public function list(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_employee_not_active');
        }

        $rooms = $entityManager->getRepository(Room::class)->findAll();
        return $this->render('employee/Room/list.html.twig', [
            'rooms' => $rooms
        ]);
    }

    #[Route('/room/{id}', name: 'app_employee_room_edit')]
    public function editRequest(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_admin_not_active');
        }

        $room = $entityManager->getRepository(Room::class)->find($id);

        if (!$room) {
            throw $this->createNotFoundException('Salle non trouvée');
        }

        $form = $this->createForm(RoomType::class, $room);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stairsData = $form->get('stairs')->getData();
            $accessibleSeatsData = $form->get('accessibleSeats')->getData();

            // Gestion des escaliers
            if ($stairsData) {
                if (is_array($stairsData)) {
                    $room->setStairs($stairsData);
                } else {
                    $form->get('stairs')->addError(new FormError('Les données des escaliers sont invalides.'));
                }
            }

            // Gestion des sièges accessibles
            if ($accessibleSeatsData) {
                if (is_string($accessibleSeatsData)) {
                    $accessibleSeatsData = json_decode($accessibleSeatsData, true);
                }
                if (is_array($accessibleSeatsData)) {
                    $room->setAccessibleSeats($accessibleSeatsData);
                } else {
                    $form->get('accessibleSeats')->addError(new FormError('Les données des sièges accessibles sont invalides.'));
                }
            }

            // Calcul du nombre total de sièges
            $rows = $room->getRowsRoom();
            $columns = $room->getColumnsRoom();
            $totalSeats = $rows * $columns;

            // Soustraction des sièges occupés par les escaliers et les sièges accessibles
            $totalSeats -= count($stairsData);
            $totalSeats -= count($accessibleSeatsData);

            $room->setTotalSeats($totalSeats);

            // Sauvegarde des modifications
            $entityManager->flush();

            return $this->redirectToRoute('app_employee_room');
        }

        return $this->render('employee/Room/edit.html.twig', [
            'form' => $form->createView(),
            'room' => $room
        ]);
    }

    #[Route('/room/delete/{id}', name: 'app_employee_room_delete')]
    public function deleteRequest(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user->getIsActive()) {
            return $this->redirectToRoute('app_employee_not_active');
        }

        $room = $entityManager->getRepository(Room::class)->find($id);
        if (!$room) {
            throw $this->createNotFoundException('La salle n\'existe pas.');
        }

        $entityManager->remove($room);
        $entityManager->flush();

        $this->addFlash('success', 'La séance a été supprimée avec succès.');

        return $this->redirectToRoute('app_employee_room');
    }
}
