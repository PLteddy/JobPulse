<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250414080149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidature ADD message VARCHAR(1000) DEFAULT NULL, ADD cv VARCHAR(500) NOT NULL');
        $this->addSql('ALTER TABLE poste CHANGE contrat_type contrat_type VARCHAR(255) NOT NULL, CHANGE profil_recherche profil_recherche VARCHAR(255) NOT NULL, CHANGE info_supp info_supp VARCHAR(255) DEFAULT NULL, CHANGE duree duree VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE experience experience VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur CHANGE experience experience VARCHAR(1000) DEFAULT NULL');
        $this->addSql('ALTER TABLE candidature DROP message, DROP cv');
        $this->addSql('ALTER TABLE poste CHANGE contrat_type contrat_type LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE profil_recherche profil_recherche VARCHAR(1000) NOT NULL, CHANGE info_supp info_supp VARCHAR(500) DEFAULT NULL, CHANGE duree duree LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\'');
    }
}
