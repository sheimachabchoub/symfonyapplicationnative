<?php

namespace App\Controller;

use App\Repository\AuteurRepository;
use App\Repository\EmpruntRepository;
use App\Repository\LivreRepository;
use App\Repository\UserRepository;
use App\Service\EmpruntService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Tableau de bord du bibliothécaire : statistiques et emprunts en retard. */
#[IsGranted('ROLE_ADMIN')]
final class TableauDeBordController extends AbstractController
{
    #[Route('/tableau-de-bord', name: 'app_tableau_de_bord', methods: ['GET'])]
    public function index(
        LivreRepository $livreRepository,
        AuteurRepository $auteurRepository,
        UserRepository $userRepository,
        EmpruntRepository $empruntRepository,
        EmpruntService $empruntService,
    ): Response {
        $exemplaires = $livreRepository->countExemplaires();
        $enCours = $empruntRepository->countEnCours();
        $enRetard = $empruntService->empruntsEnRetard();

        return $this->render('tableau_de_bord/index.html.twig', [
            'stats' => [
                'livres' => $livreRepository->count(),
                'exemplaires' => $exemplaires,
                'disponibles' => max(0, $exemplaires - $enCours),
                'auteurs' => $auteurRepository->count(),
                'adherents' => $userRepository->count(),
                'empruntsEnCours' => $enCours,
                'empruntsTotal' => $empruntRepository->count(),
                'enRetard' => \count($enRetard),
            ],
            'enRetard' => $enRetard,
            'aujourdhui' => $empruntService->aujourdhui(),
        ]);
    }
}
