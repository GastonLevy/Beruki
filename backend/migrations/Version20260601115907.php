<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260601115907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cash_cut (id INT AUTO_INCREMENT NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, user_commission_amount NUMERIC(10, 2) NOT NULL, user_commission_percentage NUMERIC(5, 2) NOT NULL, amount_to_withdraw NUMERIC(10, 2) NOT NULL, payments_count INT NOT NULL, closed_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_EEAC2E98A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, full_name VARCHAR(255) NOT NULL, subscriber_number VARCHAR(100) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, monthly_amount NUMERIC(10, 2) DEFAULT NULL, monthly_debt TINYINT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer_payment (id INT AUTO_INCREMENT NOT NULL, paid_at DATETIME NOT NULL, amount NUMERIC(10, 2) NOT NULL, customer_id INT NOT NULL, user_id INT NOT NULL, cash_cut_id INT DEFAULT NULL, INDEX IDX_71F520B39395C3F3 (customer_id), INDEX IDX_71F520B3A76ED395 (user_id), INDEX IDX_71F520B34126CA79 (cash_cut_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, commission_percentage NUMERIC(5, 2) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cash_cut ADD CONSTRAINT FK_EEAC2E98A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT FK_71F520B39395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT FK_71F520B3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT FK_71F520B34126CA79 FOREIGN KEY (cash_cut_id) REFERENCES cash_cut (id)');
        $this->addSql('ALTER TABLE customer_plan ADD CONSTRAINT FK_89CBFF0A9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE customer_plan ADD CONSTRAINT FK_89CBFF0AE899029B FOREIGN KEY (plan_id) REFERENCES plan (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cash_cut DROP FOREIGN KEY FK_EEAC2E98A76ED395');
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY FK_71F520B39395C3F3');
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY FK_71F520B3A76ED395');
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY FK_71F520B34126CA79');
        $this->addSql('DROP TABLE cash_cut');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE customer_payment');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE customer_plan DROP FOREIGN KEY FK_89CBFF0A9395C3F3');
        $this->addSql('ALTER TABLE customer_plan DROP FOREIGN KEY FK_89CBFF0AE899029B');
    }
}
