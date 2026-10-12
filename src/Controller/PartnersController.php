<?php

namespace App\Controller;

use App\Service\PartnerTierCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PartnersController extends AbstractController
{
    #[Route('/partners', name: 'app_partners')]
    public function index(PartnerTierCatalog $tiers): Response
    {
        return $this->render('partners/index.html.twig', [
            'partner_tiers' => $tiers->all(),
            // TODO: once the Partner entity and login exist, look up the
            // logged-in user's current tier here (the old partners.php did this).
            'current_partner_tier' => null,
        ]);
    }
}
