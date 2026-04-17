<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restores AUTO_INCREMENT on primary keys expected to be database-generated.';
    }

    public function up(Schema $schema): void
    {
<<<<<<< HEAD
        $this->addSql('ALTER TABLE vente MODIFY idVente INT NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE commande MODIFY id INT NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE ligne_commande MODIFY id INT NOT NULL AUTO_INCREMENT');
=======
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['vente'])) {
            $this->addSql('ALTER TABLE vente MODIFY idVente INT NOT NULL AUTO_INCREMENT');
        }

        if ($schemaManager->tablesExist(['commande'])) {
            $this->addSql('ALTER TABLE commande MODIFY id INT NOT NULL AUTO_INCREMENT');
        }

        if ($schemaManager->tablesExist(['ligne_commande'])) {
            $this->addSql('ALTER TABLE ligne_commande MODIFY id INT NOT NULL AUTO_INCREMENT');
        }
>>>>>>> main
    }

    public function down(Schema $schema): void
    {
<<<<<<< HEAD
        $this->addSql('ALTER TABLE vente MODIFY idVente INT NOT NULL');
        $this->addSql('ALTER TABLE commande MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE ligne_commande MODIFY id INT NOT NULL');
=======
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['vente'])) {
            $this->addSql('ALTER TABLE vente MODIFY idVente INT NOT NULL');
        }

        if ($schemaManager->tablesExist(['commande'])) {
            $this->addSql('ALTER TABLE commande MODIFY id INT NOT NULL');
        }

        if ($schemaManager->tablesExist(['ligne_commande'])) {
            $this->addSql('ALTER TABLE ligne_commande MODIFY id INT NOT NULL');
        }
>>>>>>> main
    }
}
