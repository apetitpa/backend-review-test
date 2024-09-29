<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240929135936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on create_at column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_EVENT_CREATE_AT ON event (create_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_EVENT_CREATE_AT');
    }
}
