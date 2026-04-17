<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds equipment module support columns and order tables.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $columns = $schemaManager->listTableColumns('equipements');
        $ligneCommandeForeignKeys = [];
        if ($schemaManager->tablesExist(['ligne_commande'])) {
            foreach ($schemaManager->listTableForeignKeys('ligne_commande') as $foreignKey) {
                $ligneCommandeForeignKeys[$foreignKey->getName()] = true;
            }
        }

        $this->addSql("ALTER TABLE equipements ADD COLUMN IF NOT EXISTS image_filename VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE equipements ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1");
        $this->addSql("CREATE TABLE IF NOT EXISTS commande (
            id INT AUTO_INCREMENT NOT NULL,
            date_commande DATETIME NOT NULL,
            total NUMERIC(10, 2) NOT NULL,
            agriculteur_id INT NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE IF NOT EXISTS ligne_commande (
            id INT AUTO_INCREMENT NOT NULL,
            commande_id INT NOT NULL,
            equipement_id INT NOT NULL,
            quantite INT NOT NULL,
            prix_unitaire NUMERIC(10, 2) NOT NULL,
            total_ligne NUMERIC(10, 2) NOT NULL,
            INDEX IDX_LIGNE_COMMANDE_COMMANDE (commande_id),
            INDEX IDX_LIGNE_COMMANDE_EQUIPEMENT (equipement_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        if (!isset($ligneCommandeForeignKeys['FK_LIGNE_COMMANDE_COMMANDE'])) {
            $this->addSql("ALTER TABLE ligne_commande ADD CONSTRAINT FK_LIGNE_COMMANDE_COMMANDE FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE");
        }

        if (!isset($ligneCommandeForeignKeys['FK_LIGNE_COMMANDE_EQUIPEMENT'])) {
            $orphanedEquipements = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM ligne_commande lc LEFT JOIN equipements e ON e.id_equipement = lc.equipement_id WHERE e.id_equipement IS NULL'
            );

            if ($orphanedEquipements === 0) {
                $this->addSql("ALTER TABLE ligne_commande ADD CONSTRAINT FK_LIGNE_COMMANDE_EQUIPEMENT FOREIGN KEY (equipement_id) REFERENCES equipements (id_equipement)");
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_LIGNE_COMMANDE_COMMANDE');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_LIGNE_COMMANDE_EQUIPEMENT');
        $this->addSql('DROP TABLE IF EXISTS ligne_commande');
        $this->addSql('DROP TABLE IF EXISTS commande');
        $this->addSql('ALTER TABLE equipements DROP COLUMN image_filename');
        $this->addSql('ALTER TABLE equipements DROP COLUMN is_active');
    }
}
