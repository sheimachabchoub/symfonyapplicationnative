<?php

namespace App\Repository;

use App\Entity\Emprunt;
use App\Entity\Livre;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Emprunt> */
class EmpruntRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Emprunt::class);
    }

    public function countEnCoursPourLivre(Livre $livre): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.livre = :livre')
            ->andWhere('e.dateRetourReelle IS NULL')
            ->setParameter('livre', $livre)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countEnCoursPourAdherent(User $adherent): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.adherent = :adherent')
            ->andWhere('e.dateRetourReelle IS NULL')
            ->setParameter('adherent', $adherent)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countEnCours(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.dateRetourReelle IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Emprunt[] emprunts non rendus dont la date de retour prévue est dépassée */
    public function findEnRetard(\DateTimeImmutable $aujourdhui): array
    {
        return $this->createQueryBuilder('e')
            ->addSelect('l', 'a')
            ->join('e.livre', 'l')
            ->join('e.adherent', 'a')
            ->where('e.dateRetourReelle IS NULL')
            ->andWhere('e.dateRetourPrevue < :aujourdhui')
            ->setParameter('aujourdhui', $aujourdhui->setTime(0, 0))
            ->orderBy('e.dateRetourPrevue', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param 'tous'|'en_cours'|'rendus' $statut
     *
     * @return Emprunt[]
     */
    public function findByStatut(string $statut = 'tous', ?User $adherent = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->addSelect('l', 'a')
            ->join('e.livre', 'l')
            ->join('e.adherent', 'a')
            ->orderBy('e.dateEmprunt', 'DESC')
            ->addOrderBy('e.id', 'DESC');

        if ('en_cours' === $statut) {
            $qb->andWhere('e.dateRetourReelle IS NULL');
        } elseif ('rendus' === $statut) {
            $qb->andWhere('e.dateRetourReelle IS NOT NULL');
        }

        if (null !== $adherent) {
            $qb->andWhere('e.adherent = :adherent')->setParameter('adherent', $adherent);
        }

        return $qb->getQuery()->getResult();
    }
}
