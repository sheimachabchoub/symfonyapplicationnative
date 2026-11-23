<?php

namespace App\Controller;

use App\Repository\LivreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TestLivreController extends AbstractController
{
    #[Route('/test/livres/{numero}', name: 'test_livres', requirements: ['numero' => '[1-7]'], methods: ['GET'])]
    public function tester(int $numero, LivreRepository $repository): JsonResponse
    {
        $x = 20;
        $y = 300;

        $livres = match ($numero) {
            1 => $repository->findByPrixSup($x),
            2 => $repository->findByPrixPages($x, $y),
            3 => $repository->findByPrixPages10($x, $y),
            4, 6 => $repository->findByPrixPagesTrie($x, $y),
            5 => $repository->findByPrixPages10Trie($x, $y),
            7 => $repository->findByPrixPagesAuteurTrie($x, $y),
        };

        $resultats = [];
        foreach ($livres as $livre) {
            $auteurs = [];
            foreach ($livre->getAuteurs() as $auteur) {
                $auteurs[] = $auteur->getPrenom().' '.$auteur->getNom();
            }
            $resultats[] = [
                'id' => $livre->getId(),
                'titre' => $livre->getTitre(),
                'prix' => $livre->getPrix(),
                'nbPages' => $livre->getNbPages(),
                'auteurs' => $auteurs,
            ];
        }

        return $this->json([
            'question' => $numero,
            'x' => $x,
            'y' => $y,
            'nombre' => count($resultats),
            'livres' => $resultats,
        ]);
    }
}
