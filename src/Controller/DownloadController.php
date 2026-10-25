<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SubscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class DownloadController extends AbstractController
{
    // Only these two values are accepted in the URL; anything else is a 404.
    #[Route('/download/{os}', name: 'app_download', requirements: ['os' => 'windows|macos'])]
    public function download(string $os, SubscriptionRepository $subscriptions): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // The gate: the database decides, not the URL. No active subscription, no download.
        $subscription = $subscriptions->findActiveFor($user);

        if ($subscription === null) {
            return $this->render(
                'download/no_subscription.html.twig',
                [],
                new Response(status: Response::HTTP_FORBIDDEN),
            );
        }

        $osLabel = $os === 'macos' ? 'macOS' : 'Windows';

        // Placeholder for the real installer: a text file carrying the plan and license key.
        $content = "AM Security - {$osLabel} Edition\r\n";
        $content .= "==============================\r\n\r\n";
        $content .= "This is a placeholder installer file for demonstration purposes.\r\n\r\n";
        $content .= "Plan: {$subscription->getPlanName()}\r\n";
        $content .= "License Key: {$subscription->getLicenseKey()}\r\n";
        $content .= "Subscription Expires: {$subscription->getExpiresAt()?->format('Y-m-d H:i:s')}\r\n\r\n";
        $content .= "Enter your license key when prompted during setup to activate.\r\n\r\n";
        $content .= "Thank you for choosing AM Security.\r\n";

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            sprintf('AMSecurity-%s-Setup.txt', $osLabel),
        ));

        return $response;
    }
}
