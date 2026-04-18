<?php

namespace App\Controller;

use App\Domain\Helper\ApiHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class StatistiqueController extends AbstractController
{
    #[Route('/statistique', name: 'statistique')]
    public function index(Request $request, ApiHelper $api): Response
    {
        $filtres = array_filter([
            'page' => $request->query->get('page', 1),
            'limit' => $request->query->get('limit', 20),
            'datepesee1' => $request->query->get('date_debut'),
            'datepesee2' => $request->query->get('date_fin'),
            'code' => $request->query->get('code'),
            'produit' => $request->query->get('produit'),
            'transporteur' => $request->query->get('transporteur'),
            'client' => $request->query->get('client'),
            'fournisseur' => $request->query->get('fournisseur'),
            'mouvement' => $request->query->get('mouvement'),
            'destination' => $request->query->get('destination'),
            'provenance' => $request->query->get('provenance'),
            'immatriculation'=> $request->query->get('immatriculation')
        ]);

        $result = $api->getOperations($filtres);
        $sites  = $api->getSites();

        $referentiels = [];
        $codeSelectionne = $filtres['code'] ?? null; /*
            - Si un site est sélectionné, on pré-charge les référentiels  pour que les selects soient déjà peuplés au chargement
        */
        if($codeSelectionne) {
            $types = ['mouvement', 'client', 'fournisseur', 'transporteur', 'produit', 'destination', 'provenance', 'vehicule'];
            foreach($types as $type) {
                $referentiels[$type] = $api->getReferentiel($type, $codeSelectionne);
            }
        }

        return $this->render('statistique/index.html.twig', [
            'operations' => $result['data'] ?? [],
            'pagination' => $result['pagination'] ?? [],
            'sites' => $sites,
            'filtres' => $filtres,
            'referentiels' => $referentiels,
            'codeSelectionne' => $codeSelectionne
        ]);
    }
}
