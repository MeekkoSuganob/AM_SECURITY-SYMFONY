<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ReceiptController extends AbstractController
{
    #[Route('/receipt/{id}', name: 'app_receipt', requirements: ['id' => '\d+'])]
    public function show(int $id, OrderRepository $orders): Response
    {
        // Looking the order up by its id AND its owner means changing the number in
        // the URL can never show somebody else's receipt.
        $order = $orders->findOneBy(['id' => $id, 'user' => $this->getUser()]);

        if ($order === null) {
            return $this->render(
                'receipt/index.html.twig',
                ['order' => null],
                new Response(status: Response::HTTP_NOT_FOUND),
            );
        }

        return $this->render('receipt/index.html.twig', ['order' => $order]);
    }
}
