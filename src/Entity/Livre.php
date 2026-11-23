<?php

namespace App\Entity;

use App\Repository\LivreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LivreRepository::class)]
#[UniqueEntity(fields: ['isbn'], message: 'Un livre avec cet ISBN existe déjà.')]
class Livre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $titre = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive]
    private ?int $nbPages = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    #[Assert\LessThanOrEqual('today', message: 'La date d\'édition ne peut pas être dans le futur.')]
    private ?\DateTime $dateEdition = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?int $nbExemplaires = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private ?float $prix = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Isbn(message: 'Cet ISBN n\'est pas valide.')]
    private ?string $isbn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resume = null;

    #[ORM\ManyToOne(inversedBy: 'livres')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Choisissez un éditeur.')]
    private ?Editeur $editeur = null;

    #[ORM\ManyToOne(inversedBy: 'livres')]
    private ?Category $category = null;

    /** @var Collection<int, Auteur> */
    #[ORM\ManyToMany(targetEntity: Auteur::class, inversedBy: 'livres')]
    private Collection $auteurs;

    /** @var Collection<int, Emprunt> */
    #[ORM\OneToMany(targetEntity: Emprunt::class, mappedBy: 'livre')]
    private Collection $emprunts;

    public function __construct()
    {
        $this->auteurs = new ArrayCollection();
        $this->emprunts = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->titre;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getNbPages(): ?int
    {
        return $this->nbPages;
    }

    public function setNbPages(int $nbPages): static
    {
        $this->nbPages = $nbPages;

        return $this;
    }

    public function getNbExemplaires(): ?int
    {
        return $this->nbExemplaires;
    }

    public function setNbExemplaires(int $nbExemplaires): static
    {
        $this->nbExemplaires = $nbExemplaires;

        return $this;
    }

    public function getDateEdition(): ?\DateTime
    {
        return $this->dateEdition;
    }

    public function setDateEdition(?\DateTime $dateEdition): static
    {
        $this->dateEdition = $dateEdition;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;

        return $this;
    }

    public function getEditeur(): ?Editeur
    {
        return $this->editeur;
    }

    public function setEditeur(?Editeur $editeur): static
    {
        $this->editeur = $editeur;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /** @return Collection<int, Auteur> */
    public function getAuteurs(): Collection
    {
        return $this->auteurs;
    }

    public function addAuteur(Auteur $auteur): static
    {
        if (!$this->auteurs->contains($auteur)) {
            $this->auteurs->add($auteur);
        }

        return $this;
    }

    public function removeAuteur(Auteur $auteur): static
    {
        $this->auteurs->removeElement($auteur);

        return $this;
    }

    /** @return Collection<int, Emprunt> */
    public function getEmprunts(): Collection
    {
        return $this->emprunts;
    }
}
