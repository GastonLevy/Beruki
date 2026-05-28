<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528200247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customer_plan (id INT AUTO_INCREMENT NOT NULL, started_at DATETIME NOT NULL, is_active TINYINT NOT NULL, customer_id INT NOT NULL, plan_id INT NOT NULL, INDEX IDX_89CBFF0A9395C3F3 (customer_id), INDEX IDX_89CBFF0AE899029B (plan_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, monthly_price NUMERIC(12, 2) NOT NULL, is_active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE customer_plan ADD CONSTRAINT FK_89CBFF0A9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE customer_plan ADD CONSTRAINT FK_89CBFF0AE899029B FOREIGN KEY (plan_id) REFERENCES plan (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_plan DROP FOREIGN KEY FK_89CBFF0A9395C3F3');
        $this->addSql('ALTER TABLE customer_plan DROP FOREIGN KEY FK_89CBFF0AE899029B');
        $this->addSql('DROP TABLE customer_plan');
        $this->addSql('DROP TABLE plan');
    }
}
