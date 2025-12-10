<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251203100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit_logs table for comprehensive action tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_logs (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL,
            on_behalf_of_id INT DEFAULT NULL,
            tenant_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            entity_type VARCHAR(100) NOT NULL,
            entity_id INT DEFAULT NULL,
            description VARCHAR(255) DEFAULT NULL,
            old_values JSONB DEFAULT NULL,
            new_values JSONB DEFAULT NULL,
            metadata JSONB DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT FK_audit_user FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
            CONSTRAINT FK_audit_on_behalf_of FOREIGN KEY (on_behalf_of_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
            CONSTRAINT FK_audit_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');

        $this->addSql('CREATE INDEX idx_audit_entity ON audit_logs (entity_type, entity_id)');
        $this->addSql('CREATE INDEX idx_audit_user ON audit_logs (user_id)');
        $this->addSql('CREATE INDEX idx_audit_tenant ON audit_logs (tenant_id)');
        $this->addSql('CREATE INDEX idx_audit_created ON audit_logs (created_at)');
        $this->addSql('CREATE INDEX idx_audit_action ON audit_logs (action)');

        $this->addSql('COMMENT ON COLUMN audit_logs.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_logs');
    }
}
