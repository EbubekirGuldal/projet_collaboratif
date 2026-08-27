<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration initiale : cree l integralite du schema a partir des entites de
 * src/Entity. Elle est le point de depart d une base vide, aussi bien en
 * conteneur qu en integration continue.
 *
 * SQL genere par Doctrine\ORM\Tools\SchemaTool sur MySQL80Platform.
 */
final class Version20260827090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Schema initial de (RE)Sources Relationnelles (13 tables, MySQL 8).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, user_id INT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(255) NOT NULL, INDEX IDX_9474526C89329D25 (resource_id), INDEX IDX_9474526CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE moderation_log (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, user_id INT NOT NULL, target_type VARCHAR(255) NOT NULL, action VARCHAR(255) NOT NULL, reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7AE8684D89329D25 (resource_id), INDEX IDX_7AE8684DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE relation_kind (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE resource (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, category_id INT DEFAULT NULL, ressource_type_id INT DEFAULT NULL, relation_kind_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, external_url VARCHAR(255) DEFAULT NULL, published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', image VARCHAR(255) DEFAULT NULL, video VARCHAR(255) DEFAULT NULL, shares_count INT DEFAULT 0 NOT NULL, likes_count INT DEFAULT NULL, resource_status VARCHAR(255) NOT NULL, INDEX IDX_BC91F416A76ED395 (user_id), INDEX IDX_BC91F41612469DE2 (category_id), INDEX IDX_BC91F41670760271 (ressource_type_id), INDEX IDX_BC91F416291B3ED8 (relation_kind_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE resource_like (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, resource_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A343E009A76ED395 (user_id), INDEX IDX_A343E00989329D25 (resource_id), UNIQUE INDEX uniq_resource_like_user_resource (user_id, resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ressource_type (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE share (id INT AUTO_INCREMENT NOT NULL, channel VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, last_name VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, is_verified TINYINT(1) DEFAULT 0 NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', picture VARCHAR(255) DEFAULT NULL, last_connexion DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_favorites (user_id INT NOT NULL, resource_id INT NOT NULL, INDEX IDX_E489ED11A76ED395 (user_id), INDEX IDX_E489ED1189329D25 (resource_id), PRIMARY KEY(user_id, resource_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_liked (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, resource_id INT NOT NULL, INDEX IDX_28DB30E2A76ED395 (user_id), INDEX IDX_28DB30E289329D25 (resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_resource_state (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, resource_id INT NOT NULL, is_liked TINYINT(1) NOT NULL, is_saved TINYINT(1) NOT NULL, is_exploited TINYINT(1) NOT NULL, INDEX IDX_E3AD7790A76ED395 (user_id), INDEX IDX_E3AD779089329D25 (resource_id), UNIQUE INDEX uniq_user_resource (user_id, resource_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE moderation_log ADD CONSTRAINT FK_7AE8684D89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('ALTER TABLE moderation_log ADD CONSTRAINT FK_7AE8684DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F41612469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F41670760271 FOREIGN KEY (ressource_type_id) REFERENCES ressource_type (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416291B3ED8 FOREIGN KEY (relation_kind_id) REFERENCES relation_kind (id)');
        $this->addSql('ALTER TABLE resource_like ADD CONSTRAINT FK_A343E009A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE resource_like ADD CONSTRAINT FK_A343E00989329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT FK_E489ED11A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorites ADD CONSTRAINT FK_E489ED1189329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_liked ADD CONSTRAINT FK_28DB30E2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_liked ADD CONSTRAINT FK_28DB30E289329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('ALTER TABLE user_resource_state ADD CONSTRAINT FK_E3AD7790A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_resource_state ADD CONSTRAINT FK_E3AD779089329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C89329D25');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE moderation_log DROP FOREIGN KEY FK_7AE8684D89329D25');
        $this->addSql('ALTER TABLE moderation_log DROP FOREIGN KEY FK_7AE8684DA76ED395');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416A76ED395');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F41612469DE2');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F41670760271');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416291B3ED8');
        $this->addSql('ALTER TABLE resource_like DROP FOREIGN KEY FK_A343E009A76ED395');
        $this->addSql('ALTER TABLE resource_like DROP FOREIGN KEY FK_A343E00989329D25');
        $this->addSql('ALTER TABLE user_favorites DROP FOREIGN KEY FK_E489ED11A76ED395');
        $this->addSql('ALTER TABLE user_favorites DROP FOREIGN KEY FK_E489ED1189329D25');
        $this->addSql('ALTER TABLE user_liked DROP FOREIGN KEY FK_28DB30E2A76ED395');
        $this->addSql('ALTER TABLE user_liked DROP FOREIGN KEY FK_28DB30E289329D25');
        $this->addSql('ALTER TABLE user_resource_state DROP FOREIGN KEY FK_E3AD7790A76ED395');
        $this->addSql('ALTER TABLE user_resource_state DROP FOREIGN KEY FK_E3AD779089329D25');
        $this->addSql('DROP TABLE user_resource_state');
        $this->addSql('DROP TABLE user_liked');
        $this->addSql('DROP TABLE user_favorites');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE share');
        $this->addSql('DROP TABLE ressource_type');
        $this->addSql('DROP TABLE resource_like');
        $this->addSql('DROP TABLE resource');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE relation_kind');
        $this->addSql('DROP TABLE moderation_log');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE category');
    }
}
