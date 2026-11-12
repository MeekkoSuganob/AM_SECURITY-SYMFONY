<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\StaffAccountFormType;
use App\Repository\UserRepository;
use App\Repository\PartnerRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Admins only. Anyone who registers on the public form is a normal user;
// staff accounts can only be created here.
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

    #[Route('/admin/accounts/new', name: 'app_admin_account_new')]
    public function new(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = new User();
        $form = $this->createForm(StaffAccountFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();
            // the form already rejected anything that is not ROLE_STAFF or ROLE_ADMIN
            $role = (string) $form->get('role')->getData();

            $user->setRoles([$role]);
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Created %s account "%s".',
                $user->getRoleLabel(),
                $user->getUsername(),
            ));

            return $this->redirectToRoute('app_admin');
        }

        return $this->render('admin/account_new.html.twig', [
            'account_form' => $form,
        ]);
    }
}
