<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CalculController extends AbstractController
{
    #[Route('/calcul', name: 'app_calcul')]
    public function index(): Response
    {
        return $this->render('calcul/index.html.twig', [
            'controller_name' => 'CalculController',
        ]);
    }
    #[Route('/somme/{a}/{b}')]
    public function somme(int $a, int $b): Response
    {
        $res = $a + $b;
        return new Response("<h1>GLNT21</h1> La somme de $a et $b est : $res");
    }
}