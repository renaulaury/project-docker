<?php
// src/Controller/GoogleController.php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleController extends AbstractController
{
    /**
     * Lance la redirection vers Google pour commencer l'authentification.
     */
    #[Route('/connect/google', name: 'connect_google')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        // Le client 'google' est défini dans knpu_oauth2_client.yaml
        return $clientRegistry
            ->getClient('google') 
            ->redirect([], []); 
    }

    /**
     * Cette route est le point de retour (callback) de Google.
     * Elle est interceptée par l'Authenticator. Cette méthode ne sera jamais exécutée.
     */
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(): Response
    {
        // Si vous arrivez ici, c'est que l'Authenticator a un problème.
        throw new \Exception('Authenticator Error: Le GoogleAuthenticator n\'a pas intercepté la requête de callback.');
    }
}
