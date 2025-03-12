<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250312125843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur ADD nom VARCHAR(255) NOT NULL, ADD prenom VARCHAR(255) NOT NULL, ADD adresse VARCHAR(255) NOT NULL, ADD formation VARCHAR(255) NOT NULL, ADD etablissement VARCHAR(255) NOT NULL, ADD decription VARCHAR(255) DEFAULT NULL, ADD experience VARCHAR(255) DEFAULT NULL, ADD contact VARCHAR(255) DEFAULT NULL, ADD cv VARCHAR(255) DEFAULT NULL, ADD plus_sur_moi VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur DROP nom, DROP prenom, DROP adresse, DROP formation, DROP etablissement, DROP decription, DROP experience, DROP contact, DROP cv, DROP plus_sur_moi');
    }
}
