<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\PartnerRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Admins only. (security.yaml's access_control already guards /admin too; this is a second lock.)
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(
        UserRepository $users,
        OrderRepository $orders,
        SubscriptionRepository $subscriptions,
        PartnerRepository $partners,
    ): Response {
        return $this->render('admin/index.html.twig', [
            // users are loaded first so each order's "user" is already in memory (no extra queries)
            'users' => $users->findBy([], ['createdAt' => 'DESC']),
            'orders' => $orders->findBy([], ['createdAt' => 'DESC', 'id' => 'DESC']),
            // soonest-to-expire first, so subscriptions that need attention come first
            'subscriptions' => $subscriptions->findBy([], ['expiresAt' => 'ASC']),
            'partners' => $partners->findBy([], ['purchasedAt' => 'DESC']),
            'total_revenue' => $orders->totalRevenue(),
        ]);
    }
}
