<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240621111046 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wallet ADD id INT AUTO_INCREMENT NOT NULL, CHANGE wallet_id wallet_id INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wallet MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON wallet');
        $this->addSql('ALTER TABLE wallet DROP id, CHANGE wallet_id wallet_id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE wallet ADD PRIMARY KEY (wallet_id)');
    }
}
