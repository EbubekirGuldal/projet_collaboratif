<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313132423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE resource ADD ressource_type_id INT DEFAULT NULL, ADD relation_kind_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F41670760271 FOREIGN KEY (ressource_type_id) REFERENCES ressource_type (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416291B3ED8 FOREIGN KEY (relation_kind_id) REFERENCES relation_kind (id)');
        $this->addSql('CREATE INDEX IDX_BC91F41670760271 ON resource (ressource_type_id)');
        $this->addSql('CREATE INDEX IDX_BC91F416291B3ED8 ON resource (relation_kind_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F41670760271');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416291B3ED8');
        $this->addSql('DROP INDEX IDX_BC91F41670760271 ON resource');
        $this->addSql('DROP INDEX IDX_BC91F416291B3ED8 ON resource');
        $this->addSql('ALTER TABLE resource DROP ressource_type_id, DROP relation_kind_id');
    }
}
