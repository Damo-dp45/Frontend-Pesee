<?php

namespace App\Controller;

use App\Domain\Helper\ApiHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request, ApiHelper $api): Response
    {
        $dateDebut = $request->query->get('date_debut', date('Y-m-01')); /*
            - Le mois en cours par défaut
        */
        $dateFin = $request->query->get('date_fin', date('Y-m-d'));
        $stats = $api->getStats($dateDebut, $dateFin);
        $sites = $api->getSites();

        return $this->render('home/index.html.twig', [
            'stats' => $stats,
            'sites' => $sites,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ]);
    }
}