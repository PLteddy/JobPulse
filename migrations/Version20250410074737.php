<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410074737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tuteur_etudiant (utilisateur_source INT NOT NULL, utilisateur_target INT NOT NULL, INDEX IDX_3BFDB4333E2745F8 (utilisateur_source), INDEX IDX_3BFDB43327C21577 (utilisateur_target), PRIMARY KEY(utilisateur_source, utilisateur_target)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tuteur_etudiant ADD CONSTRAINT FK_3BFDB4333E2745F8 FOREIGN KEY (utilisateur_source) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tuteur_etudiant ADD CONSTRAINT FK_3BFDB43327C21577 FOREIGN KEY (utilisateur_target) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tuteur_etudiant DROP FOREIGN KEY FK_3BFDB4333E2745F8');
        $this->addSql('ALTER TABLE tuteur_etudiant DROP FOREIGN KEY FK_3BFDB43327C21577');
        $this->addSql('DROP TABLE tuteur_etudiant');
    }
}
