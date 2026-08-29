<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move customer connection technical fields into customer plans.';
    }

    public function up(Schema $schema): void
    {
        $ambiguousCustomers = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT c.id, COUNT(DISTINCT cc.id) AS connections_count, COUNT(DISTINCT cp.id) AS plans_count
                FROM customer c
                INNER JOIN customer_connection cc ON cc.customer_id = c.id
                LEFT JOIN customer_plan cp ON cp.customer_id = c.id
                GROUP BY c.id
                HAVING connections_count <> 1 OR plans_count <> 1
            SQL
        );

        if ($ambiguousCustomers !== []) {
            $summaries = array_map(
                static fn (array $row): string => sprintf(
                    'customer_id=%s connections=%s plans=%s',
                    $row['id'],
                    $row['connections_count'],
                    $row['plans_count']
                ),
                $ambiguousCustomers
            );

            throw new \RuntimeException(
                'Cannot safely migrate customer_connection data because some customers do not have exactly one connection and one plan: '
                . implode('; ', $summaries)
            );
        }

        $this->addSql('ALTER TABLE customer_plan ADD service_ip VARCHAR(45) DEFAULT NULL, ADD mac_address VARCHAR(17) DEFAULT NULL');
        $this->addSql(
            <<<'SQL'
                UPDATE customer_plan cp
                INNER JOIN customer_connection cc ON cc.customer_id = cp.customer_id
                SET cp.service_ip = cc.service_ip,
                    cp.mac_address = cc.mac_address,
                    cp.is_active = cc.is_active
            SQL
        );
        $this->addSql('CREATE UNIQUE INDEX UNIQ_89CBFF0AF7867D9B ON customer_plan (service_ip)');
        $this->addSql('ALTER TABLE customer_connection DROP FOREIGN KEY FK_CUSTOMER_CONNECTION_CUSTOMER');
        $this->addSql('DROP TABLE customer_connection');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE customer_connection (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, service_ip VARCHAR(45) DEFAULT NULL, mac_address VARCHAR(17) DEFAULT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D43E77E4F7867D9B (service_ip), INDEX IDX_D43E77E49395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE customer_connection ADD CONSTRAINT FK_CUSTOMER_CONNECTION_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql(
            <<<'SQL'
                INSERT INTO customer_connection (customer_id, service_ip, mac_address, is_active, created_at)
                SELECT customer_id, service_ip, mac_address, is_active, started_at
                FROM customer_plan
                WHERE service_ip IS NOT NULL OR mac_address IS NOT NULL
            SQL
        );
        $this->addSql('DROP INDEX UNIQ_89CBFF0AF7867D9B ON customer_plan');
        $this->addSql('ALTER TABLE customer_plan DROP service_ip, DROP mac_address');
    }
}
