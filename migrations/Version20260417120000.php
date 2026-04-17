<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds updated_at to equipements for VichUploaderBundle file tracking.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['equipements'])) {
            return;
        }

        $columns = $this->connection->createSchemaManager()->listTableColumns('equipements');
        if (!isset($columns['updated_at'])) {
            $this->addSql("ALTER TABLE equipements ADD COLUMN updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }
    }

    public function down(Schema $schema): void
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['equipements'])) {
            return;
        }

        $columns = $this->connection->createSchemaManager()->listTableColumns('equipements');
        if (isset($columns['updated_at'])) {
            $this->addSql('ALTER TABLE equipements DROP COLUMN updated_at');
        }
    }
}
