<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Synchronize customer connection schema with current mapping.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer RENAME INDEX uniq_customer_customer_code TO UNIQ_81398E09238494EF');
        $this->addSql('ALTER TABLE customer_connection CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE customer_connection RENAME INDEX uniq_customer_connection_service_ip TO UNIQ_D43E77E4F7867D9B');
        $this->addSql('ALTER TABLE customer_connection RENAME INDEX idx_customer_connection_customer TO IDX_D43E77E49395C3F3');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_connection RENAME INDEX IDX_D43E77E49395C3F3 TO idx_customer_connection_customer');
        $this->addSql('ALTER TABLE customer_connection RENAME INDEX UNIQ_D43E77E4F7867D9B TO uniq_customer_connection_service_ip');
        $this->addSql('ALTER TABLE customer_connection CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE customer RENAME INDEX UNIQ_81398E09238494EF TO uniq_customer_customer_code');
    }
}
