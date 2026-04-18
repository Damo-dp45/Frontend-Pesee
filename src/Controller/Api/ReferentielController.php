<?php

namespace App\Controller\Api;

use App\Domain\Helper\ApiHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ReferentielController extends AbstractController
{
    private const TYPES_AUTORISES = [
        'mouvement',
        'client',
        'fournisseur',
        'transporteur',
        'produit',
        'destination',
        'provenance',
        'vehicule'
    ];

    public function __construct(
        private readonly ApiHelper $api
    )
    {
    }

    #[Route('referentiels/{code}/{type}', name: 'referentiel_get', methods: ['GET'])]
    public function get(
        string $code,
        string $type
    ): JsonResponse
    {
        if(!in_array($type, self::TYPES_AUTORISES, true)) {
            return new JsonResponse(['error' => 'Type invalide'], 400);
        }
        $data = $this->api->getReferentiel($type, $code);
        return new JsonResponse($data);
    }
}
