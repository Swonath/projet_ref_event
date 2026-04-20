<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs latitude et longitude au centre commercial';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP latitude, DROP longitude');
    }
}
