<?php

namespace App\Security;

use App\Domain\Service\ApiClientService;
use App\Entity\ApiUser;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class ApiAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    private const RT_COOKIE = 'rt';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private readonly ApiClientService $apiClient,
        private readonly RouterInterface $router
    )
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrf = $request->request->get('_csrf_token', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new SelfValidatingPassport(
            new UserBadge($email, function (string $userIdentifier) use ($email, $password) {
                try {
                    $data = $this->apiClient->login($email, $password);
                } catch(\RuntimeException $e) {
                    throw new CustomUserMessageAuthenticationException($e->getMessage());
                }

                if(!isset($data['user'])) {
                    throw new CustomUserMessageAuthenticationException('Utilisateur invalide');
                }

                return new ApiUser($data['user']);
            }),
            [
                new CsrfTokenBadge('authenticate', $csrf),
                new RememberMeBadge()
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $response = new RedirectResponse($this->getTargetPath($request->getSession(), $firewallName) ?? $this->urlGenerator->generate('home'));
        $refreshToken = $this->apiClient->getRefreshToken();

        if($refreshToken) {
            $response->headers->setCookie(
                Cookie::create(self::RT_COOKIE)
                    ->withValue($refreshToken)
                    ->withExpires(time() + 2592000) // 30j
                    ->withHttpOnly(true) // inaccessible au JS
                    ->withSecure($request->isSecure()) // Https only, on peut '$this->params->get('app.env') == 'prod'
                    ->withSameSite('lax') // Protection CSRF strict = très sécurisé mais souvent casse les apps, lax = bon équilibre
                    ->withPath('/')
            );
        }

        return $response;
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
