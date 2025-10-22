<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251022130350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, note_globale INT NOT NULL, note_proprete_conformite INT DEFAULT NULL, note_emplacement INT DEFAULT NULL, note_qualite_prix INT DEFAULT NULL, note_communication INT DEFAULT NULL, commentaire LONGTEXT DEFAULT NULL, type_auteur VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, date_publication DATETIME DEFAULT NULL, est_publie TINYINT(1) NOT NULL, reponse LONGTEXT DEFAULT NULL, date_reponse DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, sujet VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, dernier_message_date DATETIME DEFAULT NULL, est_archivee TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE document (id INT AUTO_INCREMENT NOT NULL, type_document VARCHAR(20) NOT NULL, numero_document VARCHAR(50) NOT NULL, date_generation DATETIME NOT NULL, chemin_fichier VARCHAR(255) NOT NULL, statut VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE emplacement (id INT AUTO_INCREMENT NOT NULL, titre_annonce VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, surface NUMERIC(10, 2) NOT NULL, localisation_precise VARCHAR(255) DEFAULT NULL, type_emplacement VARCHAR(50) NOT NULL, equipements LONGTEXT DEFAULT NULL, tarif_jour NUMERIC(10, 2) NOT NULL, tarif_semaine NUMERIC(10, 2) DEFAULT NULL, tarif_mois NUMERIC(10, 2) DEFAULT NULL, caution NUMERIC(10, 2) DEFAULT NULL, duree_min_location INT DEFAULT NULL, duree_max_location INT DEFAULT NULL, statut_annonce VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, nombre_vues INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_envoi DATETIME NOT NULL, est_lu TINYINT(1) NOT NULL, date_lecture DATETIME DEFAULT NULL, type_expediteur VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paiement (id INT AUTO_INCREMENT NOT NULL, montant NUMERIC(10, 2) NOT NULL, date_paiement DATETIME NOT NULL, methode_paiement VARCHAR(20) NOT NULL, statut VARCHAR(20) NOT NULL, transaction_id VARCHAR(255) DEFAULT NULL, date_remboursement DATETIME DEFAULT NULL, montant_rembourse NUMERIC(10, 2) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE parametre (id INT AUTO_INCREMENT NOT NULL, nom_parametre VARCHAR(100) NOT NULL, valeur VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, date_modification DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE periode_indisponibilite (id INT AUTO_INCREMENT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, motif LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE photo (id INT AUTO_INCREMENT NOT NULL, chemin_fichier VARCHAR(255) NOT NULL, legende VARCHAR(255) DEFAULT NULL, ordre_affichage INT NOT NULL, date_upload DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, montant_location NUMERIC(10, 2) NOT NULL, montant_commission NUMERIC(10, 2) NOT NULL, montant_total NUMERIC(10, 2) NOT NULL, caution_versee NUMERIC(10, 2) DEFAULT NULL, statut VARCHAR(30) NOT NULL, date_demande DATETIME NOT NULL, date_validation DATETIME DEFAULT NULL, date_paiement DATETIME DEFAULT NULL, motif_refus LONGTEXT DEFAULT NULL, annulee_par VARCHAR(20) DEFAULT NULL, date_annulation DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE emplacement');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE parametre');
        $this->addSql('DROP TABLE periode_indisponibilite');
        $this->addSql('DROP TABLE photo');
        $this->addSql('DROP TABLE reservation');
    }
}
