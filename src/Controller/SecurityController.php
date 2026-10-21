<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // already logged in? no need to see the login form
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/login.html.twig', [
            // the username they typed last time, so the field stays filled after a failed attempt
            'last_username' => $authenticationUtils->getLastUsername(),
            // null, or the reason the last login attempt failed
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        // Never actually runs: the "logout" key in security.yaml intercepts this URL.
        throw new \LogicException('This method is intercepted by the logout key on the firewall.');
    }
}
