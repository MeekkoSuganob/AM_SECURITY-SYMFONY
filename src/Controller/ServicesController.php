<?php

namespace App\Controller;

use App\Service\PlanCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ServicesController extends AbstractController
{
    #[Route('/services', name: 'app_services')]
    public function index(PlanCatalog $plans): Response
    {
        return $this->render('services/index.html.twig', [
            'consumer_plans' => $plans->consumer(),
            'business_plans' => $plans->business(),
            'enterprise_plans' => $plans->enterprise(),
        ]);
    }
}
