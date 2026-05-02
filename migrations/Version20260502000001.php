<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fixes old action_type values in action_logs by converting them to lowercase.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE action_logs SET action_type = LOWER(action_type) WHERE action_type IN ('CREATE', 'UPDATE', 'DELETE', 'VIEW')");
    }

    public function down(Schema $schema): void
    {
    }
}
