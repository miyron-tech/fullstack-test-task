<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623131722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add refund table for tracking partial and full refunds with idempotency';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE refund (
                id              INT AUTO_INCREMENT NOT NULL,
                transaction_id  INT NOT NULL,
                amount          NUMERIC(14, 2) NOT NULL,
                reason          LONGTEXT DEFAULT NULL,
                idempotency_key VARCHAR(64) NOT NULL,
                provider_refund_id VARCHAR(64) DEFAULT NULL,
                created_at      DATETIME NOT NULL,
                INDEX IDX_5B2D60A42FC0CB0F (transaction_id),
                UNIQUE INDEX UNIQ_5B2D60A4A2A8D7B5 (idempotency_key),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);

        $this->addSql(
            'ALTER TABLE refund ADD CONSTRAINT FK_5B2D60A42FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refund DROP FOREIGN KEY FK_5B2D60A42FC0CB0F');
        $this->addSql('DROP TABLE refund');
    }
}