<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410073533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE utilisateur_poste_sauvegarde (utilisateur_id INT NOT NULL, poste_id INT NOT NULL, INDEX IDX_8D0F32F3FB88E14F (utilisateur_id), INDEX IDX_8D0F32F3A0905086 (poste_id), PRIMARY KEY(utilisateur_id, poste_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE utilisateur_poste_sauvegarde ADD CONSTRAINT FK_8D0F32F3FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_poste_sauvegarde ADD CONSTRAINT FK_8D0F32F3A0905086 FOREIGN KEY (poste_id) REFERENCES poste (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur_poste_sauvegarde DROP FOREIGN KEY FK_8D0F32F3FB88E14F');
        $this->addSql('ALTER TABLE utilisateur_poste_sauvegarde DROP FOREIGN KEY FK_8D0F32F3A0905086');
        $this->addSql('DROP TABLE utilisateur_poste_sauvegarde');
    }
}
