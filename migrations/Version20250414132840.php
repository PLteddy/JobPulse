<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250414132840 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidature CHANGE motivation motivation LONGTEXT DEFAULT NULL, CHANGE cv_candidature cv_candidature VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE message CHANGE contenu contenu LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE poste CHANGE description description LONGTEXT NOT NULL, CHANGE profil_recherche profil_recherche LONGTEXT NOT NULL, CHANGE info_supp info_supp LONGTEXT DEFAULT NULL, CHANGE presentation_entreprise presentation_entreprise LONGTEXT NOT NULL, CHANGE missions missions LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE decription decription LONGTEXT DEFAULT NULL, CHANGE experience experience LONGTEXT DEFAULT NULL, CHANGE plus_sur_moi plus_sur_moi LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candidature CHANGE motivation motivation VARCHAR(1000) DEFAULT NULL, CHANGE cv_candidature cv_candidature VARCHAR(500) NOT NULL');
        $this->addSql('ALTER TABLE message CHANGE contenu contenu VARCHAR(300) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE decription decription VARCHAR(500) DEFAULT NULL, CHANGE experience experience VARCHAR(1000) DEFAULT NULL, CHANGE plus_sur_moi plus_sur_moi VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE poste CHANGE description description VARCHAR(1000) NOT NULL, CHANGE profil_recherche profil_recherche VARCHAR(255) NOT NULL, CHANGE info_supp info_supp VARCHAR(255) DEFAULT NULL, CHANGE presentation_entreprise presentation_entreprise VARCHAR(500) NOT NULL, CHANGE missions missions VARCHAR(500) NOT NULL');
    }
}
