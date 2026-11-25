<?php

namespace App\DataFixtures;

use App\Entity\Auteur;
use App\Entity\Category;
use App\Entity\Editeur;
use App\Entity\Emprunt;
use App\Entity\Livre;
use App\Entity\Personne;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de données de test : une bibliothèque réaliste.
 *
 *   php bin/console doctrine:fixtures:load
 *
 * ATTENTION : la commande VIDE la base avant de la remplir.
 *
 * Comptes créés, tous avec le mot de passe de test MOT_DE_PASSE :
 *   - bibliothécaire : biblio@biblio.tn
 *   - adhérents      : voir ADHERENTS (sami.benali@biblio.tn, amira.trabelsi@biblio.tn…)
 *
 * Les dates des emprunts sont calculées à partir d'aujourd'hui : chaque chargement
 * donne des emprunts en cours, bientôt à rendre, en retard et rendus.
 */
class AppFixtures extends Fixture
{
    /** Mot de passe de test de tous les comptes (environnement de développement uniquement). */
    public const MOT_DE_PASSE = 'biblio2026';

    public const BIBLIOTHECAIRE = 'biblio@biblio.tn';

    /** @var list<array{0: string, 1: string, 2: string}> prénom, nom, email */
    public const ADHERENTS = [
        ['Sami', 'Ben Ali', 'sami.benali@biblio.tn'],
        ['Amira', 'Trabelsi', 'amira.trabelsi@biblio.tn'],
        ['Youssef', 'Gharbi', 'youssef.gharbi@biblio.tn'],
        ['Leila', 'Mansouri', 'leila.mansouri@biblio.tn'],
        ['Karim', 'Bouazizi', 'karim.bouazizi@biblio.tn'],
        ['Ines', 'Haddad', 'ines.haddad@biblio.tn'],
        ['Mehdi', 'Jaziri', 'mehdi.jaziri@biblio.tn'],
        ['Nour', 'Ayari', 'nour.ayari@biblio.tn'],
    ];

    private const CATEGORIES = [
        'Roman' => 'Romans classiques et contemporains.',
        'Science-fiction' => 'Anticipation, robots et voyages extraordinaires.',
        'Policier' => 'Enquêtes, mystères et suspense.',
        'Poésie' => 'Recueils de poèmes.',
        'Théâtre' => 'Comédies et tragédies.',
        'Philosophie' => 'Essais et pensée.',
        'Informatique' => 'Programmation, PHP, Symfony et génie logiciel.',
        'Jeunesse' => 'Livres pour les jeunes lecteurs.',
    ];

    /** nom, pays, adresse, téléphone */
    private const EDITEURS = [
        ['Gallimard', 'France', '5 rue Gaston-Gallimard, 75007 Paris', '+33 1 49 54 42 00'],
        ['Le Livre de Poche', 'France', '21 rue du Montparnasse, 75006 Paris', '+33 1 43 92 30 00'],
        ['Flammarion', 'France', '87 quai Panhard-et-Levassor, Paris', '+33 1 40 51 31 00'],
        ['Folio', 'France', '5 rue Gaston-Gallimard, 75007 Paris', '+33 1 49 54 42 01'],
        ['Eyrolles', 'France', '61 boulevard Saint-Germain, 75005 Paris', '+33 1 44 41 11 11'],
        ['ENI', 'France', 'Rue Benjamin-Franklin, Saint-Herblain', '+33 2 51 80 15 15'],
        ['Dunod', 'France', '11 rue Paul-Bert, 92240 Malakoff', '+33 1 41 23 66 00'],
        ['Cérès Éditions', 'Tunisie', '6 rue Alain Savary, 1002 Tunis', '+216 71 280 505'],
        ['Sud Éditions', 'Tunisie', '79 rue de Palestine, 1002 Tunis', '+216 71 785 179'],
    ];

