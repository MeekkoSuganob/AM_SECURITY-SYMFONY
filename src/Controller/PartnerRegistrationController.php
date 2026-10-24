<?php

namespace App\Controller;

use App\Entity\Partner;
use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Service\PartnerTierCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class PartnerRegistrationController extends AbstractController
{
    #[Route('/partners/register', name: 'app_partner_register')]
    public function register(
        Request $request,
        PartnerTierCatalog $tiers,
        PartnerRepository $partners,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $partner = $partners->findOneBy(['user' => $user]);

        // The URL only carries the tier NAME. The real price always comes from the catalog.
        $tier = $tiers->findByName($request->query->getString('tier'));

        // 1) No (valid) tier chosen yet: show the tier picker.
        if ($tier === null) {
            return $this->render('partner_registration/index.html.twig', [
                'state' => 'picker',
                'tiers' => $tiers->all(),
                'partner' => $partner,
                'tier' => null,
                'form' => null,
            ]);
        }

        // 2) Already on this exact tier: nothing to buy.
        if ($partner !== null && $partner->getTier() === $tier['name']) {
            return $this->render('partner_registration/index.html.twig', [
                'state' => 'same',
                'tiers' => [],
                'partner' => $partner,
                'tier' => $tier,
                'form' => null,
            ]);
        }

        // 3) Confirm page. This form has no fields of its own: it only carries the CSRF token.
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isSwitch = $partner !== null;

            if ($partner === null) {
                $partner = (new Partner())->setUser($user);
                $entityManager->persist($partner);
            }

            // One row per user: this sets the tier on a new row, or replaces it on the existing one.
            $partner->switchTo($tier['name'], (int) str_replace(',', '', $tier['price']));
            $entityManager->flush();

            if ($isSwitch) {
                $this->addFlash('success', sprintf("You've switched to the %s tier!", $tier['name']));
            } else {
                $article = in_array(strtolower($tier['name'][0]), ['a', 'e', 'i', 'o', 'u'], true) ? 'an' : 'a';
                $this->addFlash('success', sprintf("You're now %s %s partner!", $article, $tier['name']));
            }

            return $this->redirectToRoute('app_account');
        }

        return $this->render('partner_registration/index.html.twig', [
            'state' => 'confirm',
            'tiers' => [],
            'partner' => $partner,
            'tier' => $tier,
            'form' => $form,
        ]);
    }
}
