<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261029100841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE auteur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, biographie LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, designation VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE editeur (id INT AUTO_INCREMENT NOT NULL, nom_editeur VARCHAR(255) NOT NULL, pays VARCHAR(50) NOT NULL, adresse VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE livre (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, nb_pages INT NOT NULL, date_edition DATE NOT NULL, nb_exemplaires INT NOT NULL, prix DOUBLE PRECISION NOT NULL, isbn BIGINT NOT NULL, editeur_id INT NOT NULL, category_id INT DEFAULT NULL, INDEX IDX_AC634F993375BD21 (editeur_id), INDEX IDX_AC634F9912469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE livre_auteur (livre_id INT NOT NULL, auteur_id INT NOT NULL, INDEX IDX_A11876B537D925CB (livre_id), INDEX IDX_A11876B560BB6FE6 (auteur_id), PRIMARY KEY (livre_id, auteur_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE personne (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(25) NOT NULL, prenom VARCHAR(25) NOT NULL, age INT NOT NULL, salaire DOUBLE PRECISION NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE livre ADD CONSTRAINT FK_AC634F993375BD21 FOREIGN KEY (editeur_id) REFERENCES editeur (id)');
        $this->addSql('ALTER TABLE livre ADD CONSTRAINT FK_AC634F9912469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE livre_auteur ADD CONSTRAINT FK_A11876B537D925CB FOREIGN KEY (livre_id) REFERENCES livre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE livre_auteur ADD CONSTRAINT FK_A11876B560BB6FE6 FOREIGN KEY (auteur_id) REFERENCES auteur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE livre DROP FOREIGN KEY FK_AC634F993375BD21');
        $this->addSql('ALTER TABLE livre DROP FOREIGN KEY FK_AC634F9912469DE2');
        $this->addSql('ALTER TABLE livre_auteur DROP FOREIGN KEY FK_A11876B537D925CB');
        $this->addSql('ALTER TABLE livre_auteur DROP FOREIGN KEY FK_A11876B560BB6FE6');
        $this->addSql('DROP TABLE auteur');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE editeur');
        $this->addSql('DROP TABLE livre');
        $this->addSql('DROP TABLE livre_auteur');
        $this->addSql('DROP TABLE personne');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
