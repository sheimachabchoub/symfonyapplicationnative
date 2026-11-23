<?php

namespace App\Entity;

use App\Repository\EmpruntRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmpruntRepository::class)]
#[ORM\Index(name: 'idx_emprunt_retour', fields: ['dateRetourReelle', 'dateRetourPrevue'])]
class Emprunt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'emprunts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Choisissez un livre.', groups: ['Default', 'creation'])]
    private ?Livre $livre = null;

    #[ORM\ManyToOne(inversedBy: 'emprunts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Choisissez un adhérent.', groups: ['Default', 'creation'])]
    private ?User $adherent = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateEmprunt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(propertyPath: 'dateEmprunt', message: 'La date de retour prévue doit être après la date d\'emprunt.')]
    private ?\DateTimeImmutable $dateRetourPrevue = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\GreaterThanOrEqual(propertyPath: 'dateEmprunt', message: 'La date de retour doit être après la date d\'emprunt.')]
    private ?\DateTimeImmutable $dateRetourReelle = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLivre(): ?Livre
    {
        return $this->livre;
    }

    public function setLivre(?Livre $livre): static
    {
        $this->livre = $livre;

        return $this;
    }

    public function getAdherent(): ?User
    {
        return $this->adherent;
    }

    public function setAdherent(?User $adherent): static
    {
        $this->adherent = $adherent;

        return $this;
    }

    public function getDateEmprunt(): ?\DateTimeImmutable
    {
        return $this->dateEmprunt;
    }

    public function setDateEmprunt(\DateTimeImmutable $dateEmprunt): static
    {
        $this->dateEmprunt = $dateEmprunt;

        return $this;
    }

    public function getDateRetourPrevue(): ?\DateTimeImmutable
    {
        return $this->dateRetourPrevue;
    }

    public function setDateRetourPrevue(?\DateTimeImmutable $dateRetourPrevue): static
    {
        $this->dateRetourPrevue = $dateRetourPrevue;

        return $this;
    }

    public function getDateRetourReelle(): ?\DateTimeImmutable
    {
        return $this->dateRetourReelle;
    }

    public function setDateRetourReelle(?\DateTimeImmutable $dateRetourReelle): static
    {
        $this->dateRetourReelle = $dateRetourReelle;

        return $this;
    }

    public function isEnCours(): bool
    {
        return null === $this->dateRetourReelle;
    }

    public function isEnRetard(\DateTimeImmutable $aujourdhui): bool
    {
        return $this->isEnCours() && $this->dateRetourPrevue < $aujourdhui->setTime(0, 0);
    }

    /** Nombre de jours de retard (0 si pas en retard). */
    public function getJoursDeRetard(\DateTimeImmutable $aujourdhui): int
    {
        if (!$this->isEnRetard($aujourdhui)) {
            return 0;
        }

        return (int) $this->dateRetourPrevue->diff($aujourdhui->setTime(0, 0))->days;
    }
}
