<?php

use Boyhagemann\Storage\Exceptions\ResourceNotFound;
use Boyhagemann\Storage\Exceptions\ResourceWithVersionNotFound;
use Boyhagemann\Storage\Drivers\MysqlRecord;
use Boyhagemann\Storage\Drivers\MysqlEntity;

abstract class AbstractTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Boyhagemann\Storage\Contracts\Record
     */
    protected $driver;

    /**
     * @var MysqlEntity
     */
    protected $entityRepository;

    abstract public function init();

    public function setUp()
    {
        $this->init();
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
                'uses' => [
                    'first',
                    'second',
                ],
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
                'uses' => [
                    'first',
                    'second',
                ],
            ],
            [
                '_id' => 'record2',
                '_version' => 2,
                'id' => 'id2',
                'name' => 'foo',
                'label' => 'bar',
                'uses' => null,
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
            'and' => [
                ['field2', '=', 'bar'],
            ],
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
                'uses' => null,
            ],
        ], $result);
    }

    public function testFetchLatestRecord()
    {
        $entity = $this->entityRepository->get('resource1');
        $result = $this->driver->first($entity, [
            'and' => [
                ['field3', '=', 'id1'],
            ],
        ]);

        $this->assertSame([
            '_id' => 'record1',
            '_version' => 2,
            'id' => 'id1',
            'name' => 'test',
            'label' => '456',
            'uses' => [
                'first',
                'second',
            ],
        ], $result);
    }

    public function testFetchRecordWithVersion()
    {
        $entity = $this->entityRepository->get('resource1', 1);
        $result = $this->driver->first($entity, [
            'and' => [
                ['field3', '=', 'id2'],
            ]
        ], [
            'version' => 1
        ]);

        $this->assertSame([
            '_id' => 'record2',
            '_version' => 2,
            'id' => 'id2',
            'name' => 'foo',
            'label_old' => 'bar',
            'uses' => null,
        ], $result);
    }

    public function testFetchDeletedRecordWithResourceVersion()
    {
        $entity = $this->entityRepository->get('resource1');
        $result = $this->driver->first($entity, [
            'and' => [
                ['field3', '=', 'id2']
            ],
        ]);

        $this->assertSame(null, $result);
    }

    public function testFetchRecordWithCondition()
    {
        $entity = $this->entityRepository->get('resource1');
        $result = $this->driver->first($entity, [
            'and' => [
                ['field3', '=', 'id1'] // id = 'id1' @todo needs a field column mapper
            ],
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
            'uses' => [
                'first',
                'second',
            ],
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
            'uses' => [
                'first',
                'second',
            ],
        ], $result);
    }

    public function testGetRecordThrowsExceptionWhenNotFound()
    {
        $this->expectException(\Boyhagemann\Storage\Exceptions\RecordNotFound::class);

        $entity = $this->entityRepository->get('resource1');
        $this->driver->get($entity, 'not-existing');
    }

    public function testInsertRecord()
    {
        $entity = $this->entityRepository->get('resource1');

        $this->driver->insert($entity, [
            '_id' => 'generated-unique-id',
            'id' => 'id2',
            'name' => 'second',
            'label' => 'Second record'
        ]);

        $expected = [
            [
                '_id' => 'record1',
                '_version' => 2,
                'id' => 'id1',
                'name' => 'test',
                'label' => '456',
                'uses' => [
                    'first',
                    'second',
                ],
            ],
            [
                '_id' => 'generated-unique-id',
                '_version' => 3,
                'id' => 'id2',
                'name' => 'second',
                'label' => 'Second record',
                'uses' => null,
            ],
        ];

        $this->assertSame($expected, $this->driver->find($entity, [], [
            'order' => 'id',
        ]));
    }

    public function testUpdateRecord()
    {
        $entity = $this->entityRepository->get('resource1');

        $this->driver->update($entity, 'record1', [
            'label' => 'Updated'
        ]);

        $expected = [
            [
                '_id' => 'record1',
                '_version' => 3,
                'id' => 'id1',
                'name' => 'test',
                'label' => 'Updated',
                'uses' => [
                    'first',
                    'second',
                ],
            ],
        ];

        $this->assertSame($expected, $this->driver->find($entity, [], [
            'order' => 'id',
        ]));
    }


    public function testUpdateRecordThrowsIfDataIsNotChanged()
    {
        $this->expectException(\Boyhagemann\Storage\Exceptions\RecordNotChanged::class);

        $entity = $this->entityRepository->get('resource1');

        $this->driver->update($entity, 'record1', [
            'label' => '456'
        ]);
    }

    public function testUpsertWithExistingRecord()
    {
        $entity = $this->entityRepository->get('resource1');

        $this->driver->upsert($entity, 'record1', [
            'label' => 'Updated'
        ]);

        $expected = [
            [
                '_id' => 'record1',
                '_version' => 3,
                'id' => 'id1',
                'name' => 'test',
                'label' => 'Updated',
                'uses' => [
                    'first',
                    'second',
                ],
            ],
        ];

        $this->assertSame($expected, $this->driver->find($entity, [], [
            'order' => 'id',
        ]));

    }

    public function testUpsertWithNewRecord()
    {
        $entity = $this->entityRepository->get('resource1');

        $this->driver->upsert($entity, 'non-existing', [
            '_id' => 'non-existing',
            'id' => 'id1',
            'name' => 'test',
            'label' => 'Created'
        ]);

        $this->assertCount(2, $this->driver->find($entity, [], [
            'order' => 'id',
        ]));
    }

    public function testDeleteRecord()
    {
        $entity = $this->entityRepository->get('resource1');

        $this->driver->delete($entity, 'record1');

        $expected = [];

        $this->assertSame($expected, $this->driver->find($entity, [], [
            'order' => 'id',
        ]));
    }

}