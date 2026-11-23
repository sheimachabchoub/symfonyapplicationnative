<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EmpruntRepository;
use App\Service\EmpruntService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Espace personnel de l'adhérent connecté. */
#[IsGranted('ROLE_USER')]
final class EspaceAdherentController extends AbstractController
{
    #[Route('/mes-emprunts', name: 'app_mes_emprunts', methods: ['GET'])]
    public function mesEmprunts(#[CurrentUser] User $user, EmpruntRepository $empruntRepository, EmpruntService $empruntService): Response
    {
        return $this->render('espace/mes_emprunts.html.twig', [
            'enCours' => $empruntRepository->findByStatut('en_cours', $user),
            'rendus' => $empruntRepository->findByStatut('rendus', $user),
            'restants' => $empruntService->empruntsRestants($user),
            'max' => $empruntService->getMaxEmpruntsParAdherent(),
            'aujourdhui' => $empruntService->aujourdhui(),
        ]);
    }
}
