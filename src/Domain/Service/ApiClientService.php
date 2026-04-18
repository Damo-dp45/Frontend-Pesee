<?php

namespace App\Domain\Service;

use App\Security\Exception\AuthenticationExpiredException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClientService
{
    private const TOKEN_KEY = 'token';
    private const USER_KEY = 'user';
    private const REFRESH_TOKEN_KEY = 'refresh_token';

    public function __construct(
        private string $apiUrl,
        private RequestStack $requestStack,
        private HttpClientInterface $httpClient
    )
    {
    }

    private function getSession(): SessionInterface
    {
        $session = $this->requestStack->getSession(); /*
            - throw déjà une exception si la session est indisponible
        */
        return $session;
    }

    // -- Auth -- //

    /**
     * Permet d'authentifier l'utilisateur auprès du backend et stocke les tokens en session
     * @param string $email
     * @param string $password
     * @throws \RuntimeException
     * @return array{refresh_token: mixed, token: mixed, user: array}
     */
    public function login(string $email, string $password): array
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiUrl . '/api/login_check', [
                'json' => [
                    'username' => $email,
                    'password' => $password
                ]
            ]);
            $data = $response->toArray(false);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Erreur API: ' . $e->getMessage());
        }

        if($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Identifiants invalides');
        }

        $session = $this->getSession();
        $session->set(self::TOKEN_KEY, $data['token']);
        $session->set(self::REFRESH_TOKEN_KEY, $data['refresh_token']);

        $userResponse = $this->httpClient->request('GET', $this->apiUrl . '/api/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $data['token'],
                'Accept' => 'application/json'
            ]
        ]);
        $user = $userResponse->toArray(false);
        $session->set(self::USER_KEY, $user);

        return [
            'user' => $user,
            'token' => $data['token'],
            'refresh_token' => $data['refresh_token']
        ];
    }

    /**
     * Permet de rafraîchir le token 'jwt'
     * @throws \RuntimeException
     * @return string
     */
    public function refreshToken(): string
    {
        $refreshToken = $this->getSession()->get(self::REFRESH_TOKEN_KEY);

        if(!$refreshToken) {
            throw new \RuntimeException('Session expirée. Veuillez vous reconnecter');
        }

        $response = $this->httpClient->request('POST', $this->apiUrl . '/api/token/refresh', [
            'json' => [
                'refresh_token' => $refreshToken
            ]
        ]);

        if($response->getStatusCode() !== 200) {
            $this->logout();
            throw new \RuntimeException('Impossible de rafraîchir la session');
        }

        $data = $response->toArray(false);
        $session = $this->getSession();
        $session->set(self::TOKEN_KEY, $data['token']);
        $session->set(self::REFRESH_TOKEN_KEY, $data['refresh_token']);
        $this->refreshCurrentUser();

        return $data['token'];
    }

    /**
     * Permet de reconstruire la session complète depuis le cookie 'rt' au retour après fermeture navigateur
     */
    public function refreshTokenFromCookie(string $refreshToken): string
    {
        $response = $this->httpClient->request('POST', $this->apiUrl . '/api/token/refresh', [
            'json' => [
                'refresh_token' => $refreshToken
            ]
        ]);

        if($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Refresh token invalide ou expiré.');
        }

        $data = $response->toArray(false);
        $session = $this->getSession();
        $session->set(self::TOKEN_KEY, $data['token']);
        $session->set(self::REFRESH_TOKEN_KEY, $data['refresh_token']);

        $userResponse = $this->httpClient->request('GET', $this->apiUrl . '/api/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $data['token'],
                'Accept' => 'application/json'
            ]
        ]);
        $session->set(self::USER_KEY, $userResponse->toArray(false));

        return $data['token'];
    }

    /**
     * Permet de rafraichir l'utilisateur
     * @return void
     */
    public function refreshCurrentUser(): void
    {
        $token = $this->getToken();
        if(!$token) {
            return;
        }
        $response = $this->httpClient->request('GET', $this->apiUrl . '/api/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ]
        ]);
        $this->getSession()->set(self::USER_KEY, $response->toArray(false));
    }

    public function register(array $data): array
    {
        $response = $this->httpClient->request('POST', $this->apiUrl . '/api/inscription', [
            'headers' => [
                'Content-Type' => 'application/json'
                // 'Authorization' => 'Bearer ' . $this->getToken()
            ],
            'json' => $data
        ]);

        return [
            'status' => $response->getStatusCode(),
            'data' => $response->toArray(false)
        ];
    }

    public function registerUser(array $data): array
    {
        $response = $this->httpClient->request('POST', $this->apiUrl . '/api/inscription/utilisateurs', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $data
        ]);

        return [
            'status' => $response->getStatusCode(),
            'data' => $response->toArray(false)
        ];
    }

    public function logout(): void
    {
        $session = $this->getSession();
        $session->clear();
        $session->invalidate();
    }

    // -- Helper de sionn -- //

    public function getToken(): ?string
    {
        return $this->getSession()->get(self::TOKEN_KEY);
    }

    public function getRefreshToken(): ?string
    {
        return $this->getSession()->get(self::REFRESH_TOKEN_KEY);
    }

    public function getCurrentUser(): ?array
    {
        return $this->getSession()->get(self::USER_KEY);
    }

    public function isAuthenticated(): bool
    {
        return $this->getToken() !== null;
    }

    // -- Request -- //

    /**
     * Va gérer le corps de la requête en injectant le 'Bearer'
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return ResponseInterface
     */
    public function request(string $method, string $endpoint, array $options = [], bool $retry = true): ResponseInterface
    {
        $token = $this->getToken();
        $url = str_starts_with($endpoint, 'http') ? $endpoint : $this->apiUrl . $endpoint;

        $headers = array_merge([
            'Accept' => 'application/json',
        ], $options['headers'] ?? []);

        $publicEndpoints = ['/api/login_check', '/api/token/refresh', '/api/register'];
        $path = parse_url($endpoint, PHP_URL_PATH);

        if(!in_array($path, $publicEndpoints) && $token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $options['headers'] = $headers;
        $response = $this->httpClient->request($method, $url, $options); // Ou.. 'HttpClient::create()->request'

        if($response->getStatusCode() === 401 && $token && $retry) {
            try {
                $newToken = $this->refreshToken();
                $options['headers']['Authorization'] = 'Bearer ' . $newToken;
                return $this->request($method, $endpoint, $options, false); /*
                    - 'false' pour éviter une boucle infinie vu que si le refresh échoue il va renvoyé une '401'
                    - return $this->httpClient->request($method, $url, $options);
                */
            } catch (\Throwable) {
                throw new AuthenticationExpiredException(); /*
                    - Le refresh a échoué donc on on retourne le '401' original et le controller redirigera vers le login
                */
            }
        }

        return $response;
    }
}