<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Subscription;
use App\Entity\User;
use App\Form\CheckoutFormType;
use App\Service\LicenseKeyGenerator;
use App\Service\PlanCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout')]
    public function checkout(
        Request $request,
        PlanCatalog $plans,
        LicenseKeyGenerator $licenseKeys,
        EntityManagerInterface $entityManager,
    ): Response {
        // The URL only carries the plan NAME. The real price always comes from the
        // catalog, so nobody can change what they pay by editing the page or the URL.
        $plan = $plans->findByName($request->query->getString('plan'));

        if ($plan === null) {
            return $this->render(
                'checkout/index.html.twig',
                ['plan' => null, 'form' => null],
                new Response(status: Response::HTTP_NOT_FOUND),
            );
        }

        // The form checks the CSRF token and that the payment method is one of the allowed choices.
        $form = $this->createForm(CheckoutFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();

            $order = (new Order())
                ->setUser($user)
                ->setPlanName($plan['name'])
                ->setPlanSection($plan['section'])
                // the catalog stores "1,499" for display; we store whole pesos
                ->setPricePaid((int) str_replace(',', '', $plan['price']))
                ->setPaymentMethod((string) $form->get('paymentMethod')->getData());

            // Every plan lasts 1 year for now (matches "*For the first year" on the pricing cards).
            // When monthly vs. yearly plans are decided, this is the one place to change.
            $subscription = (new Subscription())
                ->setUser($user)
                ->setPlanName($plan['name'])
                ->setPlanSection($plan['section'])
                ->setExpiresAt(new \DateTimeImmutable('+1 year'))
                ->setLicenseKey($licenseKeys->generate());

            $entityManager->persist($order);
            $entityManager->persist($subscription);
            // one flush = one database transaction: either both rows are saved or neither is
            $entityManager->flush();

            return $this->redirectToRoute('app_receipt', ['id' => $order->getId()]);
        }

        return $this->render('checkout/index.html.twig', [
            'plan' => $plan,
            'form' => $form,
        ]);
    }
}
