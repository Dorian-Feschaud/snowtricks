<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241018085843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CB281BE2E FOREIGN KEY (trick_id) REFERENCES trick (id)');
        $this->addSql('ALTER TABLE trick ADD groupe_id INT NOT NULL');
        $this->addSql('ALTER TABLE trick ADD CONSTRAINT FK_D8F0A91E7A45358C FOREIGN KEY (groupe_id) REFERENCES `group` (id)');
        $this->addSql('CREATE INDEX IDX_D8F0A91E7A45358C ON trick (groupe_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10CB281BE2E');
        $this->addSql('ALTER TABLE trick DROP FOREIGN KEY FK_D8F0A91E7A45358C');
        $this->addSql('DROP INDEX IDX_D8F0A91E7A45358C ON trick');
        $this->addSql('ALTER TABLE trick DROP groupe_id');
    }
}
