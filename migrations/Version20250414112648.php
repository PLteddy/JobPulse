<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250414112648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des colonnes message et cv à la table candidature, modification des colonnes dans utilisateur et poste.';
    }

    public function up(Schema $schema): void
    {
        // Nettoyer la colonne experience dans utilisateur
        $this->addSql('UPDATE utilisateur SET experience = LEFT(experience, 1000) WHERE CHAR_LENGTH(experience) > 1000');

        // Modifier la colonne experience dans utilisateur
        $this->addSql('ALTER TABLE utilisateur CHANGE experience experience VARCHAR(1000) DEFAULT NULL');

        // Nettoyer la colonne profil_recherche dans poste
        $this->addSql('UPDATE poste SET profil_recherche = LEFT(profil_recherche, 255) WHERE CHAR_LENGTH(profil_recherche) > 255');

        // Ajouter les colonnes message et cv dans candidature (assurez-vous qu'elles n'existent pas déjà avant d'exécuter cette migration)
        $this->addSql('ALTER TABLE candidature ADD message VARCHAR(1000) DEFAULT NULL');
        $this->addSql('ALTER TABLE candidature ADD cv VARCHAR(500) NOT NULL');

        // Modifier les colonnes dans poste
        $this->addSql('ALTER TABLE poste 
            CHANGE contrat_type contrat_type VARCHAR(255) NOT NULL, 
            CHANGE profil_recherche profil_recherche VARCHAR(255) NOT NULL, 
            CHANGE info_supp info_supp VARCHAR(255) DEFAULT NULL, 
            CHANGE duree duree VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revenir aux anciennes définitions des colonnes
        $this->addSql('ALTER TABLE utilisateur CHANGE experience experience VARCHAR(1000) DEFAULT NULL');

        // Supprimer les colonnes ajoutées
        $this->addSql('ALTER TABLE candidature DROP COLUMN message');
        $this->addSql('ALTER TABLE candidature DROP COLUMN cv');

        // Revenir aux anciens types dans la table poste
        $this->addSql('ALTER TABLE poste 
            CHANGE contrat_type contrat_type LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', 
            CHANGE profil_recherche profil_recherche VARCHAR(1000) NOT NULL, 
            CHANGE info_supp info_supp VARCHAR(500) DEFAULT NULL, 
            CHANGE duree duree LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\'');
    }
}
