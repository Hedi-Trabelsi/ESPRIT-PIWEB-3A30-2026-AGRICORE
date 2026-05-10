<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds an `image` LONGBLOB column to `equipements` so the Java desktop app
 * and the Symfony web app can share the picture through a single column.
 * The legacy `image_filename` (Vich) column is kept for fallback rendering
 * of rows uploaded before this migration.
 */
final class Version20260508120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image LONGBLOB column to equipements for cross-app picture sync.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipements ADD COLUMN image LONGBLOB DEFAULT NULL AFTER updated_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipements DROP COLUMN image');
    }
}
