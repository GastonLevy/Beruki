<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260525170632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cash_cut (id INT AUTO_INCREMENT NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, payments_count INT NOT NULL, closed_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_EEAC2E98A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cash_cut ADD CONSTRAINT FK_EEAC2E98A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_payment ADD cash_cut_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT FK_71F520B34126CA79 FOREIGN KEY (cash_cut_id) REFERENCES cash_cut (id)');
        $this->addSql('CREATE INDEX IDX_71F520B34126CA79 ON customer_payment (cash_cut_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cash_cut DROP FOREIGN KEY FK_EEAC2E98A76ED395');
        $this->addSql('DROP TABLE cash_cut');
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY FK_71F520B34126CA79');
        $this->addSql('DROP INDEX IDX_71F520B34126CA79 ON customer_payment');
        $this->addSql('ALTER TABLE customer_payment DROP cash_cut_id');
    }
}
