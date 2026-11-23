<?php

namespace App\Controller;

use App\Entity\Emprunt;
use App\Form\EmpruntType;
use App\Repository\EmpruntRepository;
use App\Repository\LivreRepository;
use App\Service\EmpruntException;
use App\Service\EmpruntService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Gestion des emprunts : réservée au bibliothécaire. */
#[Route('/emprunt')]
#[IsGranted('ROLE_ADMIN')]
final class EmpruntController extends AbstractController
{
    public function __construct(private readonly EmpruntService $empruntService)
    {
    }

    #[Route(name: 'app_emprunt_index', methods: ['GET'])]
    public function index(
        EmpruntRepository $empruntRepository,
        #[MapQueryParameter(filter: \FILTER_VALIDATE_REGEXP, options: ['regexp' => '/^(tous|en_cours|rendus|retard)$/'])] string $statut = 'en_cours',
    ): Response {
        $emprunts = 'retard' === $statut
            ? $this->empruntService->empruntsEnRetard()
            : $empruntRepository->findByStatut($statut);

        return $this->render('emprunt/index.html.twig', [
            'emprunts' => $emprunts,
            'statut' => $statut,
            'aujourdhui' => $this->empruntService->aujourdhui(),
        ]);
    }

    #[Route('/new', name: 'app_emprunt_new', methods: ['GET', 'POST'])]
    public function new(Request $request, LivreRepository $livreRepository, #[MapQueryParameter] ?int $livre = null): Response
    {
        $emprunt = new Emprunt();
        // Lien « Prêter ce livre » depuis la fiche : livre présélectionné
        if (null !== $livre) {
            $emprunt->setLivre($livreRepository->find($livre));
        }
        $form = $this->createForm(EmpruntType::class, $emprunt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $emprunt = $this->empruntService->emprunter(
                    $emprunt->getLivre(),
                    $emprunt->getAdherent(),
                    $emprunt->getDateRetourPrevue(),
                );
                $this->addFlash('success', \sprintf('Emprunt enregistré : retour prévu le %s.', $emprunt->getDateRetourPrevue()->format('d/m/Y')));

                return $this->redirectToRoute('app_emprunt_show', ['id' => $emprunt->getId()], Response::HTTP_SEE_OTHER);
            } catch (EmpruntException $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('emprunt/new.html.twig', [
            'form' => $form,
            'duree' => $this->empruntService->getDureeJours(),
            'max' => $this->empruntService->getMaxEmpruntsParAdherent(),
        ]);
    }

    #[Route('/{id}', name: 'app_emprunt_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Emprunt $emprunt): Response
    {
        return $this->render('emprunt/show.html.twig', [
            'emprunt' => $emprunt,
            'aujourdhui' => $this->empruntService->aujourdhui(),
        ]);
    }

    /** Prolongation : seule la date de retour prévue est modifiable. */
    #[Route('/{id}/edit', name: 'app_emprunt_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Emprunt $emprunt, EntityManagerInterface $entityManager): Response
    {
        if (!$emprunt->isEnCours()) {
            $this->addFlash('warning', 'Un emprunt rendu ne peut plus être modifié.');

            return $this->redirectToRoute('app_emprunt_show', ['id' => $emprunt->getId()], Response::HTTP_SEE_OTHER);
        }

        $form = $this->createForm(EmpruntType::class, $emprunt, ['modification' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Date de retour prévue mise à jour.');

            return $this->redirectToRoute('app_emprunt_show', ['id' => $emprunt->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('emprunt/edit.html.twig', [
            'emprunt' => $emprunt,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/retour', name: 'app_emprunt_retour', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function retour(Request $request, Emprunt $emprunt): Response
    {
        if ($this->isCsrfTokenValid('retour'.$emprunt->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $this->empruntService->enregistrerRetour($emprunt);
                $this->addFlash('success', \sprintf('Retour de « %s » enregistré.', $emprunt->getLivre()->getTitre()));
            } catch (EmpruntException $e) {
                $this->addFlash('warning', $e->getMessage());
            }
        }

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('app_emprunt_index'), Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_emprunt_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Emprunt $emprunt, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$emprunt->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($emprunt);
            $entityManager->flush();
            $this->addFlash('success', 'Emprunt supprimé.');
        }

        return $this->redirectToRoute('app_emprunt_index', [], Response::HTTP_SEE_OTHER);
    }
}
