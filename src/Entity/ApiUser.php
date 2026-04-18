<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly array $data
    )
    {
    }

    public function getId(): int
    {
        return $this->data['id'];
    }

    public function getNom(): string
    {
        return $this->data['nom'] ?? '';
    }

    public function getPrenom(): string
    {
        return $this->data['prenom'] ?? '';
    }

    public function getEmail(): string
    {
        return $this->data['email'] ?? '';
    }

    public function getEntrepriseId(): ?int
    {
        return $this->data['entreprise'] ?? null;
    }

    /**
     * Va retourner les rôles métier de symfony
     */
    public function getRoles(): array
    {
        return $this->data['roles'];
    }

    public function eraseCredentials(): void
    {
        // On n'a rien à effacer vu qu'on n'a pas de mot de passe stocké
    }

    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }

    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Pour le 'remember_me' pour générer la signature du cookie vu qu'il est réquis
     * @return null
     */
    public function getPassword(): ?string
    {
        return null; /*
            - Si 'null' il utilisera 'getUserIdentifier()' pour la signature
        */
    }
}