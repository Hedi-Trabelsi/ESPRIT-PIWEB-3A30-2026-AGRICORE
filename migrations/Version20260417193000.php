<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repairs the equipements, commande and ligne_commande tables after manual database resets.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['equipements'])) {
            $this->addSql("CREATE TABLE equipements (
                id_equipement INT AUTO_INCREMENT NOT NULL,
                id_fournisseur INT DEFAULT NULL,
                nom VARCHAR(255) NOT NULL,
                type VARCHAR(255) NOT NULL,
                prix VARCHAR(255) NOT NULL,
                quantite INT NOT NULL,
                image_filename VARCHAR(255) DEFAULT NULL,
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                INDEX IDX_EQUIPEMENTS_FOURNISSEUR (id_fournisseur),
                PRIMARY KEY(id_equipement)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        } else {
            $equipementColumns = $schemaManager->listTableColumns('equipements');

            if (!isset($equipementColumns['id_fournisseur'])) {
                $this->addSql('ALTER TABLE equipements ADD COLUMN id_fournisseur INT DEFAULT NULL');
            }

            if (!isset($equipementColumns['image_filename'])) {
                $this->addSql('ALTER TABLE equipements ADD COLUMN image_filename VARCHAR(255) DEFAULT NULL');
            }

            if (!isset($equipementColumns['updated_at'])) {
                $this->addSql("ALTER TABLE equipements ADD COLUMN updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
            }

            if (!isset($equipementColumns['is_active'])) {
                $this->addSql('ALTER TABLE equipements ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1');
            }

            $equipementIndexes = $schemaManager->listTableIndexes('equipements');
            if (!isset($equipementIndexes['idx_equipements_fournisseur'])) {
                $this->addSql('CREATE INDEX IDX_EQUIPEMENTS_FOURNISSEUR ON equipements (id_fournisseur)');
            }
        }

        if (!$schemaManager->tablesExist(['commande'])) {
            $this->addSql("CREATE TABLE commande (
                id INT AUTO_INCREMENT NOT NULL,
                date_commande DATETIME NOT NULL,
                total NUMERIC(10, 2) NOT NULL,
                agriculteur_id INT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        } else {
            $commandeColumns = $schemaManager->listTableColumns('commande');

            if (!isset($commandeColumns['date_commande'])) {
                $this->addSql('ALTER TABLE commande ADD COLUMN date_commande DATETIME NOT NULL');
            }

            if (!isset($commandeColumns['total'])) {
                $this->addSql('ALTER TABLE commande ADD COLUMN total NUMERIC(10, 2) NOT NULL');
            }

            if (!isset($commandeColumns['agriculteur_id'])) {
                $this->addSql('ALTER TABLE commande ADD COLUMN agriculteur_id INT NOT NULL');
            }

            $this->addSql('ALTER TABLE commande MODIFY id INT NOT NULL AUTO_INCREMENT');
        }

        if (!$schemaManager->tablesExist(['ligne_commande'])) {
            $this->addSql("CREATE TABLE ligne_commande (
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
        } else {
            $ligneColumns = $schemaManager->listTableColumns('ligne_commande');

            if (!isset($ligneColumns['commande_id'])) {
                $this->addSql('ALTER TABLE ligne_commande ADD COLUMN commande_id INT NOT NULL');
            }

            if (!isset($ligneColumns['equipement_id'])) {
                $this->addSql('ALTER TABLE ligne_commande ADD COLUMN equipement_id INT NOT NULL');
            }

            if (!isset($ligneColumns['quantite'])) {
                $this->addSql('ALTER TABLE ligne_commande ADD COLUMN quantite INT NOT NULL');
            }

            if (!isset($ligneColumns['prix_unitaire'])) {
                $this->addSql('ALTER TABLE ligne_commande ADD COLUMN prix_unitaire NUMERIC(10, 2) NOT NULL');
            }

            if (!isset($ligneColumns['total_ligne'])) {
                $this->addSql('ALTER TABLE ligne_commande ADD COLUMN total_ligne NUMERIC(10, 2) NOT NULL');
            }

            $this->addSql('ALTER TABLE ligne_commande MODIFY id INT NOT NULL AUTO_INCREMENT');

            $ligneIndexes = $schemaManager->listTableIndexes('ligne_commande');
            if (!isset($ligneIndexes['idx_ligne_commande_commande'])) {
                $this->addSql('CREATE INDEX IDX_LIGNE_COMMANDE_COMMANDE ON ligne_commande (commande_id)');
            }

            if (!isset($ligneIndexes['idx_ligne_commande_equipement'])) {
                $this->addSql('CREATE INDEX IDX_LIGNE_COMMANDE_EQUIPEMENT ON ligne_commande (equipement_id)');
            }
        }

        $foreignKeys = [];
        foreach ($this->connection->createSchemaManager()->listTableForeignKeys('ligne_commande') as $foreignKey) {
            $foreignKeys[$foreignKey->getName()] = true;
        }

        if (!isset($foreignKeys['FK_LIGNE_COMMANDE_COMMANDE'])) {
            $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_LIGNE_COMMANDE_COMMANDE FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        }

        if (!isset($foreignKeys['FK_LIGNE_COMMANDE_EQUIPEMENT'])) {
            $orphanedEquipements = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM ligne_commande lc LEFT JOIN equipements e ON e.id_equipement = lc.equipement_id WHERE e.id_equipement IS NULL'
            );

            if ($orphanedEquipements === 0) {
                $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_LIGNE_COMMANDE_EQUIPEMENT FOREIGN KEY (equipement_id) REFERENCES equipements (id_equipement)');
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
