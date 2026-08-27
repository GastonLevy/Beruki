<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stable MikroTik rate key to plans.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE plan ADD mikrotik_rate_key VARCHAR(50) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DD5A5B7D9E957569 ON plan (mikrotik_rate_key)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_DD5A5B7D9E957569 ON plan');
        $this->addSql('ALTER TABLE plan DROP mikrotik_rate_key');
    }
}