    /** prénom, nom, biographie */
    private const AUTEURS = [
        'hugo' => ['Victor', 'Hugo', 'Poète, dramaturge et romancier français (1802-1885).'],
        'zola' => ['Émile', 'Zola', 'Romancier naturaliste français, auteur des Rougon-Macquart (1840-1902).'],
        'camus' => ['Albert', 'Camus', 'Écrivain et philosophe français né en Algérie, prix Nobel 1957.'],
        'flaubert' => ['Gustave', 'Flaubert', 'Romancier français, maître du réalisme (1821-1880).'],
        'moliere' => ['Jean-Baptiste', 'Molière', 'Dramaturge et comédien français (1622-1673).'],
        'verne' => ['Jules', 'Verne', 'Écrivain français des Voyages extraordinaires (1828-1905).'],
        'christie' => ['Agatha', 'Christie', 'Romancière britannique, reine du roman policier (1890-1976).'],
        'asimov' => ['Isaac', 'Asimov', 'Écrivain américain de science-fiction (1920-1992).'],
        'stex' => ['Antoine', 'de Saint-Exupéry', 'Écrivain et aviateur français (1900-1944).'],
        'baudelaire' => ['Charles', 'Baudelaire', 'Poète français (1821-1867).'],
        'voltaire' => ['François-Marie', 'Voltaire', 'Philosophe et écrivain des Lumières (1694-1778).'],
        'beauvoir' => ['Simone', 'de Beauvoir', 'Philosophe et romancière française (1908-1986).'],
        'benjelloun' => ['Tahar', 'Ben Jelloun', 'Écrivain marocain de langue française, prix Goncourt 1987.'],
        'memmi' => ['Albert', 'Memmi', 'Écrivain et essayiste tunisien (1920-2020).'],
        'chebbi' => ['Abou el Kacem', 'Chebbi', 'Poète tunisien, auteur de « La volonté de vivre » (1909-1934).'],
        'potencier' => ['Fabien', 'Potencier', 'Créateur du framework Symfony.'],
        'martin' => ['Robert C.', 'Martin', 'Ingénieur logiciel américain, auteur de Clean Code.'],
        'fowler' => ['Martin', 'Fowler', 'Auteur britannique spécialiste du refactoring et de l\'architecture logicielle.'],
    ];

