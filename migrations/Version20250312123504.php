<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250312123504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE poste ADD profil_recherche VARCHAR(255) NOT NULL, ADD info_supp VARCHAR(255) DEFAULT NULL, ADD presentation_entreprise VARCHAR(500) NOT NULL, ADD contact VARCHAR(255) NOT NULL, ADD salaire INT NOT NULL, ADD presence VARCHAR(255) NOT NULL, ADD duree LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE description description VARCHAR(500) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE poste DROP profil_recherche, DROP info_supp, DROP presentation_entreprise, DROP contact, DROP salaire, DROP presence, DROP duree, CHANGE description description VARCHAR(255) NOT NULL');
    }
}
