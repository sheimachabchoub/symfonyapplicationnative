<?php

namespace App\Tests\Controller;

use App\Entity\Auteur;
use App\Entity\Category;
use App\Entity\Emprunt;
use App\Tests\BibliothequeTestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class BibliothequeControllerTest extends WebTestCase
{
    use BibliothequeTestTrait;
    use ClockSensitiveTrait;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        self::mockTime('2026-03-01 10:00:00');
        $this->viderBase();
    }

    public function testLaRechercheTrouveParTitreAuteurEtCategorie(): void
    {
        $roman = (new Category())->setDesignation('Roman');
        $this->em->persist($roman);
        $hugo = $this->creerLivre('Les Misérables', categorie: $roman, isbn: '9782070409228');
        $hugo->addAuteur((new Auteur())->setNom('Hugo')->setPrenom('Victor'));
        $this->em->persist($hugo->getAuteurs()->first());
        $this->creerLivre('Apprendre Symfony', isbn: '9782070409235');
        $this->em->flush();

        foreach (['misérables', 'Hugo', 'Victor Hugo', 'roman'] as $terme) {
            $crawler = $this->client->request('GET', '/livre', ['q' => $terme]);
            self::assertResponseIsSuccessful();
            $titres = $crawler->filter('tbody td:first-child a')->each(fn ($a) => $a->text());
            self::assertSame(['Les Misérables'], $titres, "Recherche « $terme »");
        }

        $crawler = $this->client->request('GET', '/livre', ['categorie' => $roman->getId()]);
        self::assertCount(1, $crawler->filter('tbody td:first-child a'));
    }

    public function testLesPagesDeGestionExigentUneConnexion(): void
    {
        foreach (['/emprunt', '/tableau-de-bord', '/adherent', '/livre/new', '/mes-emprunts'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseRedirects('/connexion', null, $url);
        }
    }

    public function testUnAdherentNAccedePasAuxPagesDuBibliothecaire(): void
    {
        $this->client->loginUser($this->creerUtilisateur());

        foreach (['/emprunt', '/tableau-de-bord', '/adherent', '/livre/new', '/admin'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }

        $this->client->request('GET', '/mes-emprunts');
        self::assertResponseIsSuccessful();
    }

    public function testConnexionParFormulaire(): void
    {
        $hasher = static::getContainer()->get('security.user_password_hasher');
        $user = $this->creerUtilisateur('lecteur@test.tn');
        $user->setPassword($hasher->hashPassword($user, 'secret123'));
        $this->em->flush();

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', ['_username' => 'lecteur@test.tn', '_password' => 'secret123']);
        self::assertResponseRedirects();
        $this->client->followRedirect();

        $this->client->request('GET', '/mes-emprunts');
        self::assertResponseIsSuccessful();
    }

    public function testLeBibliothecairePreteUnLivrePuisEnregistreLeRetour(): void
    {
        $livre = $this->creerLivre(exemplaires: 1);
        $adherent = $this->creerUtilisateur();
        $this->client->loginUser($this->creerUtilisateur('admin@test.tn', admin: true));

        $this->client->request('GET', '/emprunt/new');
        $this->client->submitForm('Enregistrer l\'emprunt', [
            'emprunt[livre]' => $livre->getId(),
            'emprunt[adherent]' => $adherent->getId(),
        ]);
        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', '15/03/2026');

        // Le seul exemplaire est sorti : un second prêt est refusé avec un message
        $this->client->request('GET', '/emprunt/new');
        $this->client->submitForm('Enregistrer l\'emprunt', [
            'emprunt[livre]' => $livre->getId(),
            'emprunt[adherent]' => $adherent->getId(),
        ]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form[name="emprunt"]', 'Aucun exemplaire');

        // Retour
        $emprunt = $this->em->getRepository(Emprunt::class)->findOneBy([]);
        $this->client->request('GET', '/emprunt/'.$emprunt->getId());
        $this->client->submitForm('Retour');
        self::assertResponseRedirects();

        $this->em->clear();
        self::assertNotNull($this->em->find(Emprunt::class, $emprunt->getId())->getDateRetourReelle());
    }

    public function testLeTableauDeBordAfficheLesEmpruntsEnRetard(): void
    {
        $service = static::getContainer()->get(\App\Service\EmpruntService::class);
        $service->emprunter($this->creerLivre('Livre oublié'), $this->creerUtilisateur());
        $this->client->loginUser($this->creerUtilisateur('admin@test.tn', admin: true));

        self::mockTime('2026-04-01');
        $this->client->request('GET', '/tableau-de-bord');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'Livre oublié');
        self::assertSelectorTextContains('table', '17 jours');
    }

    public function testLeBackOfficeEasyAdminSAfficheDepuisLeTableauDeBord(): void
    {
        $this->creerLivre('Livre du back-office');
        $this->client->loginUser($this->creerUtilisateur('admin@test.tn', admin: true));

        $this->client->request('GET', '/admin');
        self::assertResponseRedirects('/tableau-de-bord');

        $this->client->request('GET', '/admin/livre');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Livre du back-office');
    }
}
