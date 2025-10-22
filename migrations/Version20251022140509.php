<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251022140509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis ADD reservation_id INT NOT NULL');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('CREATE INDEX IDX_8F91ABF0B83297E7 ON avis (reservation_id)');
        $this->addSql('ALTER TABLE centre_commercial ADD admin_validateur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE centre_commercial ADD CONSTRAINT FK_E2C0AC833BB83048 FOREIGN KEY (admin_validateur_id) REFERENCES administrateur (id)');
        $this->addSql('CREATE INDEX IDX_E2C0AC833BB83048 ON centre_commercial (admin_validateur_id)');
        $this->addSql('ALTER TABLE conversation ADD locataire_id INT NOT NULL, ADD centre_commercial_id INT NOT NULL, ADD reservation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9D8A38199 FOREIGN KEY (locataire_id) REFERENCES locataire (id)');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E91CE4515E FOREIGN KEY (centre_commercial_id) REFERENCES centre_commercial (id)');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E9B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E9D8A38199 ON conversation (locataire_id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E91CE4515E ON conversation (centre_commercial_id)');
        $this->addSql('CREATE INDEX IDX_8A8E26E9B83297E7 ON conversation (reservation_id)');
        $this->addSql('ALTER TABLE document ADD reservation_id INT NOT NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('CREATE INDEX IDX_D8698A76B83297E7 ON document (reservation_id)');
        $this->addSql('ALTER TABLE emplacement ADD centre_commercial_id INT NOT NULL');
        $this->addSql('ALTER TABLE emplacement ADD CONSTRAINT FK_C0CF65F61CE4515E FOREIGN KEY (centre_commercial_id) REFERENCES centre_commercial (id)');
        $this->addSql('CREATE INDEX IDX_C0CF65F61CE4515E ON emplacement (centre_commercial_id)');
        $this->addSql('ALTER TABLE message ADD conversation_id INT NOT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F9AC0396 ON message (conversation_id)');
        $this->addSql('ALTER TABLE paiement ADD reservation_id INT NOT NULL');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1EB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B1DC7A1EB83297E7 ON paiement (reservation_id)');
        $this->addSql('ALTER TABLE periode_indisponibilite ADD emplacement_id INT NOT NULL');
        $this->addSql('ALTER TABLE periode_indisponibilite ADD CONSTRAINT FK_335890C5C4598A51 FOREIGN KEY (emplacement_id) REFERENCES emplacement (id)');
        $this->addSql('CREATE INDEX IDX_335890C5C4598A51 ON periode_indisponibilite (emplacement_id)');
        $this->addSql('ALTER TABLE photo ADD emplacement_id INT NOT NULL');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B78418C4598A51 FOREIGN KEY (emplacement_id) REFERENCES emplacement (id)');
        $this->addSql('CREATE INDEX IDX_14B78418C4598A51 ON photo (emplacement_id)');
        $this->addSql('ALTER TABLE reservation ADD locataire_id INT NOT NULL, ADD emplacement_id INT NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955D8A38199 FOREIGN KEY (locataire_id) REFERENCES locataire (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955C4598A51 FOREIGN KEY (emplacement_id) REFERENCES emplacement (id)');
        $this->addSql('CREATE INDEX IDX_42C84955D8A38199 ON reservation (locataire_id)');
        $this->addSql('CREATE INDEX IDX_42C84955C4598A51 ON reservation (emplacement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0B83297E7');
        $this->addSql('DROP INDEX IDX_8F91ABF0B83297E7 ON avis');
        $this->addSql('ALTER TABLE avis DROP reservation_id');
        $this->addSql('ALTER TABLE centre_commercial DROP FOREIGN KEY FK_E2C0AC833BB83048');
        $this->addSql('DROP INDEX IDX_E2C0AC833BB83048 ON centre_commercial');
        $this->addSql('ALTER TABLE centre_commercial DROP admin_validateur_id');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9D8A38199');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E91CE4515E');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E9B83297E7');
        $this->addSql('DROP INDEX IDX_8A8E26E9D8A38199 ON conversation');
        $this->addSql('DROP INDEX IDX_8A8E26E91CE4515E ON conversation');
        $this->addSql('DROP INDEX IDX_8A8E26E9B83297E7 ON conversation');
        $this->addSql('ALTER TABLE conversation DROP locataire_id, DROP centre_commercial_id, DROP reservation_id');
        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_D8698A76B83297E7');
        $this->addSql('DROP INDEX IDX_D8698A76B83297E7 ON document');
        $this->addSql('ALTER TABLE document DROP reservation_id');
        $this->addSql('ALTER TABLE emplacement DROP FOREIGN KEY FK_C0CF65F61CE4515E');
        $this->addSql('DROP INDEX IDX_C0CF65F61CE4515E ON emplacement');
        $this->addSql('ALTER TABLE emplacement DROP centre_commercial_id');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F9AC0396');
        $this->addSql('DROP INDEX IDX_B6BD307F9AC0396 ON message');
        $this->addSql('ALTER TABLE message DROP conversation_id');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1EB83297E7');
        $this->addSql('DROP INDEX UNIQ_B1DC7A1EB83297E7 ON paiement');
        $this->addSql('ALTER TABLE paiement DROP reservation_id');
        $this->addSql('ALTER TABLE periode_indisponibilite DROP FOREIGN KEY FK_335890C5C4598A51');
        $this->addSql('DROP INDEX IDX_335890C5C4598A51 ON periode_indisponibilite');
        $this->addSql('ALTER TABLE periode_indisponibilite DROP emplacement_id');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B78418C4598A51');
        $this->addSql('DROP INDEX IDX_14B78418C4598A51 ON photo');
        $this->addSql('ALTER TABLE photo DROP emplacement_id');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955D8A38199');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955C4598A51');
        $this->addSql('DROP INDEX IDX_42C84955D8A38199 ON reservation');
        $this->addSql('DROP INDEX IDX_42C84955C4598A51 ON reservation');
        $this->addSql('ALTER TABLE reservation DROP locataire_id, DROP emplacement_id');
    }
}
