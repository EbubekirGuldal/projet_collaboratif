<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313131523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_liked (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, resource_id INT NOT NULL, INDEX IDX_28DB30E2A76ED395 (user_id), INDEX IDX_28DB30E289329D25 (resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_liked ADD CONSTRAINT FK_28DB30E2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_liked ADD CONSTRAINT FK_28DB30E289329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('DROP TABLE user_resource_state');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_resource_state (id INT AUTO_INCREMENT NOT NULL, is_favorite TINYINT(1) DEFAULT NULL, is_exploited TINYINT(1) DEFAULT NULL, is_saved_for_later TINYINT(1) DEFAULT NULL, started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_interaction_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE user_liked DROP FOREIGN KEY FK_28DB30E2A76ED395');
        $this->addSql('ALTER TABLE user_liked DROP FOREIGN KEY FK_28DB30E289329D25');
        $this->addSql('DROP TABLE user_liked');
    }
}
