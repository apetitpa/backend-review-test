<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240929094521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add default value to event count';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ALTER type TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE event ALTER count SET DEFAULT 1');
        $this->addSql('COMMENT ON COLUMN event.type IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "event" ALTER type TYPE VARCHAR(255) CHECK(type IN (\'COM\', \'MSG\', \'PR\'))');
        $this->addSql('ALTER TABLE "event" ALTER count DROP DEFAULT');
        $this->addSql('COMMENT ON COLUMN "event".type IS \'(DC2Type:EventType)\'');
    }
}
