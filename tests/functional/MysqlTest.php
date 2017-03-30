<?php

use Boyhagemann\Storage\Exceptions\ResourceNotFound;
use Boyhagemann\Storage\Exceptions\ResourceWithVersionNotFound;
use Boyhagemann\Storage\Drivers\MysqlRecord;
use Boyhagemann\Storage\Drivers\MysqlEntity;

class MysqlTest extends AbstractTest
{
    public function init()
    {
        // Create a PDO connection
        $connection = sprintf('mysql:host=%s;dbname=%s;charset=utf8', $_ENV['MYSQL_HOST'], $_ENV['MYSQL_DATABASE']);
        $pdo = new PDO($connection, $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASS'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

//        $pdo = new PDO('sqlite::memory:');

        // Reset the state of the database
        $sql = file_get_contents(__DIR__ . '/../data/seed.sql');
        $pdo->exec($sql);

        // Create the driver
        $this->entityRepository = new MysqlEntity($pdo);
        $this->driver = new MysqlRecord($pdo);
    }

}
