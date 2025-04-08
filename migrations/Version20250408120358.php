<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250408120358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Force NOT NULL sur certaines colonnes utilisateur avec valeurs par défaut pour éviter erreurs SQL';
    }

    public function up(Schema $schema): void
    {
        // On met à jour les valeurs NULL existantes avant d'appliquer NOT NULL
        $this->addSql("UPDATE utilisateur SET siret = '' WHERE siret IS NULL");
        $this->addSql("UPDATE utilisateur SET adresse = '' WHERE adresse IS NULL");
        $this->addSql("UPDATE utilisateur SET formation = '' WHERE formation IS NULL");
        $this->addSql("UPDATE utilisateur SET etablissement = '' WHERE etablissement IS NULL");

        // Ensuite, on modifie les colonnes pour qu'elles soient NOT NULL avec DEFAULT ''
        $this->addSql("ALTER TABLE utilisateur 
            CHANGE siret siret VARCHAR(255) NOT NULL DEFAULT '',
            CHANGE adresse adresse VARCHAR(255) NOT NULL DEFAULT '',
            CHANGE formation formation VARCHAR(255) NOT NULL DEFAULT '',
            CHANGE etablissement etablissement VARCHAR(255) NOT NULL DEFAULT ''");
    }

    public function down(Schema $schema): void
    {
        // Retour arrière : on enlève le NOT NULL
        $this->addSql('ALTER TABLE utilisateur 
            CHANGE siret siret VARCHAR(255) DEFAULT NULL,
            CHANGE adresse adresse VARCHAR(255) DEFAULT NULL,
            CHANGE formation formation VARCHAR(255) DEFAULT NULL,
            CHANGE etablissement etablissement VARCHAR(255) DEFAULT NULL');
    }
}
