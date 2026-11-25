<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Auteur;
use App\Entity\Category;
use App\Entity\Editeur;
use App\Entity\Emprunt;
use App\Entity\Livre;
use App\Entity\Personne;
use App\Entity\User;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Parcourt TOUTES les routes de l'application avec le jeu de données de test (AppFixtures) :
 * - chaque page GET, pour un visiteur, un adhérent et le bibliothécaire ;
 * - les 7 API JSON /test/livres/{numero} et /somme/{a}/{b} ;
 * - les formulaires : création, modification, suppression, prêt, retour, connexion.
 */
final class ToutesLesRoutesTest extends WebTestCase
{
    /** Routes réservées au bibliothécaire (#[IsGranted('ROLE_ADMIN')] ou access_control ^/admin). */
    private const ROUTES_BIBLIOTHECAIRE = '/^(admin|app_adherent_|app_emprunt_|app_tableau_de_bord|app_(livre|auteur|category|editeur|personne)_(new|edit))/';

    /** Routes qui exigent seulement d'être connecté. */
    private const ROUTES_CONNECTE = '/^app_mes_emprunts$/';

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        // Base de test remplie avec le jeu de données (vidée avant)
        $executor = new ORMExecutor($this->em, new ORMPurger($this->em));
        $executor->execute([static::getContainer()->get(AppFixtures::class)]);
    }

    public function testToutesLesPagesGetRepondentSansErreurPourChaqueProfil(): void
    {
        $urls = $this->urlsGet();
        self::assertGreaterThan(50, \count($urls), 'Toutes les routes GET de l\'application sont parcourues');

        $profils = [
            'visiteur' => null,
            'adherent' => $this->utilisateur('sami.benali@biblio.tn'),
            'bibliothecaire' => $this->utilisateur(AppFixtures::BIBLIOTHECAIRE),
        ];

        $erreurs = [];
        foreach ($profils as $profil => $user) {
            null === $user ? $this->client->restart() : $this->client->loginUser($user);
            $this->client->disableReboot();

            foreach ($urls as $route => $url) {
                $this->client->request('GET', $url);
                $code = $this->client->getResponse()->getStatusCode();
                $attendu = $this->codeAttendu($route, $profil);

                if ($code >= 500 || !\in_array($code, $attendu, true)) {
                    $erreurs[] = \sprintf('%s %s (%s) : %d, attendu %s', $route, $url, $profil, $code, implode(' ou ', $attendu));
                }
            }
        }

        self::assertSame([], $erreurs, "Routes en erreur :\n".implode("\n", $erreurs));
    }

    /** @return list<int> */
    private function codeAttendu(string $route, string $profil): array
    {
        $reservee = 1 === preg_match(self::ROUTES_BIBLIOTHECAIRE, $route);
        $connecte = 1 === preg_match(self::ROUTES_CONNECTE, $route);

        return match (true) {
            'app_logout' === $route => [302],
            'app_login' === $route && 'visiteur' !== $profil => [200, 302],
            'visiteur' === $profil && ($reservee || $connecte) => [302], // vers /connexion
            'adherent' === $profil && $reservee => [403],
            'admin' === $route => [302], // le tableau de bord EasyAdmin redirige vers /tableau-de-bord
            default => [200],
        };
    }

    public function testLesSeptApiJsonDesExercicesRenvoientLesBonsLivres(): void
    {
        $reponses = [];
        for ($numero = 1; $numero <= 7; ++$numero) {
            $this->client->request('GET', '/test/livres/'.$numero);
            self::assertResponseIsSuccessful();
            self::assertResponseHeaderSame('Content-Type', 'application/json');
            $reponses[$numero] = json_decode($this->client->getResponse()->getContent(), true);
            self::assertSame($numero, $reponses[$numero]['question']);
            self::assertGreaterThan(0, $reponses[$numero]['nombre'], "L'API $numero renvoie des livres");
        }

        // 1 : prix > 20
        foreach ($reponses[1]['livres'] as $livre) {
            self::assertGreaterThan(20, $livre['prix']);
        }
        // 2 : prix > 20 et moins de 300 pages
        foreach ($reponses[2]['livres'] as $livre) {
            self::assertGreaterThan(20, $livre['prix']);
            self::assertLessThan(300, $livre['nbPages']);
        }
        // 3 et 5 : au plus 10 résultats
        self::assertLessThanOrEqual(10, $reponses[3]['nombre']);
        self::assertLessThanOrEqual(10, $reponses[5]['nombre']);
        // 4 : triés par prix décroissant
        $prix = array_column($reponses[4]['livres'], 'prix');
        $trie = $prix;
        rsort($trie);
        self::assertSame($trie, $prix);
        // 7 : seulement les livres de Fabien Potencier (> 20 TND, < 300 pages), du plus cher au moins cher
        self::assertSame(['Symfony 5 : la quintessence', 'Symfony : le guide du développeur', 'Twig en pratique'], array_column($reponses[7]['livres'], 'titre'));
        foreach ($reponses[7]['livres'] as $livre) {
            self::assertSame(['Fabien Potencier'], $livre['auteurs']);
        }

        // Numéro hors de 1 à 7 : route introuvable
        $this->client->request('GET', '/test/livres/8');
        self::assertResponseStatusCodeSame(404);
    }

    public function testLaRouteSommeCalcule(): void
    {
        $this->client->request('GET', '/somme/12/30');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('42', $this->client->getResponse()->getContent());
    }

    public function testLeCatalogueAfficheLesDonneesDeTest(): void
    {
        $this->client->request('GET', '/livre?q=Hugo');
        self::assertSelectorTextContains('table', 'Les Misérables');
        self::assertSelectorTextContains('table', 'Notre-Dame de Paris');
        self::assertSelectorTextNotContains('table', 'Germinal');

        // « Vol de nuit » (1 exemplaire) est emprunté : indisponible
        $this->client->request('GET', '/livre?q=Vol de nuit');
        self::assertSelectorTextContains('table', 'Indisponible');
    }

    public function testLeTableauDeBordListeLesEmpruntsEnRetard(): void
    {
        $this->client->loginUser($this->utilisateur(AppFixtures::BIBLIOTHECAIRE));
        $this->client->request('GET', '/tableau-de-bord');

        foreach (['Germinal', 'Dix petits nègres', 'Madame Bovary', 'Vol de nuit'] as $titre) {
            self::assertSelectorTextContains('table', $titre);
        }
        self::assertSelectorTextNotContains('table', 'Le Petit Prince', 'Un emprunt dans les temps n\'est pas en retard');
    }

    public function testMesEmpruntsNAfficheQueLesEmpruntsDeLAdherentConnecte(): void
    {
        $this->client->loginUser($this->utilisateur('sami.benali@biblio.tn'));
        $this->client->request('GET', '/mes-emprunts');

        self::assertSelectorTextContains('body', 'Les Misérables');
        self::assertSelectorTextContains('body', 'Le Petit Prince');
        self::assertSelectorTextNotContains('body', 'Germinal');
    }

    public function testConnexionAvecUnCompteDeTestPuisDeconnexion(): void
    {
        $crawler = $this->client->request('GET', '/connexion');
        $this->client->submit($crawler->selectButton('Se connecter')->form([
            '_username' => 'amira.trabelsi@biblio.tn',
            '_password' => AppFixtures::MOT_DE_PASSE,
        ]));
        $this->client->followRedirect();
        self::assertSelectorTextContains('nav', 'Amira');

        $this->client->request('GET', '/deconnexion');
        $this->client->followRedirect();
        self::assertSelectorTextContains('nav', 'Connexion');
    }

    public function testCreerModifierSupprimerChaqueEntiteDuCatalogue(): void
    {
        $this->client->loginUser($this->utilisateur(AppFixtures::BIBLIOTHECAIRE));

        // classe, valeurs du formulaire, critère de recherche, champ modifié, nouvelle valeur, boutons [créer, modifier, supprimer]
        $cas = [
            'auteur' => [Auteur::class, ['auteur[nom]' => 'Test', 'auteur[prenom]' => 'Auteur', 'auteur[biographie]' => 'Biographie.'], ['nom' => 'Test'], 'auteur[nom]', 'Test modifié', ['Enregistrer', 'Update', 'Supprimer']],
            'category' => [Category::class, ['category[designation]' => 'Catégorie test', 'category[description]' => 'Description.'], ['designation' => 'Catégorie test'], 'category[designation]', 'Catégorie modifiée', ['Enregistrer', 'Update', 'Supprimer']],
            'editeur' => [Editeur::class, ['editeur[nomEditeur]' => 'Éditeur test', 'editeur[pays]' => 'Tunisie', 'editeur[adresse]' => 'Tunis', 'editeur[telephone]' => '+216 71 000 000'], ['nomEditeur' => 'Éditeur test'], 'editeur[nomEditeur]', 'Éditeur modifié', ['Enregistrer', 'Update', 'Supprimer']],
            'personne' => [Personne::class, ['personne[nom]' => 'Test', 'personne[prenom]' => 'Personne', 'personne[age]' => '30', 'personne[salaire]' => '1500'], ['nom' => 'Test'], 'personne[nom]', 'Test modifié', ['Save', 'Update', 'Delete']],
        ];

        foreach ($cas as $prefixe => [$classe, $valeurs, $critere, $champModifie, $valeurModifiee, [$boutonCreer, $boutonModifier, $boutonSupprimer]]) {
            // Création
            $crawler = $this->client->request('GET', "/$prefixe/new");
            $this->client->submit($crawler->selectButton($boutonCreer)->form($valeurs));
            self::assertTrue($this->client->getResponse()->isRedirect(), "Création $prefixe : ".$this->client->getResponse()->getStatusCode());
            $entite = $this->em->getRepository($classe)->findOneBy($critere);
            self::assertNotNull($entite, "$prefixe créé");
            $id = $entite->getId();

            // Modification
            $crawler = $this->client->request('GET', "/$prefixe/$id/edit");
            $this->client->submit($crawler->selectButton($boutonModifier)->form([$champModifie => $valeurModifiee]));
            self::assertTrue($this->client->getResponse()->isRedirect(), "Modification $prefixe");
            $this->em->clear();
            $this->client->request('GET', "/$prefixe/$id");
            self::assertSelectorTextContains('body', $valeurModifiee);

            // Suppression
            $crawler = $this->client->request('GET', "/$prefixe/$id");
            $this->client->submit($crawler->selectButton($boutonSupprimer)->form());
            $this->em->clear();
            self::assertNull($this->em->getRepository($classe)->find($id), "$prefixe supprimé");
        }
    }

    public function testCreerUnLivreEtRefuserDeSupprimerUnLivreQuiADesEmprunts(): void
    {
        $this->client->loginUser($this->utilisateur(AppFixtures::BIBLIOTHECAIRE));
        $editeur = $this->em->getRepository(Editeur::class)->findOneBy(['nomEditeur' => 'Gallimard']);

        $crawler = $this->client->request('GET', '/livre/new');
        $this->client->submit($crawler->selectButton('Ajouter')->form([
            'livre[titre]' => 'La Chute',
            'livre[isbn]' => AppFixtures::isbn13(900001),
            'livre[editeur]' => (string) $editeur->getId(),
            'livre[dateEdition]' => '1956-05-01',
            'livre[nbPages]' => '160',
            'livre[nbExemplaires]' => '2',
            'livre[prix]' => '9.5',
        ]));
        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Livre ajouté au catalogue');

        // « Germinal » a un historique d'emprunts : suppression refusée
        $germinal = $this->em->getRepository(Livre::class)->findOneBy(['titre' => 'Germinal']);
        $crawler = $this->client->request('GET', '/livre/'.$germinal->getId());
        $this->client->submit($crawler->selectButton('Supprimer')->form());
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'historique d\'emprunts');
    }

    public function testPreterPuisRendreUnLivreEtRespecterLePlafondDeTroisEmprunts(): void
    {
        $this->client->loginUser($this->utilisateur(AppFixtures::BIBLIOTHECAIRE));
        $karim = $this->utilisateur('karim.bouazizi@biblio.tn'); // 1 emprunt en cours
        $livres = ['Candide', 'Les Robots', 'Le Misanthrope'];

        // Deux prêts acceptés : Karim atteint 3 emprunts
        foreach (\array_slice($livres, 0, 2) as $titre) {
            $this->preter($karim, $titre);
            self::assertSelectorTextContains('.alert-success', 'Emprunt enregistré');
        }

        // Le 4e est refusé (plafond de 3)
        $crawler = $this->preter($karim, $livres[2]);
        self::assertSelectorTextContains('body', 'maximum autorisé');

        // Retour d'un livre
        $emprunt = $this->em->getRepository(Emprunt::class)->findOneBy(['adherent' => $karim, 'livre' => $this->em->getRepository(Livre::class)->findOneBy(['titre' => 'Candide'])]);
        $crawler = $this->client->request('GET', '/emprunt/'.$emprunt->getId());
        $this->client->submit($crawler->selectButton('Retour')->form());
        $this->client->followRedirect();
        $this->em->clear();
        self::assertNotNull($this->em->getRepository(Emprunt::class)->find($emprunt->getId())->getDateRetourReelle());
    }

    public function testCreerUnAdherent(): void
    {
        $this->client->loginUser($this->utilisateur(AppFixtures::BIBLIOTHECAIRE));
        $crawler = $this->client->request('GET', '/adherent/new');
        $this->client->submit($crawler->selectButton('Créer le compte')->form([
            'user[prenom]' => 'Rania',
            'user[nom]' => 'Chaabane',
            'user[email]' => 'rania.chaabane@biblio.tn',
            'user[plainPassword][first]' => 'MotDePasse123!',
            'user[plainPassword][second]' => 'MotDePasse123!',
        ]));
        self::assertResponseRedirects();
        self::assertNotNull($this->em->getRepository(User::class)->findOneBy(['email' => 'rania.chaabane@biblio.tn']));
    }

    private function preter(User $adherent, string $titre): \Symfony\Component\DomCrawler\Crawler
    {
        $livre = $this->em->getRepository(Livre::class)->findOneBy(['titre' => $titre]);
        $crawler = $this->client->request('GET', '/emprunt/new');
        $this->client->submit($crawler->selectButton('Enregistrer l\'emprunt')->form([
            'emprunt[livre]' => (string) $livre->getId(),
            'emprunt[adherent]' => (string) $adherent->getId(),
        ]));

        return $this->client->getResponse()->isRedirect() ? $this->client->followRedirect() : $this->client->getCrawler();
    }

    private function utilisateur(string $email): User
    {
        return $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    /**
     * Une URL par route GET, avec des identifiants pris dans le jeu de données.
     *
     * @return array<string, string> nom de route => URL
     */
    private function urlsGet(): array
    {
        $ids = [
            'livre' => $this->premierId(Livre::class),
            'auteur' => $this->premierId(Auteur::class),
            'category' => $this->premierId(Category::class),
            'editeur' => $this->premierId(Editeur::class),
            'emprunt' => $this->premierId(Emprunt::class),
            'adherent' => $this->utilisateur('sami.benali@biblio.tn')->getId(),
            'personne' => $this->premierId(Personne::class),
        ];

        $router = static::getContainer()->get(RouterInterface::class);
        $urls = [];
        foreach ($router->getRouteCollection() as $nom => $route) {
            $methodes = $route->getMethods();
            if (str_starts_with($nom, '_') || str_contains($nom, 'ea_ui_kit') || ([] !== $methodes && !\in_array('GET', $methodes, true))) {
                continue;
            }
            // Routes techniques d'EasyAdmin appelées en AJAX avec des paramètres précis
            if (preg_match('/_(autocomplete|render_filters)$/', $nom)) {
                continue;
            }

            $entite = preg_match('/^(?:app|admin)_([a-z]+)_/', $nom, $m) ? $m[1] : null;
            $parametres = [];
            foreach ($route->compile()->getPathVariables() as $variable) {
                $parametres[$variable] = match ($variable) {
                    'id', 'entityId' => $ids[$entite] ?? throw new \LogicException("Pas d'identifiant pour $nom"),
                    'numero' => 1,
                    'a' => 2,
                    'b' => 3,
                    default => throw new \LogicException("Paramètre inconnu $variable pour $nom"),
                };
            }
            $urls[$nom] = $router->generate($nom, $parametres);
        }

        // La déconnexion en dernier : elle ferme la session des pages suivantes
        $deconnexion = $urls['app_logout'];
        unset($urls['app_logout']);
        $urls['app_logout'] = $deconnexion;

        return $urls;
    }

    private function premierId(string $classe): int
    {
        return $this->em->getRepository($classe)->findOneBy([])->getId();
    }
}
