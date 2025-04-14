<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250414130743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
     
        //$this->addSql('ALTER TABLE utilisateur CHANGE experience experience VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {

       // $this->addSql('ALTER TABLE utilisateur CHANGE experience experience VARCHAR(1000) DEFAULT NULL');
    }
}
