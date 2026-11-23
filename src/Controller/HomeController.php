<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\LivreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(LivreRepository $livreRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'derniers' => $livreRepository->findDerniers(6),
            'categories' => $categoryRepository->findBy([], ['designation' => 'ASC']),
        ]);
    }
}
