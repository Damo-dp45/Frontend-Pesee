<?php

namespace App\Controller;

use App\Domain\Service\ApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'register', methods: ['GET', 'POST'])]
    // #[IsGranted('ROLE_SUPER_ADMIN')]
    public function register(Request $request, ApiClientService $api): Response
    {
        // On peut rediriger s'il est connecté --
        $errors = [];
        $data = [];

        if($request->isMethod('POST')) {
            $data = [
                'codeentreprise' => strtoupper(trim($request->request->get('codeentreprise', ''))),
                'nomentreprise' => trim($request->request->get('nomentreprise', '')),
                'adresse' => trim($request->request->get('adresse', '')),
                'contact' => trim($request->request->get('contact', '')),
                'nom' => trim($request->request->get('nom', '')),
                'prenom' => trim($request->request->get('prenom', '')),
                'email' => trim($request->request->get('email', '')),
                'password' => $request->request->get('password', ''),
                'password_confirm' => $request->request->get('password_confirm', '')
            ];

            if($data['password'] !== $data['password_confirm']) {
                $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
            }

            if(empty($errors)) {
                $result = $api->register($data);

                if($result['status'] === 201) {
                    $this->addFlash('success', 'Compte créé avec succès. Connectez-vous');
                    return $this->redirectToRoute('app_login');
                }

                if($result['status'] === 403) {
                    $errors['global'] = 'Accès refusé. Vous devez être super administrateur.';
                } else {
                    $errors = $result['data']['errors'] ?? [
                        'global' => 'Une erreur est survenue.'
                    ];
                }
            }
        }

        return $this->render('registration/index.html.twig', [
            'errors' => $errors,
            'data' => $data
        ]);
    }

    #[Route('/inscription/utilisateur', name: 'register.user', methods: ['GET', 'POST'])]
    public function inscription(Request $request, ApiClientService $api)
    {
        $errors = [];
        $data = [];

        if($request->isMethod('POST')) {
            $data = [
                'codeentreprise' => strtoupper(trim($request->request->get('codeentreprise', ''))),
                'nom' => trim($request->request->get('nom', '')),
                'prenom' => trim($request->request->get('prenom', '')),
                'email' => trim($request->request->get('email', '')),
                'password' => $request->request->get('password', ''),
                'password_confirm' => $request->request->get('password_confirm', '')
            ];

            if($data['password'] !== $data['password_confirm']) {
                $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
            }

            if (empty($errors)) {
                $result = $api->registerUser($data);

                if($result['status'] === 201) {
                    $this->addFlash('success', 'Compte créé avec succès. Connectez-vous');
                    return $this->redirectToRoute('app_login');
                }

                $errors = $result['data']['errors'] ?? [
                    'global' => 'Une erreur est survenue. Veuillez réessayer.',
                ];
            }
        }

        return $this->render('registration/user.html.twig', [
            'errors' => $errors,
            'data' => $data
        ]);
    }
}
