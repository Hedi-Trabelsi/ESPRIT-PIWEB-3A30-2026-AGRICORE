<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404000846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE action_logs CHANGE id id INT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE action_type action_type VARCHAR(50) NOT NULL, CHANGE target_table target_table VARCHAR(50) NOT NULL, CHANGE target_id target_id INT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE old_value old_value VARCHAR(255) NOT NULL, CHANGE new_value new_value VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY `fk`');
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY `fk`');
        $this->addSql('ALTER TABLE animal MODIFY idAnimal INT NOT NULL');
        $this->addSql('ALTER TABLE animal ADD id_animal INT NOT NULL, DROP idAnimal, CHANGE idAgriculteur idAgriculteur INT DEFAULT NULL, CHANGE codeAnimal code_animal VARCHAR(50) NOT NULL, CHANGE dateNaissance date_naissance DATE NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_animal)');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231F633BBB43 FOREIGN KEY (idAgriculteur) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk ON animal');
        $this->addSql('CREATE INDEX IDX_6AAB231F633BBB43 ON animal (idAgriculteur)');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT `fk` FOREIGN KEY (idAgriculteur) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depense DROP FOREIGN KEY `fk_depense_user`');
        $this->addSql('ALTER TABLE depense DROP FOREIGN KEY `fk_depense_user`');
        $this->addSql('ALTER TABLE depense MODIFY idDepense INT NOT NULL');
        $this->addSql('ALTER TABLE depense ADD id_depense INT NOT NULL, DROP idDepense, CHANGE type type VARCHAR(255) NOT NULL, CHANGE userId userId INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_depense)');
        $this->addSql('ALTER TABLE depense ADD CONSTRAINT FK_3405975764B64DCC FOREIGN KEY (userId) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_depense_user ON depense');
        $this->addSql('CREATE INDEX IDX_3405975764B64DCC ON depense (userId)');
        $this->addSql('ALTER TABLE depense ADD CONSTRAINT `fk_depense_user` FOREIGN KEY (userId) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY `fk_equipements`');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY `fk_equipements`');
        $this->addSql('ALTER TABLE equipements CHANGE id_equipement id_equipement INT NOT NULL, CHANGE id_fournisseur id_fournisseur INT DEFAULT NULL');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT FK_3F02D86B2E8C07C5 FOREIGN KEY (id_fournisseur) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_equipements ON equipements');
        $this->addSql('CREATE INDEX IDX_3F02D86B2E8C07C5 ON equipements (id_fournisseur)');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT `fk_equipements` FOREIGN KEY (id_fournisseur) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evennementagricole CHANGE id_ev id_ev INT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE date_debut date_debut DATETIME NOT NULL, CHANGE date_fin date_fin DATETIME NOT NULL');
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY `fk_maintenances_user`');
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY `fk_maintenances_user`');
        $this->addSql('ALTER TABLE maintenance CHANGE id_maintenance id_maintenance INT NOT NULL, CHANGE description description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E93B7489CC FOREIGN KEY (id_agriculteur) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_maintenances_user ON maintenance');
        $this->addSql('CREATE INDEX IDX_2F84F8E93B7489CC ON maintenance (id_agriculteur)');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT `fk_maintenances_user` FOREIGN KEY (id_agriculteur) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY `fk_panier`');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY `fk_panier`');
        $this->addSql('ALTER TABLE panier CHANGE id_panier id_panier INT NOT NULL, CHANGE id_equipement id_equipement INT DEFAULT NULL');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF21D3E4624 FOREIGN KEY (id_equipement) REFERENCES equipements (id_equipement) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_panier ON panier');
        $this->addSql('CREATE INDEX IDX_24CC0DF21D3E4624 ON panier (id_equipement)');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT `fk_panier` FOREIGN KEY (id_equipement) REFERENCES equipements (id_equipement) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_participant ON participants');
        $this->addSql('DROP INDEX fk2_participant ON participants');
        $this->addSql('ALTER TABLE participants CHANGE id_participant id_participant INT NOT NULL');
        $this->addSql('ALTER TABLE suivi_animal DROP FOREIGN KEY `fkhh`');
        $this->addSql('ALTER TABLE suivi_animal DROP FOREIGN KEY `fkhh`');
        $this->addSql('ALTER TABLE suivi_animal MODIFY idSuivi INT NOT NULL');
        $this->addSql('ALTER TABLE suivi_animal ADD rythme_cardiaque INT NOT NULL, ADD niveau_actitive VARCHAR(50) NOT NULL, ADD etat_sante VARCHAR(50) NOT NULL, ADD niveau_activite VARCHAR(50) NOT NULL, DROP idSuivi, DROP niveauActitive, DROP etatSante, DROP niveauActivite, CHANGE idAnimal idAnimal INT DEFAULT NULL, CHANGE remarque remarque LONGTEXT NOT NULL, CHANGE rythmeCardiaque id_suivi INT NOT NULL, CHANGE dateSuivi date_suivi DATETIME NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_suivi)');
        $this->addSql('ALTER TABLE suivi_animal ADD CONSTRAINT FK_C86F5E4D9BA8B85 FOREIGN KEY (idAnimal) REFERENCES animal (idAnimal) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_animal ON suivi_animal');
        $this->addSql('CREATE INDEX IDX_C86F5E4D9BA8B85 ON suivi_animal (idAnimal)');
        $this->addSql('ALTER TABLE suivi_animal ADD CONSTRAINT `fkhh` FOREIGN KEY (idAnimal) REFERENCES animal (idAnimal) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_tache ON tache');
        $this->addSql('ALTER TABLE tache CHANGE id_tache id_tache INT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE id_technicien id_technicien INT NOT NULL, CHANGE nomTache nom_tache VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE id id INT NOT NULL, CHANGE date date DATE NOT NULL, CHANGE image image VARCHAR(255) NOT NULL, CHANGE genre genre VARCHAR(255) NOT NULL, CHANGE profile_complete profile_complete TINYINT NOT NULL, CHANGE numeroT numero_t INT NOT NULL');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY `fk_vente_user`');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY `fk_vente_user`');
        $this->addSql('ALTER TABLE vente MODIFY idVente INT NOT NULL');
        $this->addSql('ALTER TABLE vente ADD id_vente INT NOT NULL, ADD prix_unitaire INT NOT NULL, ADD chiffre_affaires INT NOT NULL, DROP idVente, DROP prixUnitaire, DROP chiffreAffaires, CHANGE userId userId INT DEFAULT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_vente)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4C64B64DCC FOREIGN KEY (userId) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_vente_user ON vente');
        $this->addSql('CREATE INDEX IDX_888A2A4C64B64DCC ON vente (userId)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT `fk_vente_user` FOREIGN KEY (userId) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE action_logs CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE action_type action_type VARCHAR(50) DEFAULT NULL, CHANGE target_table target_table VARCHAR(50) DEFAULT NULL, CHANGE target_id target_id INT DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE old_value old_value JSON DEFAULT NULL, CHANGE new_value new_value JSON DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY FK_6AAB231F633BBB43');
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY FK_6AAB231F633BBB43');
        $this->addSql('ALTER TABLE animal ADD idAnimal INT AUTO_INCREMENT NOT NULL, DROP id_animal, CHANGE idAgriculteur idAgriculteur INT NOT NULL, CHANGE code_animal codeAnimal VARCHAR(50) NOT NULL, CHANGE date_naissance dateNaissance DATE NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idAnimal)');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT `fk` FOREIGN KEY (idAgriculteur) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_6aab231f633bbb43 ON animal');
        $this->addSql('CREATE INDEX fk ON animal (idAgriculteur)');
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231F633BBB43 FOREIGN KEY (idAgriculteur) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depense DROP FOREIGN KEY FK_3405975764B64DCC');
        $this->addSql('ALTER TABLE depense DROP FOREIGN KEY FK_3405975764B64DCC');
        $this->addSql('ALTER TABLE depense ADD idDepense INT AUTO_INCREMENT NOT NULL, DROP id_depense, CHANGE type type ENUM(\'MAINDOEUVRE\', \'INTRANT\', \'CARBURANT\', \'REPARATION\', \'AUTRE\') NOT NULL, CHANGE userId userId INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idDepense)');
        $this->addSql('ALTER TABLE depense ADD CONSTRAINT `fk_depense_user` FOREIGN KEY (userId) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_3405975764b64dcc ON depense');
        $this->addSql('CREATE INDEX fk_depense_user ON depense (userId)');
        $this->addSql('ALTER TABLE depense ADD CONSTRAINT FK_3405975764B64DCC FOREIGN KEY (userId) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY FK_3F02D86B2E8C07C5');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY FK_3F02D86B2E8C07C5');
        $this->addSql('ALTER TABLE equipements CHANGE id_equipement id_equipement INT AUTO_INCREMENT NOT NULL, CHANGE id_fournisseur id_fournisseur INT NOT NULL');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT `fk_equipements` FOREIGN KEY (id_fournisseur) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_3f02d86b2e8c07c5 ON equipements');
        $this->addSql('CREATE INDEX fk_equipements ON equipements (id_fournisseur)');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT FK_3F02D86B2E8C07C5 FOREIGN KEY (id_fournisseur) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE evennementagricole CHANGE id_ev id_ev INT AUTO_INCREMENT NOT NULL, CHANGE description description TEXT NOT NULL, CHANGE date_debut date_debut DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE date_fin date_fin DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY FK_2F84F8E93B7489CC');
        $this->addSql('ALTER TABLE maintenance DROP FOREIGN KEY FK_2F84F8E93B7489CC');
        $this->addSql('ALTER TABLE maintenance CHANGE id_maintenance id_maintenance INT AUTO_INCREMENT NOT NULL, CHANGE description description TEXT NOT NULL');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT `fk_maintenances_user` FOREIGN KEY (id_agriculteur) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_2f84f8e93b7489cc ON maintenance');
        $this->addSql('CREATE INDEX fk_maintenances_user ON maintenance (id_agriculteur)');
        $this->addSql('ALTER TABLE maintenance ADD CONSTRAINT FK_2F84F8E93B7489CC FOREIGN KEY (id_agriculteur) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF21D3E4624');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF21D3E4624');
        $this->addSql('ALTER TABLE panier CHANGE id_panier id_panier INT AUTO_INCREMENT NOT NULL, CHANGE id_equipement id_equipement INT NOT NULL');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT `fk_panier` FOREIGN KEY (id_equipement) REFERENCES equipements (id_equipement) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_24cc0df21d3e4624 ON panier');
        $this->addSql('CREATE INDEX fk_panier ON panier (id_equipement)');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF21D3E4624 FOREIGN KEY (id_equipement) REFERENCES equipements (id_equipement) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participants CHANGE id_participant id_participant INT AUTO_INCREMENT NOT NULL');
        $this->addSql('CREATE INDEX fk_participant ON participants (id_utilisateur)');
        $this->addSql('CREATE INDEX fk2_participant ON participants (id_ev)');
        $this->addSql('ALTER TABLE suivi_animal DROP FOREIGN KEY FK_C86F5E4D9BA8B85');
        $this->addSql('ALTER TABLE suivi_animal DROP FOREIGN KEY FK_C86F5E4D9BA8B85');
        $this->addSql('ALTER TABLE suivi_animal ADD idSuivi INT AUTO_INCREMENT NOT NULL, ADD rythmeCardiaque INT NOT NULL, ADD niveauActitive VARCHAR(50) DEFAULT NULL, ADD etatSante VARCHAR(50) NOT NULL, ADD niveauActivite VARCHAR(50) NOT NULL, DROP id_suivi, DROP rythme_cardiaque, DROP niveau_actitive, DROP etat_sante, DROP niveau_activite, CHANGE remarque remarque TEXT NOT NULL, CHANGE idAnimal idAnimal INT NOT NULL, CHANGE date_suivi dateSuivi DATETIME NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idSuivi)');
        $this->addSql('ALTER TABLE suivi_animal ADD CONSTRAINT `fkhh` FOREIGN KEY (idAnimal) REFERENCES animal (idAnimal) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_c86f5e4d9ba8b85 ON suivi_animal');
        $this->addSql('CREATE INDEX fk_animal ON suivi_animal (idAnimal)');
        $this->addSql('ALTER TABLE suivi_animal ADD CONSTRAINT FK_C86F5E4D9BA8B85 FOREIGN KEY (idAnimal) REFERENCES animal (idAnimal) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tache CHANGE id_tache id_tache INT AUTO_INCREMENT NOT NULL, CHANGE description description TEXT NOT NULL, CHANGE id_technicien id_technicien INT DEFAULT NULL, CHANGE nom_tache nomTache VARCHAR(50) NOT NULL');
        $this->addSql('CREATE INDEX fk_tache ON tache (id_maintenance)');
        $this->addSql('ALTER TABLE user CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE date date DATE DEFAULT NULL, CHANGE image image LONGBLOB DEFAULT NULL, CHANGE genre genre CHAR(255) NOT NULL, CHANGE profile_complete profile_complete TINYINT DEFAULT 1, CHANGE numero_t numeroT INT NOT NULL');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4C64B64DCC');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4C64B64DCC');
        $this->addSql('ALTER TABLE vente ADD idVente INT AUTO_INCREMENT NOT NULL, ADD prixUnitaire INT NOT NULL, ADD chiffreAffaires INT NOT NULL, DROP id_vente, DROP prix_unitaire, DROP chiffre_affaires, CHANGE userId userId INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idVente)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT `fk_vente_user` FOREIGN KEY (userId) REFERENCES user (id)');
        $this->addSql('DROP INDEX idx_888a2a4c64b64dcc ON vente');
        $this->addSql('CREATE INDEX fk_vente_user ON vente (userId)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4C64B64DCC FOREIGN KEY (userId) REFERENCES user (id) ON DELETE CASCADE');
    }
}
