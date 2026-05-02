<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fixes the action_logs table by adding missing created_at and updated_at columns.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['action_logs'])) {
            $this->addSql("CREATE TABLE action_logs (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                action_type VARCHAR(255) NOT NULL,
                target_table VARCHAR(255) NOT NULL,
                target_id INT NOT NULL,
                description TEXT NOT NULL,
                old_value VARCHAR(255) NOT NULL,
                new_value VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        } else {
            $columns = $schemaManager->listTableColumns('action_logs');

            if (!isset($columns['created_at'])) {
                $this->addSql("ALTER TABLE action_logs ADD COLUMN created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
            }

            if (!isset($columns['updated_at'])) {
                $this->addSql("ALTER TABLE action_logs ADD COLUMN updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
