<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260321144335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE resource_like (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, resource_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A343E009A76ED395 (user_id), INDEX IDX_A343E00989329D25 (resource_id), UNIQUE INDEX uniq_resource_like_user_resource (user_id, resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_resource_state (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, resource_id INT NOT NULL, is_liked TINYINT(1) NOT NULL, is_saved TINYINT(1) NOT NULL, is_exploited TINYINT(1) NOT NULL, INDEX IDX_E3AD7790A76ED395 (user_id), INDEX IDX_E3AD779089329D25 (resource_id), UNIQUE INDEX uniq_user_resource (user_id, resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE resource_like ADD CONSTRAINT FK_A343E009A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE resource_like ADD CONSTRAINT FK_A343E00989329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_resource_state ADD CONSTRAINT FK_E3AD7790A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_resource_state ADD CONSTRAINT FK_E3AD779089329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE resource ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F41612469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_BC91F41612469DE2 ON resource (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE resource_like DROP FOREIGN KEY FK_A343E009A76ED395');
        $this->addSql('ALTER TABLE resource_like DROP FOREIGN KEY FK_A343E00989329D25');
        $this->addSql('ALTER TABLE user_resource_state DROP FOREIGN KEY FK_E3AD7790A76ED395');
        $this->addSql('ALTER TABLE user_resource_state DROP FOREIGN KEY FK_E3AD779089329D25');
        $this->addSql('DROP TABLE resource_like');
        $this->addSql('DROP TABLE user_resource_state');
        $this->addSql('ALTER TABLE comment DROP status');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F41612469DE2');
        $this->addSql('DROP INDEX IDX_BC91F41612469DE2 ON resource');
        $this->addSql('ALTER TABLE resource DROP category_id');
    }
}
