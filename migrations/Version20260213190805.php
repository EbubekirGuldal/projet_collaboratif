<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213190805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment CHANGE status status VARCHAR(255) DEFAULT \'En attente\' NOT NULL, CHANGE parent_id resource_id INT NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('CREATE INDEX IDX_9474526C89329D25 ON comment (resource_id)');
        $this->addSql('ALTER TABLE resource CHANGE shares_count shares_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE is_verified is_verified TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C89329D25');
        $this->addSql('DROP INDEX IDX_9474526C89329D25 ON comment');
        $this->addSql('ALTER TABLE comment CHANGE status status VARCHAR(255) NOT NULL, CHANGE resource_id parent_id INT NOT NULL');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('ALTER TABLE resource CHANGE shares_count shares_count INT NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE is_verified is_verified TINYINT(1) NOT NULL');
    }
}
