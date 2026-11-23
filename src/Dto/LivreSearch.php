<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Critères de recherche du catalogue, lus depuis la query string (?q=...&categorie=...).
 */
final readonly class LivreSearch
{
    public function __construct(
        /** Cherché dans le titre, le nom/prénom des auteurs et la catégorie. */
        #[Assert\Length(max: 100)]
        public ?string $q = null,
        #[Assert\Positive]
        public ?int $categorie = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return (null === $this->q || '' === trim($this->q)) && null === $this->categorie;
    }
}
