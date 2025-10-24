<?php

namespace App\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Gère le processus de retour (callback) de Google et authentifie l'utilisateur.
 * Ce fichier a été créé manuellement pour contourner le blocage de make:social-auth.
 */
class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private ClientRegistry $clientRegistry;
    private GoogleOAuthService $googleOAuthService;
    private RouterInterface $router;

    public function __construct(
        ClientRegistry $clientRegistry, 
        GoogleOAuthService $googleOAuthService, // Votre service de gestion d'utilisateur
        RouterInterface $router
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->googleOAuthService = $googleOAuthService;
        $this->router = $router;
    }

    /**
     * Dit à Symfony d'intercepter uniquement les requêtes qui arrivent sur la route de callback.
     */
    public function supports(Request $request): ?bool
    {
        // 'connect_google_check' est la route définie dans GoogleController
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    /**
     * Étape 1 : Récupération des informations d'authentification et création du Passport.
     */
    public function authenticate(Request $request): Passport
    {
        // Récupération du client Google défini dans knpu_oauth2_client.yaml
        $client = $this->clientRegistry->getClient('google');

        // Récupération du jeton (AccessToken)
        $accessToken = $this->fetchAccessToken($client);
        
        // Création du SelfValidatingPassport
        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                // Fonction de rappel exécutée par Symfony

                // 1. Récupération du profil Google (ResourceOwner)
                $googleUser = $client->fetchUserFromToken($accessToken);
                
                // 2. Logique métier : Trouver ou créer l'utilisateur local via notre service
                $user = $this->googleOAuthService->findOrCreateUser($googleUser);
                
                return $user;
            })
        );
    }

    /**
     * Étape 2 : L'authentification a réussi.
     */
    public function onAuthenticationSuccess(Request $request, \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token, string $firewallName): ?Response
    {
        // Redirige l'utilisateur vers la page d'accueil après connexion réussie
        $targetUrl = $this->router->generate('app_home'); // Assurez-vous que la route 'app_home' existe
        return new RedirectResponse($targetUrl);
    }

    /**
     * Étape 3 : L'authentification a échoué.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Redirige vers la page de login en cas d'échec et ajoute un message flash
        $request->getSession()->getFlashBag()->add('error', 'Échec de la connexion Google: ' . $exception->getMessage());
        $targetUrl = $this->router->generate('app_login'); // Assurez-vous que la route 'app_login' existe
        return new RedirectResponse($targetUrl);
    }

    /**
     * Point d'entrée pour les pages sécurisées. Redirige vers Google pour commencer l'auth.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // Redirige vers la route de démarrage de la connexion Google (dans GoogleController)
        return new RedirectResponse('/connect/google'); 
    }
}
