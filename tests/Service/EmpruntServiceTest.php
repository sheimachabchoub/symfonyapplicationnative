<?php

namespace App\Tests\Service;

use App\Service\EmpruntException;
use App\Service\EmpruntService;
use App\Tests\BibliothequeTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

final class EmpruntServiceTest extends KernelTestCase
{
    use BibliothequeTestTrait;
    use ClockSensitiveTrait;

    private EmpruntService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        self::mockTime('2026-03-01 10:00:00');
        $this->viderBase();
        $this->service = static::getContainer()->get(EmpruntService::class);
    }

    public function testEmprunterFixeLesDatesEtReduitLesExemplairesDisponibles(): void
    {
        $livre = $this->creerLivre(exemplaires: 2);
        $adherent = $this->creerUtilisateur();

        $emprunt = $this->service->emprunter($livre, $adherent);

        self::assertNotNull($emprunt->getId(), 'L\'emprunt doit être enregistré en base');
        self::assertSame('2026-03-01', $emprunt->getDateEmprunt()->format('Y-m-d'));
        self::assertSame('2026-03-15', $emprunt->getDateRetourPrevue()->format('Y-m-d'), 'Durée par défaut : 14 jours');
        self::assertSame(1, $this->service->exemplairesDisponibles($livre));
        self::assertTrue($this->service->estDisponible($livre));
    }

    public function testRefuseQuandAucunExemplaireNestDisponible(): void
    {
        $livre = $this->creerLivre(exemplaires: 1);
        $this->service->emprunter($livre, $this->creerUtilisateur('a@test.tn'));

        self::assertFalse($this->service->estDisponible($livre));

        $this->expectException(EmpruntException::class);
        $this->expectExceptionMessage('Aucun exemplaire');
        $this->service->emprunter($livre, $this->creerUtilisateur('b@test.tn'));
    }

    public function testLimiteLeNombreDEmpruntsParAdherent(): void
    {
        $adherent = $this->creerUtilisateur();
        $max = $this->service->getMaxEmpruntsParAdherent();

        for ($i = 1; $i <= $max; ++$i) {
            $this->service->emprunter($this->creerLivre('Livre '.$i, isbn: '978207040922'.$i), $adherent);
        }

        self::assertSame(0, $this->service->empruntsRestants($adherent));

        $this->expectException(EmpruntException::class);
        $this->expectExceptionMessage('maximum autorisé');
        $this->service->emprunter($this->creerLivre('Un de trop', isbn: '9782070409990'), $adherent);
    }

    public function testEnregistrerRetourLibereLExemplaire(): void
    {
        $livre = $this->creerLivre(exemplaires: 1);
        $emprunt = $this->service->emprunter($livre, $this->creerUtilisateur());

        self::mockTime('2026-03-10');
        $this->service->enregistrerRetour($emprunt);

        self::assertFalse($emprunt->isEnCours());
        self::assertSame('2026-03-10', $emprunt->getDateRetourReelle()->format('Y-m-d'));
        self::assertSame(1, $this->service->exemplairesDisponibles($livre));
    }

    public function testUnRetourNePeutPasEtreEnregistreDeuxFois(): void
    {
        $emprunt = $this->service->emprunter($this->creerLivre(), $this->creerUtilisateur());
        $this->service->enregistrerRetour($emprunt);

        $this->expectException(EmpruntException::class);
        $this->service->enregistrerRetour($emprunt);
    }

    public function testRepereLesEmpruntsEnRetard(): void
    {
        $adherent = $this->creerUtilisateur();
        $enRetard = $this->service->emprunter($this->creerLivre('En retard', isbn: '9782070409228'), $adherent);
        $rendu = $this->service->emprunter($this->creerLivre('Rendu', isbn: '9782070409235'), $adherent);
        $this->service->enregistrerRetour($rendu);

        // Le 15/03 est le dernier jour autorisé : pas encore en retard
        self::mockTime('2026-03-15');
        self::assertSame([], $this->service->empruntsEnRetard());

        self::mockTime('2026-03-18');
        $retards = $this->service->empruntsEnRetard();

        self::assertCount(1, $retards);
        self::assertSame($enRetard->getId(), $retards[0]->getId());
        self::assertSame(3, $retards[0]->getJoursDeRetard($this->service->aujourdhui()));
    }
}
