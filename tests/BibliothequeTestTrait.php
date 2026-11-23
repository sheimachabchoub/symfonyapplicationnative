<?php

namespace App\Tests;

use App\Entity\Category;
use App\Entity\Editeur;
use App\Entity\Livre;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Vide la base de test et fournit des fabriques d'entités minimales.
 */
trait BibliothequeTestTrait
{
    private EntityManagerInterface $em;

    private function viderBase(): void
    {
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $connexion = $this->em->getConnection();
        $connexion->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['emprunt', 'livre_auteur', 'livre', 'auteur', 'category', 'editeur', '`user`'] as $table) {
            $connexion->executeStatement('DELETE FROM '.$table);
        }
        $connexion->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        $this->em->clear();
    }

    private function creerLivre(string $titre = 'Les Misérables', int $exemplaires = 1, ?Category $categorie = null, string $isbn = '9782070409228'): Livre
    {
        $editeur = (new Editeur())->setNomEditeur('Gallimard')->setPays('France')->setAdresse('Paris')->setTelephone('0100000000');
        $livre = (new Livre())
            ->setTitre($titre)
            ->setIsbn($isbn)
            ->setNbPages(250)
            ->setNbExemplaires($exemplaires)
            ->setPrix(25.0)
            ->setDateEdition(new \DateTime('2000-01-01'))
            ->setEditeur($editeur)
            ->setCategory($categorie);

        $this->em->persist($editeur);
        $this->em->persist($livre);
        $this->em->flush();

        return $livre;
    }

    private function creerUtilisateur(string $email = 'adherent@test.tn', bool $admin = false): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setNom('Test')
            ->setPrenom($admin ? 'Biblio' : 'Adhérent')
            ->setRoles($admin ? [User::ROLE_ADMIN] : [])
            ->setPassword('inutilise-en-test');

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
