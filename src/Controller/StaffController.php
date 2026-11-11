<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Repository\OrderRepository;
use App\Repository\PartnerRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Service\LicenseKeyGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Staff (and admins, who inherit ROLE_STAFF) only.
// (security.yaml's access_control already guards /staff too; this is a second lock.)
//
// Staff do day-to-day support: look up customers, orders and subscriptions, and
// fix subscription problems. They never see revenue and cannot delete users,
// create accounts or change roles - that is the admin's job.
#[IsGranted('ROLE_STAFF')]
final class StaffController extends AbstractController
{
    // Staff may only extend by one of these amounts.
    private const EXTEND_DAYS = 30;

    #[Route('/staff', name: 'app_staff')]
    public function index(
        Request $request,
        UserRepository $users,
        OrderRepository $orders,
        SubscriptionRepository $subscriptions,
        PartnerRepository $partners,
    ): Response {
        // The one search box filters customers, orders and subscriptions at the same time.
        $q = trim($request->query->getString('q'));

        // Staff look after customers, so staff and admin accounts are left out of the list.
        $isCustomer = static fn ($user): bool => !$user->isAdmin() && !$user->isStaff();

        return $this->render('staff/index.html.twig', [
            'q' => $q,
            'customer_count' => count(array_filter($users->search(null), $isCustomer)),
            'active_subscriptions' => $subscriptions->countActive(),
            'expiring_soon' => $subscriptions->countExpiringWithin(30),
            'orders_today' => $orders->countSince(new \DateTimeImmutable('today')),
            'customers' => array_values(array_filter($users->search($q), $isCustomer)),
            'orders' => $orders->search($q),
            'subscriptions' => $subscriptions->search($q),
            'partners' => $partners->findBy([], ['purchasedAt' => 'DESC']),
        ]);
    }

    #[Route('/staff/subscriptions/{id}/extend', name: 'app_staff_subscription_extend', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function extend(
        int $id,
        Request $request,
        SubscriptionRepository $subscriptions,
        EntityManagerInterface $entityManager,
    ): Response {
        $subscription = $this->findSubscription($id, $request, $subscriptions, 'staff_extend_');

        // Count from today if it already ran out, otherwise add to the current end date.
        $now = new \DateTimeImmutable();
        $from = $subscription->isExpired() ? $now : $subscription->getExpiresAt();
        $subscription->setExpiresAt($from->modify('+'.self::EXTEND_DAYS.' days'));
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            "%s's %s subscription now runs until %s.",
            $subscription->getUser()->getUsername(),
            $subscription->getPlanName(),
            $subscription->getExpiresAt()->format('Y-m-d'),
        ));

        return $this->redirectToRoute('app_staff', ['q' => $request->query->getString('q')]);
    }

    #[Route('/staff/subscriptions/{id}/reissue', name: 'app_staff_subscription_reissue', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reissue(
        int $id,
        Request $request,
        SubscriptionRepository $subscriptions,
        LicenseKeyGenerator $licenseKeys,
        EntityManagerInterface $entityManager,
    ): Response {
        $subscription = $this->findSubscription($id, $request, $subscriptions, 'staff_reissue_');

        // The old key stops working; the customer sees the new one on their next download.
        $subscription->setLicenseKey($licenseKeys->generate());
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            "A new license key was issued for %s's %s subscription.",
            $subscription->getUser()->getUsername(),
            $subscription->getPlanName(),
        ));

        return $this->redirectToRoute('app_staff', ['q' => $request->query->getString('q')]);
    }

    /** Checks the CSRF token for this exact subscription and action, then loads the subscription. */
    private function findSubscription(int $id, Request $request, SubscriptionRepository $subscriptions, string $tokenPrefix): Subscription
    {
        if (!$this->isCsrfTokenValid($tokenPrefix.$id, $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $subscriptions->find($id) ?? throw $this->createNotFoundException('Subscription not found.');
    }
}
