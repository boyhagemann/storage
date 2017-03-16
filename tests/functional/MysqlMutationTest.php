<?php

use Boyhagemann\Storage\Exceptions\ResourceNotFound;
use Boyhagemann\Storage\Exceptions\ResourceWithVersionNotFound;
use Boyhagemann\Storage\Drivers\MysqlRecord;
use Boyhagemann\Storage\Drivers\MysqlEntity;

class MysqlMutationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Boyhagemann\Storage\Contracts\Record
     */
    protected $driver;

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

//    public function testInsertRecord()
//    {
//        $entity = $this->entityRepository->get('resource1');
//
//        $this->driver->insert($entity, [
//            '_id' => 'generated-unique-id',
//            'id' => 'id2',
//            'name' => 'second',
//            'label' => 'Second record'
//        ]);
//
//        $expected = [
//            [
//                '_id' => 'record1',
//                '_version' => 2,
//                'id' => 'id1',
//                'name' => 'test',
//                'label' => '456',
//            ],
//            [
//                '_id' => 'generated-unique-id',
//                '_version' => 1,
//                'id' => 'id2',
//                'name' => 'second',
//                'label' => 'Second record',
//            ],
//        ];
//
//        $this->assertSame($expected, $this->driver->find($entity, [], [
//            'order' => 'id',
//        ]));
//    }
//
//    public function testUpdateRecord()
//    {
//        $entity = $this->entityRepository->get('resource1');
//
//        $this->driver->update($entity, 'record1', [
//            'label' => 'Updated'
//        ]);
//
//        $expected = [
//            [
//                '_id' => 'record1',
//                '_version' => 3,
//                'id' => 'id1',
//                'name' => 'test',
//                'label' => 'Updated',
//            ],
//        ];
//
//        $this->assertSame($expected, $this->driver->find($entity, [], [
//            'order' => 'id',
//        ]));
//    }


    public function testUpdateRecordThrowsIfDataIsNotChanged()
    {
        $this->expectException(\Boyhagemann\Storage\Exceptions\RecordNotChanged::class);

        $entity = $this->entityRepository->get('resource1');

        $this->driver->update($entity, 'record1', [
            'label' => '456'
        ]);
    }

//    public function testUpsertWithExistingRecord()
//    {
//        $entity = $this->entityRepository->get('resource1');
//
//        $this->driver->upsert($entity, 'record1', [
//            'label' => 'Updated'
//        ]);
//
//        $expected = [
//            [
//                '_id' => 'record1',
//                '_version' => 3,
//                'id' => 'id1',
//                'name' => 'test',
//                'label' => 'Updated',
//            ],
//        ];
//
//        $this->assertSame($expected, $this->driver->find($entity, [], [
//            'order' => 'id',
//        ]));
//
//    }
//
//    public function testUpsertWithNewRecord()
//    {
//        $entity = $this->entityRepository->get('resource1');
//
//        $this->driver->upsert($entity, 'non-existing', [
//            '_id' => 'non-existing',
//            'id' => 'id1',
//            'name' => 'test',
//            'label' => 'Created'
//        ]);
//
//        $this->assertCount(2, $this->driver->find($entity, [], [
//            'order' => 'id',
//        ]));
//    }
//
//    public function testDeleteRecord()
//    {
//        $entity = $this->entityRepository->get('resource1');
//
//        $this->driver->delete($entity, 'record1');
//
//        $expected = [];
//
//        $this->assertSame($expected, $this->driver->find($entity, [], [
//            'order' => 'id',
//        ]));
//    }


}