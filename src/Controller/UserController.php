<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/users', name: 'app_users', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy(
            [],
            ['lastLoggedInAt' => 'DESC']
        );
        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/status', name: 'app_users_status', methods: ['POST'])]
    public function updateStatus(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $this->validateCsrfToken($request);
        $status = $request->request->get('status');
        if ($status !== "active" && $status !== "blocked")
            throw $this->createNotFoundException();

        $userIds = $request->request->all('users');
        if ($userIds === []) {
            $this->addFlash('users_warning', 'Please select at least one user.');
            return $this->redirectToRoute('app_users');
        }
        $users = $userRepository->findBy(['id' => $userIds]);
        $changed = 0;
        foreach ($users as $user) {
            if ($user->getStatus() !== $status) {
                $user->setStatus($status);
                $changed++;
            }
        }
        if ($changed > 0) {
            $entityManager->flush();
            $message = $status === 'blocked' ? 'Selected users were blocked.' : 'Selected users were unblocked.';
            $this->addFlash('users_success', $message);
        } else {
            $message = $status === 'blocked' ? 'Selected users are already blocked.' : 'Selected users are already unblocked.';
            $this->addFlash('users_warning', $message);
        }
        return $this->redirectToRoute('app_users');
    }

    #[Route('/users/delete', name: 'app_users_delete', methods: ['POST'])]
    public function delete(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, Security $security): Response
    {
        $this->validateCsrfToken($request);
        $userIds = $request->request->all('users');
        if ($userIds === []) {
            $this->addFlash('users_warning', 'Please select at least one user.');
            return $this->redirectToRoute('app_users');
        }
        $users = $userRepository->findBy(['id' => $userIds,]);
        $deletedCurrentUser = $this->removeUsers($users, $entityManager);
        if ($deletedCurrentUser) {
            $security->logout(false);
            return $this->redirectToRoute('app_login');
        }
        $this->addFlash('users_success', 'Selected users were deleted.');
        return $this->redirectToRoute('app_users');
    }

    #[Route('/users/delete-unverified', name: 'app_users_delete_unverified', methods: ['POST'])]
    public function deleteUnverified(UserRepository $userRepository, EntityManagerInterface $entityManager, Request $request, Security $security): Response
    {
        $this->validateCsrfToken($request);
        $users = $userRepository->findBy(['status' => 'unverified']);
        if ($users === []) {
            $this->addFlash('users_warning', 'There are no unverified users to delete.');
            return $this->redirectToRoute('app_users');
        }
        $deletedCurrentUser = $this->removeUsers($users, $entityManager);
        if ($deletedCurrentUser) {
            $security->logout(false);
            return $this->redirectToRoute('app_login');
        }
        $this->addFlash('users_success', 'Unverified users were deleted.');
        return $this->redirectToRoute('app_users');
    }

    private function validateCsrfToken(Request $request): void
    {
        if (!$this->isCsrfTokenValid('users_management', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }

    private function getCurrentUserId(): ?int
    {
        $user = $this->getUser();
        return $user instanceof User ? $user->getId() : null;
    }

    private function removeUsers(array $users, EntityManagerInterface $entityManager): bool
    {
        $currentUserId = $this->getCurrentUserId();
        $deletedCurrentUser = false;
        foreach ($users as $user) {
            if ($user->getId() === $currentUserId) {
                $deletedCurrentUser = true;
            }
            $entityManager->remove($user);
        }
        $entityManager->flush();
        return $deletedCurrentUser;
    }
}
