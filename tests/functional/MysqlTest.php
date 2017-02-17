<?php

use Boyhagemann\Storage\Exceptions\ResourceNotFound;
use Boyhagemann\Storage\Exceptions\ResourceWithVersionNotFound;
use Boyhagemann\Storage\Drivers\Mysql as MysqlDriver;

class MysqlTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Boyhagemann\Storage\Contracts\Record
     */
    protected $driver;

    public function setUp()
    {
        // Create a PDO connection
        $connection = sprintf('mysql:host=%s;dbname=%s;charset=utf8', $_ENV['MYSQL_HOST'], $_ENV['MYSQL_DATABASE']);
        $pdo = new PDO($connection, $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASS'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

//        $pdo = new PDO('sqlite::memory:');

        // Reset the state of the database
        $sql = file_get_contents(__DIR__ . '/../data/seed.sql');
        $pdo->exec($sql);

        // Create the driver
        $this->driver = new MysqlDriver($pdo);
    }

    public function testFindRecordsShouldThrowExceptionWhenProvidedWithNonExistingResource()
    {
        $this->expectException(ResourceNotFound::class);

        $this->driver->findRecords('non-existing-resource');
    }

    public function testFindRecordsShouldThrowExceptionWhenProvidedWithNonExistingResourceVersion()
    {
        $this->expectException(ResourceWithVersionNotFound::class);

        $this->driver->findRecords('resource1', 222);
    }

    public function testFindLatestRecords()
    {
        $result = $this->driver->findRecords('resource1', null, [], []);

        $this->assertSame([
            [
                'id' => 'id1',
                'name' => 'test',
                'label' => '456',
            ],
        ], $result);
    }

    public function testFindRecordsWithVersion()
    {
        $result = $this->driver->findRecords('resource1', null, [], [
            'version' => 1
        ]);

        $this->assertSame([
            [
                'id' => 'id1',
                'name' => 'test',
                'label' => '123',
            ],
            [
                'id' => 'id2',
                'name' => 'foo',
                'label' => 'bar',
            ],
        ], $result);
    }

    public function testFindRecordsWithVersionAndQuery()
    {
        $result = $this->driver->findRecords('resource1', null, [
            ['field2', '=', 'bar'],
        ], [
            'version' => 1
        ]);

        $this->assertSame([
            [
                'id' => 'id2',
                'name' => 'foo',
                'label' => 'bar',
            ],
        ], $result);
    }

    public function testFetchLatestRecord()
    {
        $result = $this->driver->firstRecord('resource1', null, [
            ['field3', '=', 'id1']
        ]);

        $this->assertSame([
            'id' => 'id1',
            'name' => 'test',
            'label' => '456',
        ], $result);
    }

    public function testFetchRecordWithVersion()
    {
        $result = $this->driver->firstRecord('resource1', 1, [
            ['field3', '=', 'id2']
        ], [
            'version' => 1
        ]);

        $this->assertSame([
            'id' => 'id2',
            'name' => 'foo',
            'label_old' => 'bar',
        ], $result);
    }

    public function testFetchDeletedRecordWithResourceVersion()
    {
        $result = $this->driver->firstRecord('resource1', null, [
            ['field3', '=', 'id2']
        ]);

        $this->assertSame(null, $result);
    }


}