    /**
     * titre, auteurs, catégorie, éditeur, année, pages, prix (TND), exemplaires, résumé.
     *
     * Les livres de Fabien Potencier à plus de 20 TND et moins de 300 pages alimentent
     * l'exercice /test/livres/7.
     */
    private const LIVRES = [
        ['Les Misérables', ['hugo'], 'Roman', 'Le Livre de Poche', 1862, 1664, 32.5, 3, 'Le destin de Jean Valjean, ancien forçat, dans la France du XIXe siècle.'],
        ['Notre-Dame de Paris', ['hugo'], 'Roman', 'Folio', 1831, 940, 19.9, 2, 'Quasimodo, Esmeralda et Frollo autour de la cathédrale.'],
        ['Les Contemplations', ['hugo'], 'Poésie', 'Gallimard', 1856, 512, 14.5, 1, 'Recueil de poèmes marqué par la mort de Léopoldine.'],
        ['Germinal', ['zola'], 'Roman', 'Le Livre de Poche', 1885, 600, 12.9, 2, 'La grève des mineurs du Nord.'],
        ['L\'Assommoir', ['zola'], 'Roman', 'Folio', 1877, 576, 11.5, 1, 'La déchéance de Gervaise dans le Paris ouvrier.'],
        ['L\'Étranger', ['camus'], 'Roman', 'Folio', 1942, 192, 9.9, 3, 'Meursault, un homme étranger à sa propre vie.'],
        ['La Peste', ['camus'], 'Roman', 'Folio', 1947, 352, 11.0, 2, 'Une épidémie frappe la ville d\'Oran.'],
        ['Le Mythe de Sisyphe', ['camus'], 'Philosophie', 'Folio', 1942, 192, 9.0, 1, 'Essai sur l\'absurde.'],
        ['Madame Bovary', ['flaubert'], 'Roman', 'Le Livre de Poche', 1857, 512, 10.5, 2, 'Les rêves et les désillusions d\'Emma Bovary.'],
        ['Le Misanthrope', ['moliere'], 'Théâtre', 'Folio', 1666, 160, 5.5, 2, 'Alceste refuse l\'hypocrisie de la société.'],
        ['Le Malade imaginaire', ['moliere'], 'Théâtre', 'Le Livre de Poche', 1673, 192, 5.9, 1, 'Argan, hypocondriaque, veut marier sa fille à un médecin.'],
        ['Vingt mille lieues sous les mers', ['verne'], 'Science-fiction', 'Le Livre de Poche', 1870, 640, 9.9, 2, 'Le capitaine Nemo et le Nautilus.'],
        ['Le Tour du monde en quatre-vingts jours', ['verne'], 'Jeunesse', 'Le Livre de Poche', 1872, 320, 7.5, 3, 'Phileas Fogg parie qu\'il fera le tour du monde en 80 jours.'],
        ['Le Crime de l\'Orient-Express', ['christie'], 'Policier', 'Le Livre de Poche', 1934, 256, 8.9, 2, 'Hercule Poirot enquête dans un train bloqué par la neige.'],
        ['Dix petits nègres', ['christie'], 'Policier', 'Le Livre de Poche', 1939, 224, 8.5, 1, 'Dix invités sur une île disparaissent un à un.'],
        ['Fondation', ['asimov'], 'Science-fiction', 'Folio', 1951, 416, 10.9, 2, 'La psychohistoire pour sauver la civilisation galactique.'],
        ['Les Robots', ['asimov'], 'Science-fiction', 'Folio', 1950, 320, 9.5, 1, 'Les trois lois de la robotique mises à l\'épreuve.'],
        ['Le Petit Prince', ['stex'], 'Jeunesse', 'Gallimard', 1943, 96, 8.0, 4, 'Un aviateur rencontre un petit prince venu d\'une autre planète.'],
        ['Vol de nuit', ['stex'], 'Roman', 'Folio', 1931, 176, 7.0, 1, 'Les pilotes de l\'Aéropostale en Amérique du Sud.'],
        ['Les Fleurs du mal', ['baudelaire'], 'Poésie', 'Le Livre de Poche', 1857, 384, 6.9, 2, 'Le recueil majeur de Baudelaire.'],
        ['Candide', ['voltaire'], 'Philosophie', 'Folio', 1759, 160, 4.9, 2, 'Candide découvre que tout ne va pas pour le mieux.'],
        ['Le Deuxième Sexe', ['beauvoir'], 'Philosophie', 'Folio', 1949, 1000, 24.0, 1, 'Essai fondateur sur la condition des femmes.'],
        ['La Nuit sacrée', ['benjelloun'], 'Roman', 'Flammarion', 1987, 192, 21.5, 1, 'Prix Goncourt 1987.'],
        ['La Statue de sel', ['memmi'], 'Roman', 'Folio', 1953, 384, 22.0, 2, 'L\'enfance d\'Alexandre Mordekhaï Benillouche à Tunis.'],
        ['Portrait du colonisé', ['memmi'], 'Philosophie', 'Folio', 1957, 176, 21.0, 1, 'Analyse de la relation coloniale.'],
        ['Les Chants de la vie', ['chebbi'], 'Poésie', 'Cérès Éditions', 1955, 220, 25.0, 2, 'Recueil du poète tunisien, dont « La volonté de vivre ».'],
        ['Contes de Tunisie', [], 'Jeunesse', 'Sud Éditions', 2005, 120, 15.0, 2, 'Contes traditionnels tunisiens.'],
        ['Symfony 5 : la quintessence', ['potencier'], 'Informatique', 'Eyrolles', 2020, 280, 45.0, 2, 'Construire une application Symfony de bout en bout.'],
        ['Symfony : le guide du développeur', ['potencier'], 'Informatique', 'ENI', 2022, 250, 38.0, 1, 'Les bonnes pratiques du framework.'],
        ['Twig en pratique', ['potencier'], 'Informatique', 'ENI', 2021, 180, 28.0, 2, 'Le moteur de templates de Symfony.'],
        ['Composants Symfony avancés', ['potencier'], 'Informatique', 'Eyrolles', 2023, 420, 52.0, 1, 'Messenger, Workflow, Security en profondeur.'],
        ['Clean Code', ['martin'], 'Informatique', 'Eyrolles', 2008, 464, 48.0, 2, 'Écrire du code lisible et maintenable.'],
        ['Architecture propre', ['martin'], 'Informatique', 'Eyrolles', 2017, 290, 42.0, 1, 'Principes d\'architecture logicielle.'],
        ['Refactoring', ['fowler'], 'Informatique', 'Dunod', 2018, 420, 46.0, 1, 'Améliorer le code existant sans changer son comportement.'],
        ['Patterns d\'architecture', ['fowler'], 'Informatique', 'ENI', 2003, 260, 35.0, 1, 'Modèles de conception pour les applications d\'entreprise.'],
        ['PHP 8 pour les débutants', [], 'Informatique', 'ENI', 2021, 220, 29.0, 3, 'Apprendre PHP pas à pas.'],
    ];

