<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Ce service gère la logique de création ou de recherche de l'utilisateur
 * dans la base de données après un retour d'authentification réussi de Google.
 * Il est utilisé par GoogleAuthenticator.
 */
class GoogleOAuthService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Tente de trouver un utilisateur existant (par googleId ou email) ou en crée un nouveau.
     * @param ResourceOwnerInterface $googleUser Le profil utilisateur retourné par Google (contient email, id, etc.)
     * @return UserInterface L'entité User de votre application, soit trouvée, soit nouvellement créée.
     */
    public function findOrCreateUser(ResourceOwnerInterface $googleUser): UserInterface
    {
        // Forcer le typage pour aider l'IDE à reconnaître les méthodes spécifiques (getEmail, getId)
        /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */


        // 1. Récupération des informations clés du profil Google
        $googleId = $googleUser->getId();
        $email = $googleUser->getEmail();

        // A. Recherche par Google ID (connexion d'un utilisateur déjà lié)
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleId]);

        if ($user) {
            return $user; 
        }

        // B. Recherche par Email (cas de liaison d'un compte existant)
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($user) {
            // L'utilisateur existe déjà, on effectue la liaison (Linkage)
            $user->setGoogleId($googleId);
            $this->entityManager->flush();
            return $user;
        }

        // C. Création d'un nouvel utilisateur (cas de première inscription)
        $user = new User();
        $user->setEmail($email);
        $user->setGoogleId($googleId);
        
        // Mot de passe à NULL car la connexion est gérée par Google.
        // REQUIERT que la colonne 'password' soit nullable en DB.
        $user->setPassword(null); 
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
