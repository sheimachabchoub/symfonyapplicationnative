<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\EmpruntService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Gestion des comptes adhérents : réservée au bibliothécaire. */
#[Route('/adherent')]
#[IsGranted('ROLE_ADMIN')]
final class AdherentController extends AbstractController
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    #[Route(name: 'app_adherent_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('adherent/index.html.twig', [
            'adherents' => $userRepository->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'app_adherent_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $adherent = new User();
        $form = $this->createForm(UserType::class, $adherent, ['creation' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->appliquerMotDePasse($adherent, $form);
            $entityManager->persist($adherent);
            $entityManager->flush();
            $this->addFlash('success', 'Adhérent créé.');

            return $this->redirectToRoute('app_adherent_show', ['id' => $adherent->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('adherent/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'app_adherent_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(User $adherent, EmpruntService $empruntService): Response
    {
        return $this->render('adherent/show.html.twig', [
            'adherent' => $adherent,
            'restants' => $empruntService->empruntsRestants($adherent),
            'aujourdhui' => $empruntService->aujourdhui(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_adherent_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, User $adherent, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $adherent, ['creation' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->appliquerMotDePasse($adherent, $form);
            $entityManager->flush();
            $this->addFlash('success', 'Adhérent modifié.');

            return $this->redirectToRoute('app_adherent_show', ['id' => $adherent->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('adherent/edit.html.twig', [
            'adherent' => $adherent,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_adherent_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, User $adherent, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$adherent->getId(), $request->getPayload()->getString('_token'))) {
            if ($adherent === $this->getUser()) {
                $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
            } elseif (!$adherent->getEmprunts()->isEmpty()) {
                $this->addFlash('danger', 'Impossible de supprimer un adhérent qui a un historique d\'emprunts.');
            } else {
                $entityManager->remove($adherent);
                $entityManager->flush();
                $this->addFlash('success', 'Adhérent supprimé.');

                return $this->redirectToRoute('app_adherent_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->redirectToRoute('app_adherent_show', ['id' => $adherent->getId()], Response::HTTP_SEE_OTHER);
    }

    private function appliquerMotDePasse(User $adherent, FormInterface $form): void
    {
        $motDePasse = $form->get('plainPassword')->getData();
        if (\is_string($motDePasse) && '' !== $motDePasse) {
            $adherent->setPassword($this->hasher->hashPassword($adherent, $motDePasse));
        }
    }
}
