<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250410140738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Remplacer les NULLs existants par une valeur par défaut (ex: une chaîne vide)
        $this->addSql("UPDATE utilisateur SET adresse = '' WHERE adresse IS NULL");
    
        // Ensuite seulement, on peut modifier la colonne pour qu'elle soit NOT NULL
        $this->addSql('ALTER TABLE utilisateur CHANGE adresse adresse VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur CHANGE adresse adresse VARCHAR(255) DEFAULT NULL');
    }
}
