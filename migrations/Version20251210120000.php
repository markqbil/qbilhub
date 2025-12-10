<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251210120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add externalId and metadata fields to received_documents for Qbil Trade integration';
    }

    public function up(Schema $schema): void
    {
        // Add external_id field for tracking Qbil Trade contract IDs
        $this->addSql('ALTER TABLE received_documents ADD external_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_received_documents_external_id ON received_documents (external_id)');

        // Add metadata JSONB field for storing integration metadata
        $this->addSql('ALTER TABLE received_documents ADD metadata JSONB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_received_documents_external_id');
        $this->addSql('ALTER TABLE received_documents DROP external_id');
        $this->addSql('ALTER TABLE received_documents DROP metadata');
    }
}
