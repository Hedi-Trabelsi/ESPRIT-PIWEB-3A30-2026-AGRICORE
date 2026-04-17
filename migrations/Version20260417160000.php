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
        $this->addSql('ALTER TABLE vente MODIFY idVente INT NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE commande MODIFY id INT NOT NULL AUTO_INCREMENT');
        $this->addSql('ALTER TABLE ligne_commande MODIFY id INT NOT NULL AUTO_INCREMENT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vente MODIFY idVente INT NOT NULL');
        $this->addSql('ALTER TABLE commande MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE ligne_commande MODIFY id INT NOT NULL');
    }
}
