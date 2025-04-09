<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250409091627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE poste ADD entreprise_id INT NOT NULL');
        $this->addSql('ALTER TABLE poste ADD CONSTRAINT FK_7C890FABA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_7C890FABA4AEAFEA ON poste (entreprise_id)');
        $this->addSql('ALTER TABLE utilisateur CHANGE siret siret VARCHAR(255) NOT NULL, CHANGE adresse adresse VARCHAR(255) NOT NULL, CHANGE formation formation VARCHAR(255) NOT NULL, CHANGE etablissement etablissement VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur CHANGE siret siret VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE adresse adresse VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE formation formation VARCHAR(255) DEFAULT \'\' NOT NULL, CHANGE etablissement etablissement VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE poste DROP FOREIGN KEY FK_7C890FABA4AEAFEA');
        $this->addSql('DROP INDEX IDX_7C890FABA4AEAFEA ON poste');
        $this->addSql('ALTER TABLE poste DROP entreprise_id');
    }
}
