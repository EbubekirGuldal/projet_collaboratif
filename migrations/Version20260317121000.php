<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add category relation on resource.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F41612469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_BC91F41612469DE2 ON resource (category_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F41612469DE2');
        $this->addSql('DROP INDEX IDX_BC91F41612469DE2 ON resource');
        $this->addSql('ALTER TABLE resource DROP category_id');
    }
}
