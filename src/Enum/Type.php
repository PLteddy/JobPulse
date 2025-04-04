<?php
namespace App\Enum;

enum Type: string {
    case ENTREPRISE = 'ENTREPRISE';
    case TUTEUR = 'TUTEUR';
    case ETUDIANT = 'ETUDIANT';

    /**
     * Convertit une chaîne en une instance de l'énumération.
     */
    public static function fromString(string $type): self
    {
        return match (strtoupper($type)) {
            'ENTREPRISE' => self::ENTREPRISE,
            'TUTEUR' => self::TUTEUR,
            'ETUDIANT' => self::ETUDIANT,
            default => throw new \InvalidArgumentException("Type invalide : $type"),
        };
    }

    /**
     * Retourne tous les types sous forme de tableau pour les formulaires.
     */
    public static function getChoices(): array
    {
        return [
            'Entreprise' => self::ENTREPRISE->value,
            'Tuteur' => self::TUTEUR->value,
            'Étudiant' => self::ETUDIANT->value,
        ];
    }
}