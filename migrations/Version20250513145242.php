<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250513145242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создание таблиц для подписок и данных о погоде';
    }

    public function up(Schema $schema): void
    {
        // Создание таблицы subscriptions
        $this->addSql('CREATE TABLE subscriptions (
            id SERIAL NOT NULL, 
            email VARCHAR(255) NOT NULL, 
            city VARCHAR(255) NOT NULL, 
            frequency VARCHAR(10) NOT NULL, 
            confirmed BOOLEAN NOT NULL, 
            token VARCHAR(36) NOT NULL, 
            last_sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
            PRIMARY KEY(id)
        )');

        // Создание уникальных индексов
        $this->addSql('CREATE UNIQUE INDEX subscription_unique ON subscriptions (email, city)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4778A01C980D5C1 ON subscriptions (token)');

        // Создание таблицы weather_data (если она есть в Entity)
        $this->addSql('CREATE TABLE weather_data (
            id SERIAL NOT NULL, 
            city VARCHAR(255) NOT NULL, 
            temperature DOUBLE PRECISION NOT NULL, 
            humidity INT NOT NULL, 
            description VARCHAR(255) NOT NULL, 
            forecast_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
            PRIMARY KEY(id)
        )');

        // Индекс для ускорения поиска по городу
        $this->addSql('CREATE INDEX IDX_8C6CAF5C2D5B0234 ON weather_data (city)');

        /*// Таблица для подписок
        $this->addSql('CREATE TABLE subscriptions (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(255) NOT NULL,
            city VARCHAR(255) NOT NULL,
            frequency VARCHAR(10) NOT NULL,
            confirmed TINYINT(1) NOT NULL DEFAULT 0,
            token VARCHAR(36) NOT NULL,
            last_sent_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE INDEX subscription_unique (email, city),
            UNIQUE INDEX UNIQ_4778A01A5F37A13B (token),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Таблица для данных о погоде
        $this->addSql('CREATE TABLE weather_data (
            id INT AUTO_INCREMENT NOT NULL,
            city VARCHAR(255) NOT NULL,
            temperature NUMERIC(5, 2) NOT NULL,
            humidity INT NOT NULL,
            description VARCHAR(255) NOT NULL,
            forecast_time DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX city_idx (city),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');*/
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE subscriptions');
        $this->addSql('DROP TABLE weather_data');
    }
}
