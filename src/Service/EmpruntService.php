<?php

namespace App\Service;

use App\Entity\Emprunt;
use App\Entity\Livre;
use App\Entity\User;
use App\Repository\EmpruntRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Règles de gestion des emprunts de la bibliothèque.
 */
class EmpruntService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EmpruntRepository $empruntRepository,
        private readonly ClockInterface $clock,
        #[Autowire(param: 'app.emprunt.max_par_adherent')]
        private readonly int $maxEmpruntsParAdherent,
        #[Autowire(param: 'app.emprunt.duree_jours')]
        private readonly int $dureeJours,
    ) {
    }

    public function getMaxEmpruntsParAdherent(): int
    {
        return $this->maxEmpruntsParAdherent;
    }

    public function getDureeJours(): int
    {
        return $this->dureeJours;
    }

    public function aujourdhui(): \DateTimeImmutable
    {
        return $this->clock->now()->setTime(0, 0);
    }

    public function exemplairesDisponibles(Livre $livre): int
    {
        return max(0, $livre->getNbExemplaires() - $this->empruntRepository->countEnCoursPourLivre($livre));
    }

    public function estDisponible(Livre $livre): bool
    {
        return $this->exemplairesDisponibles($livre) > 0;
    }

    public function empruntsRestants(User $adherent): int
    {
        return max(0, $this->maxEmpruntsParAdherent - $this->empruntRepository->countEnCoursPourAdherent($adherent));
    }

    /**
     * Enregistre un nouvel emprunt après avoir vérifié la disponibilité et le quota.
     *
     * Le livre et l'adhérent sont verrouillés en base pendant la vérification :
     * deux bibliothécaires ne peuvent pas prêter le dernier exemplaire en même temps.
     *
     * @throws EmpruntException si une règle n'est pas respectée
     */
    public function emprunter(Livre $livre, User $adherent, ?\DateTimeImmutable $dateRetourPrevue = null): Emprunt
    {
        return $this->em->wrapInTransaction(function () use ($livre, $adherent, $dateRetourPrevue): Emprunt {
            $this->em->lock($livre, LockMode::PESSIMISTIC_WRITE);
            $this->em->lock($adherent, LockMode::PESSIMISTIC_WRITE);

            if (!$this->estDisponible($livre)) {
                throw new EmpruntException(\sprintf('Aucun exemplaire de « %s » n\'est disponible.', $livre->getTitre()));
            }

            if (0 === $this->empruntsRestants($adherent)) {
                throw new EmpruntException(\sprintf('%s a déjà %d emprunts en cours (maximum autorisé).', $adherent->getNomComplet(), $this->maxEmpruntsParAdherent));
            }

            $aujourdhui = $this->aujourdhui();
            $dateRetourPrevue ??= $aujourdhui->modify(\sprintf('+%d days', $this->dureeJours));

            if ($dateRetourPrevue < $aujourdhui) {
                throw new EmpruntException('La date de retour prévue ne peut pas être dans le passé.');
            }

            $emprunt = (new Emprunt())
                ->setLivre($livre)
                ->setAdherent($adherent)
                ->setDateEmprunt($aujourdhui)
                ->setDateRetourPrevue($dateRetourPrevue);

            $this->em->persist($emprunt);

            return $emprunt;
        });
    }

    /**
     * @throws EmpruntException si le livre a déjà été rendu
     */
    public function enregistrerRetour(Emprunt $emprunt): void
    {
        if (!$emprunt->isEnCours()) {
            throw new EmpruntException('Ce livre a déjà été rendu.');
        }

        $emprunt->setDateRetourReelle($this->aujourdhui());
        $this->em->flush();
    }

    /** @return Emprunt[] */
    public function empruntsEnRetard(): array
    {
        return $this->empruntRepository->findEnRetard($this->aujourdhui());
    }
}
