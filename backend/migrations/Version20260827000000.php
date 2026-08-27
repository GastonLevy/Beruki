<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000000 extends AbstractMigration
{
    private const CUSTOMER_CODE_DIGITS = '0123456789';
    private const CUSTOMER_CODE_LETTERS = 'abcdefghijklmnopqrstuvwxyz';

    public function getDescription(): string
    {
        return 'Add public customer codes and customer connections.';
    }

    public function up(Schema $schema): void
    {
        $customerIds = $this->connection->fetchFirstColumn('SELECT id FROM customer ORDER BY id ASC');
        $customerCodesById = $this->generateCustomerCodesById($customerIds);

        $this->addSql('ALTER TABLE customer ADD customer_code VARCHAR(8) DEFAULT NULL, CHANGE full_name full_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE TABLE customer_connection (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, service_ip VARCHAR(45) DEFAULT NULL, mac_address VARCHAR(17) DEFAULT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D43E77E4F7867D9B (service_ip), INDEX IDX_D43E77E49395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE customer_connection ADD CONSTRAINT FK_CUSTOMER_CONNECTION_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id)');

        foreach ($customerCodesById as $customerId => $customerCode) {
            $this->addSql(
                'UPDATE customer SET customer_code = :customerCode WHERE id = :customerId',
                [
                    'customerCode' => $customerCode,
                    'customerId' => $customerId,
                ]
            );
        }

        $this->addSql('ALTER TABLE customer CHANGE customer_code customer_code VARCHAR(8) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_81398E09238494EF ON customer (customer_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE customer SET full_name = '' WHERE full_name IS NULL");
        $this->addSql('ALTER TABLE customer_connection DROP FOREIGN KEY FK_CUSTOMER_CONNECTION_CUSTOMER');
        $this->addSql('DROP TABLE customer_connection');
        $this->addSql('DROP INDEX UNIQ_81398E09238494EF ON customer');
        $this->addSql('ALTER TABLE customer DROP customer_code, CHANGE full_name full_name VARCHAR(255) NOT NULL');
    }

    /**
     * @param array<int, int|string> $customerIds
     * @return array<int|string, string>
     */
    private function generateCustomerCodesById(array $customerIds): array
    {
        $usedCodes = [];
        $customerCodesById = [];

        foreach ($customerIds as $customerId) {
            do {
                $customerCode = $this->generateCustomerCode();
            } while (isset($usedCodes[$customerCode]));

            $usedCodes[$customerCode] = true;
            $customerCodesById[$customerId] = $customerCode;
        }

        return $customerCodesById;
    }

    private function generateCustomerCode(): string
    {
        $customerCode = '';

        for ($i = 0; $i < 4; $i++) {
            $customerCode .= self::CUSTOMER_CODE_DIGITS[random_int(0, strlen(self::CUSTOMER_CODE_DIGITS) - 1)];
            $customerCode .= self::CUSTOMER_CODE_LETTERS[random_int(0, strlen(self::CUSTOMER_CODE_LETTERS) - 1)];
        }

        return $customerCode;
    }
}
