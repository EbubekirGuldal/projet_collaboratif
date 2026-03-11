<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225083730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $orphanComments = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM comment c
             LEFT JOIN `user` u ON u.id = c.user_id
             WHERE u.id IS NULL'
        );

        if ($orphanComments > 0) {
            $fallbackUserId = $this->connection->fetchOne('SELECT id FROM `user` ORDER BY id ASC LIMIT 1');
            $this->abortIf(
                $fallbackUserId === false,
                sprintf('Cannot repair %d orphan comments because table `user` is empty.', $orphanComments)
            );

            $this->addSql(sprintf(
                'UPDATE comment c
                 LEFT JOIN `user` u ON u.id = c.user_id
                 SET c.user_id = %d
                 WHERE u.id IS NULL',
                (int) $fallbackUserId
            ));
        }

        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_9474526CA76ED395 ON comment (user_id)');
        $this->addSql('ALTER TABLE resource ADD user_id INT DEFAULT NULL, ADD likes_count INT DEFAULT NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_BC91F416A76ED395 ON resource (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('DROP INDEX IDX_9474526CA76ED395 ON comment');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416A76ED395');
        $this->addSql('DROP INDEX IDX_BC91F416A76ED395 ON resource');
        $this->addSql('ALTER TABLE resource DROP user_id, DROP likes_count');
    }
}
