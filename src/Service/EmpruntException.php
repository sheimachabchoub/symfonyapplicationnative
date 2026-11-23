<?php

namespace App\Service;

/**
 * Règle métier non respectée (livre indisponible, quota atteint...).
 * Le message est destiné à être affiché à l'utilisateur.
 */
final class EmpruntException extends \DomainException
{
}
