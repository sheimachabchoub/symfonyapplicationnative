<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Crée un compte (indispensable pour le tout premier bibliothécaire).
 *
 *   php bin/console app:create-user admin@biblio.tn motdepasse Admin Biblio --admin
 */
#[AsCommand(name: 'app:create-user', description: 'Crée un adhérent ou un bibliothécaire (--admin)')]
final class CreateUserCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Email (identifiant de connexion)')] string $email,
        #[Argument('Mot de passe en clair')] string $password,
        #[Argument('Nom')] string $nom,
        #[Argument('Prénom')] string $prenom,
        #[Option('Donner le rôle ROLE_ADMIN (bibliothécaire)')] bool $admin = false,
    ): int {
        $user = (new User())
            ->setEmail($email)
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setRoles($admin ? [User::ROLE_ADMIN] : []);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $errors = $this->validator->validate($user);
        if (\count($errors) > 0) {
            foreach ($errors as $error) {
                $io->error($error->getPropertyPath().' : '.$error->getMessage());
            }

            return Command::FAILURE;
        }

        $this->em->persist($user);
        $this->em->flush();

        $io->success(\sprintf('%s créé : %s', $admin ? 'Bibliothécaire' : 'Adhérent', $email));

        return Command::SUCCESS;
    }
}
