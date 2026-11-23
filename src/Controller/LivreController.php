<?php

namespace App\Controller;

use App\Dto\LivreSearch;
use App\Entity\Livre;
use App\Form\LivreType;
use App\Repository\CategoryRepository;
use App\Repository\LivreRepository;
use App\Service\EmpruntService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/livre')]
final class LivreController extends AbstractController
{
    /** Catalogue public avec recherche par titre, auteur ou catégorie. */
    #[Route(name: 'app_livre_index', methods: ['GET'])]
    public function index(
        LivreRepository $livreRepository,
        CategoryRepository $categoryRepository,
        EmpruntService $empruntService,
        #[MapQueryString] LivreSearch $search = new LivreSearch(),
    ): Response {
        $livres = $livreRepository->search($search);

        $disponibles = [];
        foreach ($livres as $livre) {
            $disponibles[$livre->getId()] = $empruntService->exemplairesDisponibles($livre);
        }

        return $this->render('livre/index.html.twig', [
            'livres' => $livres,
            'disponibles' => $disponibles,
            'search' => $search,
            'categories' => $categoryRepository->findBy([], ['designation' => 'ASC']),
        ]);
    }

    #[Route('/exercice', name: 'app_livre_exercice', methods: ['GET'])]
    public function exercice(LivreRepository $livreRepository): Response
    {
        return $this->render('livre/exercice.html.twig', [
            'livres' => $livreRepository->findByPrixPages10Trie(20, 300),
        ]);
    }

    #[Route('/new', name: 'app_livre_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $livre = new Livre();
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($livre);
            $entityManager->flush();
            $this->addFlash('success', 'Livre ajouté au catalogue.');

            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livre/new.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_livre_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Livre $livre, EmpruntService $empruntService): Response
    {
        return $this->render('livre/show.html.twig', [
            'livre' => $livre,
            'disponibles' => $empruntService->exemplairesDisponibles($livre),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_livre_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Livre $livre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Livre modifié.');

            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livre/edit.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_livre_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Livre $livre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$livre->getId(), $request->getPayload()->getString('_token'))) {
            if (!$livre->getEmprunts()->isEmpty()) {
                $this->addFlash('danger', 'Impossible de supprimer un livre qui a un historique d\'emprunts.');

                return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($livre);
            $entityManager->flush();
            $this->addFlash('success', 'Livre supprimé.');
        }

        return $this->redirectToRoute('app_livre_index', [], Response::HTTP_SEE_OTHER);
    }
}