    /**
     * Emprunts : adhérent, livre, emprunté il y a N jours, échéance dans N jours
     * (négatif = dépassée), rendu il y a N jours (null = pas encore rendu).
     *
     * Règles respectées : 3 emprunts en cours au plus par adhérent, jamais plus
     * d'emprunts en cours que d'exemplaires.
     */
    private const EMPRUNTS = [
        // En cours, dans les temps
        ['sami.benali@biblio.tn', 'Les Misérables', 3, 11, null],
        ['sami.benali@biblio.tn', 'Le Petit Prince', 5, 9, null],
        ['amira.trabelsi@biblio.tn', 'La Peste', 2, 12, null],
        ['youssef.gharbi@biblio.tn', 'Clean Code', 6, 8, null],
        ['leila.mansouri@biblio.tn', 'Symfony : le guide du développeur', 1, 13, null],
        ['karim.bouazizi@biblio.tn', 'Fondation', 10, 4, null],
        // Bientôt à rendre
        ['ines.haddad@biblio.tn', 'Le Crime de l\'Orient-Express', 13, 1, null],
        // En retard
        ['mehdi.jaziri@biblio.tn', 'Germinal', 25, -11, null],
        ['mehdi.jaziri@biblio.tn', 'Dix petits nègres', 20, -6, null],
        ['nour.ayari@biblio.tn', 'Madame Bovary', 18, -4, null],
        ['amira.trabelsi@biblio.tn', 'Vol de nuit', 16, -2, null],
        // Rendus (historique)
        ['sami.benali@biblio.tn', 'L\'Étranger', 60, -46, 50],
        ['sami.benali@biblio.tn', 'Candide', 40, -26, 30],
        ['amira.trabelsi@biblio.tn', 'Notre-Dame de Paris', 90, -76, 80],
        ['youssef.gharbi@biblio.tn', 'Twig en pratique', 45, -31, 35],
        ['youssef.gharbi@biblio.tn', 'Symfony 5 : la quintessence', 70, -56, 58],
        ['leila.mansouri@biblio.tn', 'Les Fleurs du mal', 33, -19, 20],
        ['karim.bouazizi@biblio.tn', 'Le Tour du monde en quatre-vingts jours', 50, -36, 40],
        ['ines.haddad@biblio.tn', 'Le Petit Prince', 75, -61, 65],
        ['nour.ayari@biblio.tn', 'La Statue de sel', 28, -14, 15],
        ['nour.ayari@biblio.tn', 'Les Misérables', 100, -86, 88],
        ['mehdi.jaziri@biblio.tn', 'Refactoring', 120, -106, 100],
    ];

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(2026); // mêmes valeurs à chaque chargement

