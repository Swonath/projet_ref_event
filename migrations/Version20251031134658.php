<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251031134658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE favori (id INT AUTO_INCREMENT NOT NULL, locataire_id INT NOT NULL, emplacement_id INT NOT NULL, date_ajout DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_EF85A2CCD8A38199 (locataire_id), INDEX IDX_EF85A2CCC4598A51 (emplacement_id), UNIQUE INDEX unique_favori (locataire_id, emplacement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCD8A38199 FOREIGN KEY (locataire_id) REFERENCES locataire (id)');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCC4598A51 FOREIGN KEY (emplacement_id) REFERENCES emplacement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCD8A38199');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCC4598A51');
        $this->addSql('DROP TABLE favori');
    }
}
