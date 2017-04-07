<?php

use Boyhagemann\Storage\Exceptions\EntityNotFound;
use Boyhagemann\Storage\Exceptions\EntityWithVersionNotFound;
use Boyhagemann\Storage\Drivers\MysqlRecord;
use Boyhagemann\Storage\Drivers\MysqlEntity;
use Boyhagemann\Storage\Contracts\FieldRepository;
use Boyhagemann\Storage\Exceptions\Invalid;

abstract class AbstractTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MysqlRecord
     */
    protected $driver;

    /**
     * @var MysqlEntity
     */
    protected $entities;

    /**
     * @var FieldRepository
     */
    protected $fields;

    abstract public function init();

    public function setUp()
    {
        $this->init();
    }

    public function testCreateEntityThrowsExceptionOnInvalidData()
    {
        $this->expectException(Invalid::class);
        $this->entities->create([]);
    }

    public function testCreateEntity()
    {
        $this->entities->create([
            'id' => 'my-entity',
        ]);

        $entity = $this->entities->get('my-entity');

        $this->assertSame($entity->id(), 'my-entity');
        $this->assertSame($entity->version(), 1);
    }

    public function testCreateField()
    {
        $this->fields->create([
            'entity' => 'my-entity',
            'id' => 'some-unique-id',
            'name' => 'my-field',
            'type' => 'string',
            'required' => true,
            'collection' => false,
        ]);

        $subset = [
            [
                'id' => 'some-unique-id',
                'name' => 'my-field',
                'entity' => 'my-entity',
                'order' => 0,
                'type' => 'string',
            ]
        ];

        $collection = $this->fields->find([
            ['entity', '=', 'my-entity'],
        ])->toArray();

        $this->assertArraySubset($subset, $collection);
        $this->assertCount(1, $collection);
        $this->assertResultHasExactKeys($collection[0], ['id', 'entity', 'name', 'order', 'type', 'required', 'collection']);
    }

    public function testGetNonExistingEntityThrowsException()
    {
        $this->expectException(EntityNotFound::class);
        $this->entities->get('non-existing-resource');
    }

    public function testGetNonExistingEntityVersionThrowsException()
    {
        $this->expectException(EntityWithVersionNotFound::class);
        $this->entities->get('resource1', 222);
    }

    public function testFindLatestRecords()
    {
        $entity = $this->entities->get('resource1');
        $collection = $this->driver->find($entity, [], [])->toArray();

        $subset = [
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
        ];

        $this->assertArraySubset($subset, $collection);
        $this->assertCount(1, $collection);
        $this->assertResultHasExactKeys($collection[0]);
    }

    public function testFindRecordsWithVersion()
    {
        $entity = $this->entities->get('resource1');
        $collection = $this->driver->find($entity, [], [
            'version' => 1
        ])->toArray();

        $subset = [
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
        ];

        $this->assertArraySubset($subset, $collection);
        $this->assertCount(2, $collection);
        $this->assertResultHasExactKeys($collection[0]);
    }

    /**
     * @param array $query
     * @param $count
     * @dataProvider queryProvider
     */
    public function testFindRecordsWithQuery(Array $query, $count)
    {
        $entity = $this->entities->get('resource1');
        $collection = $this->driver->find($entity, $query);

        $this->assertCount($count, $collection);
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
        $entity = $this->entities->get('resource1');
        $collection = $this->driver->find($entity, [
            'and' => [
                ['field2', '=', 'bar'],
            ],
        ], [
            'version' => 1
        ])->toArray();


        $subset = [
            [
                '_id' => 'record2',
                '_version' => 2,
                'id' => 'id2',
                'name' => 'foo',
                'label' => 'bar',
                'uses' => null,
            ],
        ];

        $this->assertArraySubset($subset, $collection);
        $this->assertCount(1, $collection);
        $this->assertResultHasExactKeys($collection[0]);
    }

    public function testFetchLatestRecord()
    {
        $entity = $this->entities->get('resource1');
        $record = $this->driver->first($entity, [
            'and' => [
                ['field3', '=', 'id1'],
            ],
        ]);

        $this->assertArraySubset([
            '_id' => 'record1',
            '_version' => 2,
            'id' => 'id1',
            'name' => 'test',
            'label' => '456',
            'uses' => [
                'first',
                'second',
            ],
        ], $record->toArray());

        $this->assertResultHasExactKeys($record->toArray());
    }

    public function testFetchRecordWithVersion()
    {
        $entity = $this->entities->get('resource1', 1);
        $record = $this->driver->first($entity, [
            'and' => [
                ['field3', '=', 'id2'],
            ]
        ], [
            'version' => 1
        ]);

        $this->assertArraySubset([
            '_id' => 'record2',
            '_version' => 2,
            'id' => 'id2',
            'name' => 'foo',
            'label_old' => 'bar',
            'uses' => null,
        ], $record->toArray());

    }

    public function testFetchDeletedRecordWithResourceVersion()
    {
        $entity = $this->entities->get('resource1');
        $record = $this->driver->first($entity, [
            'and' => [
                ['field3', '=', 'id2']
            ],
        ]);

        $this->assertSame(null, $record);
    }

    public function testFetchRecordWithCondition()
    {
        $entity = $this->entities->get('resource1');
        $record = $this->driver->first($entity, [
            'and' => [
                ['field3', '=', 'id1'] // id = 'id1' @todo needs a field column mapper
            ],
        ], [
            'conditions' => [
                'lang' => 'nl'
            ],
        ]);

        $this->assertArraySubset([
            '_id' => 'record1',
            '_version' => 3,
            'id' => 'id1',
            'name' => 'test',
            'label' => 'Nederlandse vertaling',
            'uses' => [
                'first',
                'second',
            ],
        ], $record->toArray());

        $this->assertResultHasExactKeys($record->toArray());
    }

    public function testGetRecord()
    {
        $entity = $this->entities->get('resource1');
        $record = $this->driver->get($entity, 'record1');

        $this->assertArraySubset([
            '_id' => 'record1',
            '_version' => 2,
            'id' => 'id1',
            'name' => 'test',
            'label' => '456',
            'uses' => [
                'first',
                'second',
            ],
        ], $record->toArray());

        $this->assertResultHasExactKeys($record->toArray());
    }

    public function testGetRecordThrowsExceptionWhenNotFound()
    {
        $this->expectException(\Boyhagemann\Storage\Exceptions\RecordNotFound::class);

        $entity = $this->entities->get('resource1');
        $this->driver->get($entity, 'not-existing');
    }

    public function testInsertRecord()
    {
        $entity = $this->entities->get('resource1');

        $this->driver->insert($entity, [
            '_id' => 'generated-unique-id',
            'id' => 'id2',
            'name' => 'second',
            'label' => 'Second record',
            'some_fake_field' => 'must be skipped',
        ]);

        $subset = [
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
                '_version' => 1,
                'id' => 'id2',
                'name' => 'second',
                'label' => 'Second record',
                'uses' => null,
            ],
        ];

        $collection = $this->driver->find($entity, [], [
            'order' => 'id',
        ])->toArray();

        $this->assertArraySubset($subset, $collection);
        $this->assertCount(2, $collection);
        $this->assertResultHasExactKeys($collection[0]);

    }

    public function testInsertRecordWithInvalidDataThrowsException()
    {
        $this->expectException(Invalid::class);

        $entity = $this->entities->get('resource1');

        $this->driver->insert($entity, []);
    }

    public function testUpdateRecord()
    {
        $entity = $this->entities->get('resource1');

        $this->driver->update($entity, 'record1', [
            'label' => 'Updated'
        ]);

        $subset = [
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

        $collection = $this->driver->find($entity, [], [
            'order' => 'id',
        ])->toArray();

        $this->assertArraySubset($subset, $collection);
        $this->assertCount(1, $collection);
        $this->assertResultHasExactKeys($collection[0]);
    }

    public function testUpdateRecordThrowsExceptionIfDataIsNotChanged()
    {
        $this->expectException(\Boyhagemann\Storage\Exceptions\RecordNotChanged::class);

        $entity = $this->entities->get('resource1');

        $this->driver->update($entity, 'record1', [
            'label' => '456'
        ]);
    }

    /**
     * @group test
     */
    public function testUpsertWithExistingRecord()
    {
        $entity = $this->entities->get('resource1');

        $this->driver->upsert($entity, 'record1', [
            'label' => 'Updated'
        ]);

        $subset = [
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

        $collection = $this->driver->find($entity, [], [
            'order' => 'id',
        ])->toArray();

        $this->assertArraySubset($subset, $collection);
        $this->assertCount(1, $collection);
        $this->assertResultHasExactKeys($collection[0]);
    }

    /**
     * @param array $data
     */
    protected function assertResultHasExactKeys(Array $data, Array $keys = ['_id', '_version', '_created_at', 'id', 'name', 'label', 'uses'])
    {
        $this->assertSame(array_keys($data), $keys);
    }

    public function testUpsertWithNewRecord()
    {
        $entity = $this->entities->get('resource1');

        $this->driver->upsert($entity, 'non-existing', [
            'id' => 'id1',
            'name' => 'test',
            'label' => 'Created'
        ]);

        $collection = $this->driver->find($entity, [], [
            'order' => 'id',
        ]);

        $this->assertCount(2, $collection);
    }

    public function testUpsertWithNewRecordWillOnlyInsertOnce()
    {
        $entity = $this->entities->get('resource1');

        $this->driver->upsert($entity, 'non-existing', [
            'id' => 'id1',
            'name' => 'test',
            'label' => 'Created'
        ]);

        $this->driver->upsert($entity, 'non-existing', [
            'id' => 'id1',
            'name' => 'updated-name-value',
            'label' => 'Created'
        ]);

        $collection = $this->driver->find($entity, [], [
            'order' => 'id',
        ]);

        $this->assertCount(2, $collection);
    }

    public function testDeleteRecord()
    {
        $entity = $this->entities->get('resource1');

        $this->driver->delete($entity, 'record1');

        $expected = [];
        $collection = $this->driver->find($entity, [], [
            'order' => 'id',
        ]);

        $this->assertInstanceOf(\Boyhagemann\Storage\Contracts\Collection::class, $collection);
        $this->assertSame($expected, $collection->toArray());
    }

}
