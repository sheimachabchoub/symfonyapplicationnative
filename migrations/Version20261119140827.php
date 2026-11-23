<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261119140827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Bibliothèque : adhérents (user), emprunts, résumé et ISBN texte unique sur livre';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE emprunt (id INT AUTO_INCREMENT NOT NULL, date_emprunt DATE NOT NULL, date_retour_prevue DATE NOT NULL, date_retour_reelle DATE DEFAULT NULL, livre_id INT NOT NULL, adherent_id INT NOT NULL, INDEX idx_emprunt_retour (date_retour_reelle, date_retour_prevue), INDEX IDX_364071D737D925CB (livre_id), INDEX IDX_364071D725F06C53 (adherent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE emprunt ADD CONSTRAINT FK_364071D737D925CB FOREIGN KEY (livre_id) REFERENCES livre (id)');
        $this->addSql('ALTER TABLE emprunt ADD CONSTRAINT FK_364071D725F06C53 FOREIGN KEY (adherent_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE auteur CHANGE biographie biographie LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE category CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE livre ADD resume LONGTEXT DEFAULT NULL, CHANGE isbn isbn VARCHAR(20) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AC634F99CC1CF4E6 ON livre (isbn)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE emprunt DROP FOREIGN KEY FK_364071D737D925CB');
        $this->addSql('ALTER TABLE emprunt DROP FOREIGN KEY FK_364071D725F06C53');
        $this->addSql('DROP TABLE emprunt');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('ALTER TABLE auteur CHANGE biographie biographie LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE category CHANGE description description LONGTEXT NOT NULL');
        $this->addSql('DROP INDEX UNIQ_AC634F99CC1CF4E6 ON livre');
        $this->addSql('ALTER TABLE livre DROP resume, CHANGE isbn isbn BIGINT NOT NULL');
    }
}
