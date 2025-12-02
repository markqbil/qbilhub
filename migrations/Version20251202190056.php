<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202190056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE purchase_contracts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE received_documents_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tenant_relation_mappings_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE tenants_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_delegations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE purchase_contracts (id INT NOT NULL, tenant_id INT NOT NULL, created_by_id INT DEFAULT NULL, contract_number VARCHAR(100) NOT NULL, supplier VARCHAR(255) NOT NULL, product VARCHAR(255) NOT NULL, quantity NUMERIC(10, 2) NOT NULL, unit VARCHAR(50) NOT NULL, price_per_unit NUMERIC(10, 2) NOT NULL, currency VARCHAR(10) NOT NULL, delivery_date DATE NOT NULL, delivery_location VARCHAR(255) DEFAULT NULL, additional_terms JSONB DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8C1C5BB8AAD0FA19 ON purchase_contracts (contract_number)');
        $this->addSql('CREATE INDEX IDX_8C1C5BB89033212A ON purchase_contracts (tenant_id)');
        $this->addSql('CREATE INDEX IDX_8C1C5BB8B03A8386 ON purchase_contracts (created_by_id)');
        $this->addSql('COMMENT ON COLUMN purchase_contracts.delivery_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN purchase_contracts.additional_terms IS \'(DC2Type:jsonb)\'');
        $this->addSql('COMMENT ON COLUMN purchase_contracts.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN purchase_contracts.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE received_documents (id INT NOT NULL, source_tenant_id INT NOT NULL, target_tenant_id INT NOT NULL, processed_by_id INT DEFAULT NULL, linked_contract_id INT DEFAULT NULL, status VARCHAR(50) NOT NULL, document_type VARCHAR(100) NOT NULL, document_url VARCHAR(255) DEFAULT NULL, raw_data JSONB NOT NULL, extracted_schema JSONB DEFAULT NULL, mapped_data JSONB DEFAULT NULL, confidence_scores JSONB DEFAULT NULL, is_read BOOLEAN NOT NULL, processed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, received_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_291CDF68F8ADB2E4 ON received_documents (source_tenant_id)');
        $this->addSql('CREATE INDEX IDX_291CDF68A4875306 ON received_documents (target_tenant_id)');
        $this->addSql('CREATE INDEX IDX_291CDF682FFD4FD3 ON received_documents (processed_by_id)');
        $this->addSql('CREATE INDEX IDX_291CDF68CD59F015 ON received_documents (linked_contract_id)');
        $this->addSql('COMMENT ON COLUMN received_documents.raw_data IS \'(DC2Type:jsonb)\'');
        $this->addSql('COMMENT ON COLUMN received_documents.extracted_schema IS \'(DC2Type:jsonb)\'');
        $this->addSql('COMMENT ON COLUMN received_documents.mapped_data IS \'(DC2Type:jsonb)\'');
        $this->addSql('COMMENT ON COLUMN received_documents.confidence_scores IS \'(DC2Type:jsonb)\'');
        $this->addSql('COMMENT ON COLUMN received_documents.processed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN received_documents.received_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN received_documents.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN received_documents.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE tenant_relation_mappings (id INT NOT NULL, source_tenant_id INT NOT NULL, internal_relation_id VARCHAR(255) NOT NULL, external_tenant_code VARCHAR(100) NOT NULL, is_active BOOLEAN NOT NULL, default_send_via_hub BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B9E340F3F8ADB2E4 ON tenant_relation_mappings (source_tenant_id)');
        $this->addSql('COMMENT ON COLUMN tenant_relation_mappings.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN tenant_relation_mappings.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE tenants (id INT NOT NULL, name VARCHAR(255) NOT NULL, tenant_code VARCHAR(100) NOT NULL, is_hub_active BOOLEAN NOT NULL, logo_url VARCHAR(255) DEFAULT NULL, metadata JSONB DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8FC96BB5E237E06 ON tenants (name)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B8FC96BB3D7A6A4B ON tenants (tenant_code)');
        $this->addSql('COMMENT ON COLUMN tenants.metadata IS \'(DC2Type:jsonb)\'');
        $this->addSql('COMMENT ON COLUMN tenants.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN tenants.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE user_delegations (id INT NOT NULL, delegator_id INT NOT NULL, delegate_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_813F34388825BEFA ON user_delegations (delegator_id)');
        $this->addSql('CREATE INDEX IDX_813F34388A0BB485 ON user_delegations (delegate_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_delegation ON user_delegations (delegator_id, delegate_id)');
        $this->addSql('COMMENT ON COLUMN user_delegations.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE users (id INT NOT NULL, tenant_id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E99033212A ON users (tenant_id)');
        $this->addSql('COMMENT ON COLUMN users.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE purchase_contracts ADD CONSTRAINT FK_8C1C5BB89033212A FOREIGN KEY (tenant_id) REFERENCES tenants (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE purchase_contracts ADD CONSTRAINT FK_8C1C5BB8B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE received_documents ADD CONSTRAINT FK_291CDF68F8ADB2E4 FOREIGN KEY (source_tenant_id) REFERENCES tenants (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE received_documents ADD CONSTRAINT FK_291CDF68A4875306 FOREIGN KEY (target_tenant_id) REFERENCES tenants (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE received_documents ADD CONSTRAINT FK_291CDF682FFD4FD3 FOREIGN KEY (processed_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE received_documents ADD CONSTRAINT FK_291CDF68CD59F015 FOREIGN KEY (linked_contract_id) REFERENCES purchase_contracts (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tenant_relation_mappings ADD CONSTRAINT FK_B9E340F3F8ADB2E4 FOREIGN KEY (source_tenant_id) REFERENCES tenants (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_delegations ADD CONSTRAINT FK_813F34388825BEFA FOREIGN KEY (delegator_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_delegations ADD CONSTRAINT FK_813F34388A0BB485 FOREIGN KEY (delegate_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E99033212A FOREIGN KEY (tenant_id) REFERENCES tenants (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE purchase_contracts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE received_documents_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tenant_relation_mappings_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE tenants_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE user_delegations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
        $this->addSql('ALTER TABLE purchase_contracts DROP CONSTRAINT FK_8C1C5BB89033212A');
        $this->addSql('ALTER TABLE purchase_contracts DROP CONSTRAINT FK_8C1C5BB8B03A8386');
        $this->addSql('ALTER TABLE received_documents DROP CONSTRAINT FK_291CDF68F8ADB2E4');
        $this->addSql('ALTER TABLE received_documents DROP CONSTRAINT FK_291CDF68A4875306');
        $this->addSql('ALTER TABLE received_documents DROP CONSTRAINT FK_291CDF682FFD4FD3');
        $this->addSql('ALTER TABLE received_documents DROP CONSTRAINT FK_291CDF68CD59F015');
        $this->addSql('ALTER TABLE tenant_relation_mappings DROP CONSTRAINT FK_B9E340F3F8ADB2E4');
        $this->addSql('ALTER TABLE user_delegations DROP CONSTRAINT FK_813F34388825BEFA');
        $this->addSql('ALTER TABLE user_delegations DROP CONSTRAINT FK_813F34388A0BB485');
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E99033212A');
        $this->addSql('DROP TABLE purchase_contracts');
        $this->addSql('DROP TABLE received_documents');
        $this->addSql('DROP TABLE tenant_relation_mappings');
        $this->addSql('DROP TABLE tenants');
        $this->addSql('DROP TABLE user_delegations');
        $this->addSql('DROP TABLE users');
    }
}
