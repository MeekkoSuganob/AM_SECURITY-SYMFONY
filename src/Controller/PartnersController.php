<?php

namespace App\Controller;

use App\Repository\PartnerRepository;
use App\Service\PartnerTierCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PartnersController extends AbstractController
{
    #[Route('/partners', name: 'app_partners')]
    public function index(PartnerTierCatalog $tiers, PartnerRepository $partners): Response
    {
        // Anyone can view this page, but a logged-in partner sees their current tier highlighted.
        $user = $this->getUser();
        $partner = $user !== null ? $partners->findOneBy(['user' => $user]) : null;

        return $this->render('partners/index.html.twig', [
            'partner_tiers' => $tiers->all(),
            'current_partner_tier' => $partner?->getTier(),
        ]);
    }
}
