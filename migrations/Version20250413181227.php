<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250413181227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE poste CHANGE contrat_type contrat_type VARCHAR(255) NOT NULL, CHANGE duree duree VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE formation formation VARCHAR(500) DEFAULT NULL, CHANGE decription decription LONGTEXT DEFAULT NULL, CHANGE experience experience VARCHAR(500) DEFAULT NULL, CHANGE plus_sur_moi plus_sur_moi VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur CHANGE formation formation VARCHAR(255) DEFAULT NULL, CHANGE decription decription VARCHAR(255) DEFAULT NULL, CHANGE experience experience VARCHAR(255) DEFAULT NULL, CHANGE plus_sur_moi plus_sur_moi VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE poste CHANGE contrat_type contrat_type LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', CHANGE duree duree LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\'');
    }
}
