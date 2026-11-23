<?php

namespace App\Repository;

use App\Dto\LivreSearch;
use App\Entity\Livre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Livre> */
class LivreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livre::class);
    }

    /**
     * Recherche par titre, auteur (nom ou prénom) ou catégorie, filtrable par catégorie.
     *
     * @return Livre[]
     */
    public function search(LivreSearch $search): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('DISTINCT l', 'c')
            ->leftJoin('l.category', 'c')
            ->leftJoin('l.auteurs', 'a')
            ->orderBy('l.titre', 'ASC');

        $terme = trim((string) $search->q);
        if ('' !== $terme) {
            $qb->andWhere('l.titre LIKE :q OR a.nom LIKE :q OR a.prenom LIKE :q OR CONCAT(a.prenom, \' \', a.nom) LIKE :q OR c.designation LIKE :q')
                ->setParameter('q', '%'.addcslashes($terme, '%_').'%');
        }

        if (null !== $search->categorie) {
            $qb->andWhere('c.id = :categorie')->setParameter('categorie', $search->categorie);
        }

        return $qb->getQuery()->getResult();
    }

    /** @return Livre[] */
    public function findDerniers(int $limite = 6): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.id', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }

    public function countExemplaires(): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COALESCE(SUM(l.nbExemplaires), 0)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Livre[] */
    public function findByPrixSup(float $x): array
    {
        return $this->getEntityManager()
            ->createQuery('SELECT l FROM App\Entity\Livre l WHERE l.prix > :x')
            ->setParameter('x', $x)
            ->getResult();
    }

    /** @return Livre[] */
    public function findByPrixPages(float $x, int $y): array
    {
        return $this->getEntityManager()
            ->createQuery('SELECT l FROM App\Entity\Livre l WHERE l.prix > :x AND l.nbPages < :y')
            ->setParameter('x', $x)
            ->setParameter('y', $y)
            ->getResult();
    }

    /** @return Livre[] */
    public function findByPrixPages10(float $x, int $y): array
    {
        return $this->getEntityManager()
            ->createQuery('SELECT l FROM App\Entity\Livre l WHERE l.prix > :x AND l.nbPages < :y ORDER BY l.id ASC')
            ->setParameter('x', $x)
            ->setParameter('y', $y)
            ->setMaxResults(10)
            ->getResult();
    }

    /** @return Livre[] */
    public function findByPrixPagesTrie(float $x, int $y): array
    {
        return $this->getEntityManager()
            ->createQuery('SELECT l FROM App\Entity\Livre l WHERE l.prix > :x AND l.nbPages < :y ORDER BY l.prix DESC')
            ->setParameter('x', $x)
            ->setParameter('y', $y)
            ->getResult();
    }

    /** @return Livre[] */
    public function findByPrixPages10Trie(float $x, int $y): array
    {   
        $em =  $this->getEntityManager();
        
        $query=$em->createQuery('SELECT l FROM App\Entity\Livre l WHERE l.prix > :x AND l.nbPages < :y ORDER BY l.prix DESC, l.id ASC')
            ->setParameter('x', $x)
            ->setParameter('y', $y)
            ->setMaxResults(10);

        return $query->getResult();
    }

    /** @return Livre[] */
    public function findByPrixPagesAuteurTrie(float $x, int $y): array
    {
        return $this->getEntityManager()
            ->createQuery('SELECT DISTINCT l FROM App\Entity\Livre l JOIN l.auteurs a WHERE l.prix > :x AND l.nbPages < :y AND a.prenom = :prenom AND a.nom = :nom ORDER BY l.prix DESC')
            ->setParameter('x', $x)
            ->setParameter('y', $y)
            ->setParameter('prenom', 'Fabien')
            ->setParameter('nom', 'Potencier')
            ->getResult();
    }
}
