<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200819093801 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(400) NOT NULL, email VARCHAR(255) NOT NULL, email_hash VARCHAR(500) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, type SMALLINT DEFAULT 2 NOT NULL, position VARCHAR(255) DEFAULT NULL, id_author INT NOT NULL, status SMALLINT DEFAULT 1 NOT NULL, avatar VARCHAR(400) DEFAULT NULL, last_login DATETIME DEFAULT NULL, roles VARCHAR(255) NOT NULL, salt VARCHAR(255) NOT NULL, INDEX index_id (id) USING BTREE, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tokens (id BIGINT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, token VARCHAR(500) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, expired DATETIME NOT NULL, INDEX index_id (id) USING BTREE, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE login_log (id BIGINT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, token VARCHAR(500) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, user_agent VARCHAR(1000) DEFAULT NULL, ip VARCHAR(100) DEFAULT NULL, origin VARCHAR(400) DEFAULT NULL, status SMALLINT DEFAULT NULL, INDEX index_id (id) USING BTREE, INDEX index_id_user (id_user) USING BTREE, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE login_log');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE tokens');
        $this->addSql('DROP TABLE users');
    }
}
