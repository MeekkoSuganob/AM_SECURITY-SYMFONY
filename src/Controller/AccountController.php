<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\PartnerRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Every page in this controller needs a logged-in user
// (security.yaml's access_control already guards /account too; this is a second lock).
#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    public function index(
        OrderRepository $orders,
        SubscriptionRepository $subscriptions,
        PartnerRepository $partners,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/index.html.twig', [
            'orders' => $orders->findBy(['user' => $user], ['createdAt' => 'DESC', 'id' => 'DESC']),
            'subscriptions' => $subscriptions->findBy(['user' => $user], ['expiresAt' => 'DESC']),
            'partner' => $partners->findOneBy(['user' => $user]),
        ]);
    }

    #[Route('/account/delete', name: 'app_account_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security,
    ): Response {
        // CSRF check: the token comes from the hidden field in the delete form
        if (!$this->isCsrfTokenValid('delete_account', $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        /** @var User $user */
        $user = $this->getUser();

        // log out first, so the session no longer points at an account that is about to disappear
        $logoutResponse = $security->logout(false);

        // Orders, subscriptions and the partnership row are removed by the
        // database itself (ON DELETE CASCADE), so deleting the user is enough.
        $entityManager->remove($user);
        $entityManager->flush();

        return $logoutResponse ?? $this->redirectToRoute('app_home');
    }
}
