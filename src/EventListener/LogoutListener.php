<?php

namespace App\EventListener;

use App\Domain\Service\ApiClientService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class LogoutListener
{
    public function __construct(
        private ApiClientService $apiClient,
        private UrlGeneratorInterface $urlGenerator
    )
    {
    }

    #[AsEventListener]
    public function onLogoutEvent(LogoutEvent $event): void
    {
        $this->apiClient->logout();
        $response = new RedirectResponse($this->urlGenerator->generate('app_login'));
        $response->headers->clearCookie(
            'rt',
            '/',
            null,
            $event->getRequest()->isSecure(), // Ou.. '$this->params->get('app.env') == 'prod''
            true,
            'lax'
        ); /*
            - 'clearCookie' attend les mêmes attributs que lors de la création sinon le navigateur ne reconnaît pas le cookie à supprimer
        */ 
        $event->setResponse($response);
    }
}
