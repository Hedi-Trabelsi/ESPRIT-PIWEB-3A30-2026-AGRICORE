<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405185315 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $foreignKeys = [];
        foreach ($schemaManager->listTableForeignKeys('vente') as $foreignKey) {
            $foreignKeys[$foreignKey->getName()] = true;
        }

        $indexes = [];
        foreach ($schemaManager->listTableIndexes('vente') as $index) {
            $indexes[$index->getName()] = true;
        }

        $this->addSql('ALTER TABLE utilisateurs CHANGE image image VARCHAR(65535) NOT NULL');

        if (isset($foreignKeys['FK_888A2A4C64B64DCC'])) {
            $this->addSql('ALTER TABLE vente DROP FOREIGN KEY `FK_888A2A4C64B64DCC`');
        }

        if (isset($indexes['fk_vente_user'])) {
            $this->addSql('DROP INDEX fk_vente_user ON vente');
        }

        $this->addSql('CREATE INDEX IDX_888A2A4C64B64DCC ON vente (userId)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT `FK_888A2A4C64B64DCC` FOREIGN KEY (userId) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $foreignKeys = [];
        foreach ($schemaManager->listTableForeignKeys('vente') as $foreignKey) {
            $foreignKeys[$foreignKey->getName()] = true;
        }

        $indexes = [];
        foreach ($schemaManager->listTableIndexes('vente') as $index) {
            $indexes[$index->getName()] = true;
        }

        $this->addSql('ALTER TABLE utilisateurs CHANGE image image MEDIUMTEXT NOT NULL');

        if (isset($foreignKeys['FK_888A2A4C64B64DCC'])) {
            $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4C64B64DCC');
        }

        if (isset($indexes['idx_888a2a4c64b64dcc'])) {
            $this->addSql('DROP INDEX idx_888a2a4c64b64dcc ON vente');
        }

        $this->addSql('CREATE INDEX fk_vente_user ON vente (userId)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4C64B64DCC FOREIGN KEY (userId) REFERENCES user (id)');
    }
}