        $categories = [];
        foreach (self::CATEGORIES as $designation => $description) {
            $categories[$designation] = (new Category())->setDesignation($designation)->setDescription($description);
            $manager->persist($categories[$designation]);
        }

        $editeurs = [];
        foreach (self::EDITEURS as [$nom, $pays, $adresse, $telephone]) {
            $editeurs[$nom] = (new Editeur())->setNomEditeur($nom)->setPays($pays)->setAdresse($adresse)->setTelephone($telephone);
            $manager->persist($editeurs[$nom]);
        }

        $auteurs = [];
        foreach (self::AUTEURS as $cle => [$prenom, $nom, $biographie]) {
            $auteurs[$cle] = (new Auteur())->setPrenom($prenom)->setNom($nom)->setBiographie($biographie);
            $manager->persist($auteurs[$cle]);
        }

        $livres = [];
        foreach (self::LIVRES as $i => [$titre, $cles, $categorie, $editeur, $annee, $pages, $prix, $exemplaires, $resume]) {
            $livre = (new Livre())
                ->setTitre($titre)
                ->setIsbn(self::isbn13(100000 + $i))
                ->setNbPages($pages)
                ->setPrix($prix)
                ->setNbExemplaires($exemplaires)
                ->setDateEdition(new \DateTime(\sprintf('%d-%02d-15', $annee, 1 + $i % 12)))
                ->setResume($resume)
                ->setCategory($categories[$categorie])
                ->setEditeur($editeurs[$editeur]);
            foreach ($cles as $cle) {
                $livre->addAuteur($auteurs[$cle]);
            }
            $manager->persist($livre);
            $livres[$titre] = $livre;
        }

        $manager->persist($this->utilisateur('Biblio', 'Admin', self::BIBLIOTHECAIRE, [User::ROLE_ADMIN]));
        $adherents = [];
        foreach (self::ADHERENTS as [$prenom, $nom, $email]) {
            $adherents[$email] = $this->utilisateur($prenom, $nom, $email, []);
            $manager->persist($adherents[$email]);
        }

        $aujourdhui = $this->clock->now()->setTime(0, 0);
        foreach (self::EMPRUNTS as [$email, $titre, $depuis, $echeance, $rendu]) {
            $manager->persist((new Emprunt())
                ->setAdherent($adherents[$email])
                ->setLivre($livres[$titre])
                ->setDateEmprunt($aujourdhui->modify(\sprintf('-%d days', $depuis)))
                ->setDateRetourPrevue($aujourdhui->modify(\sprintf('%+d days', $echeance)))
                ->setDateRetourReelle(null === $rendu ? null : $aujourdhui->modify(\sprintf('-%d days', $rendu))));
        }

        // Module d'exercice « Personne »
        for ($i = 0; $i < 12; ++$i) {
            $manager->persist((new Personne())
                ->setPrenom($faker->firstName())
                ->setNom($faker->lastName())
                ->setAge($faker->numberBetween(18, 65))
                ->setSalaire($faker->randomFloat(2, 900, 4500)));
        }

        $manager->flush();
    }

    /** @param list<string> $roles */
    private function utilisateur(string $prenom, string $nom, string $email, array $roles): User
    {
        $user = (new User())->setPrenom($prenom)->setNom($nom)->setEmail($email)->setRoles($roles);
        $user->setPassword($this->hasher->hashPassword($user, self::MOT_DE_PASSE));

        return $user;
    }

    /** ISBN-13 valide (préfixe 978, clé de contrôle calculée), accepté par #[Assert\Isbn]. */
    public static function isbn13(int $numero): string
    {
        $base = '978'.str_pad((string) $numero, 9, '0', \STR_PAD_LEFT);
        $somme = 0;
        foreach (str_split($base) as $i => $chiffre) {
            $somme += (int) $chiffre * (0 === $i % 2 ? 1 : 3);
        }

        return $base.((10 - $somme % 10) % 10);
    }
}
