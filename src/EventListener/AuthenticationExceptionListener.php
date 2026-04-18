<?php

namespace App\EventListener;

use App\Security\Exception\AuthenticationExpiredException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\RouterInterface;

final class AuthenticationExceptionListener
{
    public function __construct(
        private RouterInterface $router,
        private Security $security
    )
    {
    }

    #[AsEventListener]
    public function onRequestEvent(ExceptionEvent $event): void
    {
        if(!$event->getThrowable() instanceof AuthenticationExpiredException) {
            return;
        }
        $this->security->logout(false); /*
            - Pour déconnecter l'utilisateur et 'false' ne fait pas de CSRF check car c'est forcé côté serveur 'bypass le CSRF check'
        */
        $event->setResponse(new RedirectResponse($this->router->generate('app_login')));
    }
}
