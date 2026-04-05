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
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateurs CHANGE image image VARCHAR(65535) NOT NULL');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY `FK_888A2A4C64B64DCC`');
        $this->addSql('DROP INDEX fk_vente_user ON vente');
        $this->addSql('CREATE INDEX IDX_888A2A4C64B64DCC ON vente (userId)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT `FK_888A2A4C64B64DCC` FOREIGN KEY (userId) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateurs CHANGE image image MEDIUMTEXT NOT NULL');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4C64B64DCC');
        $this->addSql('DROP INDEX idx_888a2a4c64b64dcc ON vente');
        $this->addSql('CREATE INDEX fk_vente_user ON vente (userId)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4C64B64DCC FOREIGN KEY (userId) REFERENCES user (id)');
    }
}
