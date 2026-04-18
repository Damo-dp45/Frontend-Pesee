<?php

namespace App\Domain\Helper;

use App\Domain\Service\ApiClientService;

class ApiHelper
{
    public function __construct(
        private readonly ApiClientService $api
    )
    {
    }

    public function getStats(?string $dateDebut = null, ?string $dateFin = null): array
    {
        $query = http_build_query(array_filter([
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ]));
        $url = '/api/frontend/operations/stats' . ($query ? "?$query" : '');

        $response = $this->api->request('GET', $url); // Ou.. '['query' => $query]'
        if($response->getStatusCode() === 200) {
            $data = $response->toArray(false);
            return $data;
        }
        return [];
    }

    public function getOperations(array $params = []): array
    {
        $query = http_build_query(array_filter($params));
        $url = '/api/frontend/operations' . ($query ? "?$query" : '');

        $response = $this->api->request('GET', $url);
        if($response->getStatusCode() === 200) {
            $data = $response->toArray(false);
            return $data;
        }
        return [];
    }

    public function getSites(): array
    {
        $response = $this->api->request('GET', '/api/frontend/sites');
        if($response->getStatusCode() === 200) {
            $data = $response->toArray(false);
            return $data;
        }
        return [];
    }

    public function getReferentiel(string $type, string $code): array
    {
        $response = $this->api->request('POST', '/api/' . $type, [
            'json' => [
                'code' => $code
            ]
        ]);
        if($response->getStatusCode() === 200) {
            $data = $response->toArray(false);
            return $data;
        }
        return [];
    }
}