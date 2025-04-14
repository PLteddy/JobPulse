<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250414123533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration pour ajuster la table candidature, en supprimant l\'ancienne colonne cv et en ajoutant cvCandidature.';
    }

    public function up(Schema $schema): void
    {
 

      
      

        // Modifier la colonne 'motivation'
        $this->addSql('ALTER TABLE candidature CHANGE motivation motivation VARCHAR(1000) DEFAULT NULL');

        // Modification de la colonne 'experience' dans la table 'utilisateur'
        $this->addSql('ALTER TABLE utilisateur CHANGE experience experience VARCHAR(1000) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {

        $this->addSql('ALTER TABLE candidature CHANGE motivation motivation VARCHAR(1000) DEFAULT \'\'');

        // On peut décider de ne pas renommer à nouveau la colonne 'experience' si nécessaire
        $this->addSql('ALTER TABLE utilisateur CHANGE experience experience VARCHAR(500) DEFAULT NULL');
    }
}