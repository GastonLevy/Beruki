<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260525152609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customer_payment (id INT AUTO_INCREMENT NOT NULL, paid_at DATETIME NOT NULL, amount NUMERIC(10, 2) NOT NULL, customer_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_71F520B39395C3F3 (customer_id), INDEX IDX_71F520B3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT FK_71F520B39395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE customer_payment ADD CONSTRAINT FK_71F520B3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY FK_71F520B39395C3F3');
        $this->addSql('ALTER TABLE customer_payment DROP FOREIGN KEY FK_71F520B3A76ED395');
        $this->addSql('DROP TABLE customer_payment');
    }
}
