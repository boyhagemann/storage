<?php

use Boyhagemann\Storage\Exceptions\ResourceNotFound;
use Boyhagemann\Storage\Exceptions\ResourceWithVersionNotFound;
use Boyhagemann\Storage\Drivers\MysqlRecord;
use Boyhagemann\Storage\Drivers\MysqlEntity;

class MysqlQueryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Boyhagemann\Storage\Contracts\Record
     */
    protected $driver;

    /**
     * @var MysqlEntity
     */
    protected $entityRepository;

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
        $this->entityRepository = new MysqlEntity($pdo);
        $this->driver = new MysqlRecord($pdo);
    }

    public function testGetNonExistingEntityThrowsException()
    {
        $this->expectException(ResourceNotFound::class);

        $this->entityRepository->get('non-existing-resource');
    }

    public function testGetNonExistingEntityVersionThrowsException()
    {
        $this->expectException(ResourceWithVersionNotFound::class);

        $this->entityRepository->get('resource1', 222);
    }

    public function testFindLatestRecords()
    {
        $entity = $this->entityRepository->get('resource1');
        $result = $this->driver->find($entity, [], []);

        $this->assertSame([
            [
                '_id' => 'record1',
                '_version' => 2,
                'id' => 'id1',
                'name' => 'test',
                'label' => '456',
            ],
        ], $result);
    }

    public function testFindRecordsWithVersion()
    {
        $entity = $this->entityRepository->get('resource1');
        $result = $this->driver->find($entity, [], [
            'version' => 1
        ]);

        $this->assertSame([
            [
                '_id' => 'record1',
                '_version' => 2,
                'id' => 'id1',
                'name' => 'test',
                'label' => '123',
            ],
            [
                '_id' => 'record2',
                '_version' => 2,
                'id' => 'id2',
                'name' => 'foo',
                'label' => 'bar',
            ],
        ], $result);
    }

    /**
     * @param array $query
     * @param $count
     * @dataProvider queryProvider
     */
    public function testFindRecordsWithQuery(Array $query, $count)
    {
        $entity = $this->entityRepository->get('resource1');
        $result = $this->driver->find($entity, $query);

        $this->assertCount($count, $result);
    }

    public function queryProvider() {
        return [
            [
                [
                    ['field2', 'IN', '456'],
                ],
                1
            ],
            [
                [
                    ['field2', 'IN', ['456']],
                ],
                1
            ],
        ];
    }

    public function testFindRecordsWithVersionAndQuery()
    {
        $entity = $this->entityRepository->get('resource1');
        $result = $this->driver->find($entity, [
            ['field2', '=', 'bar'],
        ], [
            'version' => 1
        ]);

        $this->assertSame([
            [
                '_id' => 'record2',
                '_version' => 2,
                'id' => 'id2',
                'name' => 'foo',
                'label' => 'bar',
            ],
        ], $result);
    }

    public function testFetchLatestRecord()
    {
        $entity = $this->entityRepository->get('resource1');
        $result = $this->driver->first($entity, [
            ['field3', '=', 'id1']
        ]);

        $this->assertSame([
            '_id' => 'record1',
            '_version' => 2,
            'id' => 'id1',
            'name' => 'test',
            'label' => '456',
        ], $result);
    }

    public function testFetchRecordWithVersion()
    {
        $entity = $this->entityRepository->get('resource1', 1);
        $result = $this->driver->first($entity, [
            ['field3', '=', 'id2']
        ], [
            'version' => 1
        ]);

        $this->assertSame([
            '_id' => 'record2',
            '_version' => 2,
            'id' => 'id2',
            'name' => 'foo',
            'label_old' => 'bar',
        ], $result);
    }

    public function testFetchDeletedRecordWithResourceVersion()
    {
        $entity = $this->entityRepository->get('resource1');
        $result = $this->driver->first($entity, [
            ['field3', '=', 'id2']
        ]);

        $this->assertSame(null, $result);
    }

    public function testFetchRecordWithCondition()
    {
        $entity = $this->entityRepository->get('resource1');
        $result = $this->driver->first($entity, [
            ['field3', '=', 'id1'] // id = 'id1' @todo needs a field column mapper
        ], [
            'conditions' => [
                'lang' => 'nl'
            ],
        ]);

        $this->assertSame([
            '_id' => 'record1',
            '_version' => 3,
            'id' => 'id1',
            'name' => 'test',
            'label' => 'Nederlandse vertaling',
        ], $result);
    }

    public function testGetRecord()
    {
        $entity = $this->entityRepository->get('resource1');
        $result = $this->driver->get($entity, 'record1');

        $this->assertSame([
            '_id' => 'record1',
            '_version' => 2,
            'id' => 'id1',
            'name' => 'test',
            'label' => '456',
        ], $result);
    }

    public function testGetRecordThrowsExceptionWhenNotFound()
    {
        $this->expectException(\Boyhagemann\Storage\Exceptions\RecordNotFound::class);

        $entity = $this->entityRepository->get('resource1');
        $this->driver->get($entity, 'not-existing');
    }

}
