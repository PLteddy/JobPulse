<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250405130122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE candidature (id INT AUTO_INCREMENT NOT NULL, etat VARCHAR(255) NOT NULL, enregistre TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE message ADD from_user_id INT NOT NULL, ADD to_user_id INT NOT NULL, ADD is_read TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F2130303A FOREIGN KEY (from_user_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F29F6EE60 FOREIGN KEY (to_user_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F2130303A ON message (from_user_id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F29F6EE60 ON message (to_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE candidature');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F2130303A');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F29F6EE60');
        $this->addSql('DROP INDEX IDX_B6BD307F2130303A ON message');
        $this->addSql('DROP INDEX IDX_B6BD307F29F6EE60 ON message');
        $this->addSql('ALTER TABLE message DROP from_user_id, DROP to_user_id, DROP is_read');
    }
}
