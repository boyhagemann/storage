<?php namespace Boyhagemann\Storage\Drivers;

use Boyhagemann\Storage\Contracts;
use Boyhagemann\Storage\Contracts\Entity;
use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Databases\MySQL as Builder;
use Ramsey\Uuid\Uuid;
use PDO;

class MysqlRecord implements Contracts\Record
{
    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * MysqlRecord constructor.
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $builder = new Builder($pdo);

        $this->pdo = $pdo;
        $this->builder = $builder;
    }

    /**
     * @param Contracts\Entity $entity
     * @param array $query
     * @param array $options
     * @return RunnableSelect
     * @throws \Exception
     */
    protected function buildFindQuery(Contracts\Entity $entity, Array $query = [], Array $options = [])
    {
        // Find the values with an optional version
        $dataVersion = isset($options['version']) ? $options['version'] : null;

        // Build the record deleted where clause
        $whereDeleted = $this->builder->select()
            ->field('deleted')
            ->from('_record')
            ->where('`id` = r.`id`')
            ->orderBy('`version`', 'desc')
            ->limit(1);

        // Check if the record is deleted for this version
        if($dataVersion) {
            $whereDeleted->where('`version` <= ?', $dataVersion);
        }

        // Show the latest available version of the record
        $versionField = $this->builder->select()
            ->from('_record')
            ->field('`version`')
            ->where('`id` = r.`id`')
            ->orderBy('`version`', 'desc')
            ->limit(1);

        // Only fetch the records that are not deleted
        $q = $this->builder->select()
            ->from('r', '_record')
            ->field('id', '_id')
            ->field($versionField, '_version')
            ->where(sprintf('(%s) = ?', (string) $whereDeleted), 0)
            ->groupBy('r.`id`');

        // Build a subquery for every field
        foreach ($entity->fields() as $field) {
            $fieldQuery = $this->buildValueFieldQuery($field['id'], $dataVersion);
            $q->field($fieldQuery, $field['name']);
        }

        // Build the where statements
        foreach ($query as $statement) {

            switch($statement[0]) {

                case '_id':
                    $q->where(sprintf('`id` %s ?', $statement[1]), $statement[2]);
                    break;

                default:
                    $fieldQuery = $this->buildValueFieldQuery($statement[0], $dataVersion);
                    $q->where(sprintf('(%s) %s ?', (string) $fieldQuery, $statement[1]), $statement[2]);

            }
        }

        foreach($options as $key => $value) {

            switch($key) {

                case 'order':
                    $direction = isset($options['direction']) ? $options['direction'] : 'asc';
                    $q->orderBy($value, $direction);
                    break;

                case 'limit':
                    $q->limit($value);
                    break;

            }
        }

        return $q;
    }

    /**
     * @param Contracts\Entity $entity
     * @param array $query
     * @param array $options
     * @return \array[]
     */
    public function find(Contracts\Entity $entity, Array $query = [], Array $options = [])
    {
        $rows = $this->buildFindQuery($entity, $query, $options)->fetchRows();
        $formatted = array_map(function(Array $data) use ($entity) {
            return static::format($entity, $data);
        }, $rows);

        return $formatted;
    }

    /**
     * @param Contracts\Entity $entity
     * @param array $query
     * @param array $options
     * @return \mixed[]
     */
    public function first(Contracts\Entity $entity, Array $query = [], Array $options = [])
    {
        $data = $this->buildFindQuery($entity, $query, $options)->fetchRow();

        return $data ? static::format($entity, $data) : null;
    }

    /**
     * @param Entity $entity
     * @param array $data
     * @return array
     */
    public static function format(Contracts\Entity $entity, Array $data)
    {
        $formatted = [
            '_id' => $data['_id'],
            '_version' => (int) $data['_version'],
        ];

        foreach($entity->fields() as $field) {

            $name = $field['name'];
            $value = array_key_exists($name, $data) ? $data[$name] : ''; // @todo use a default from field

            switch($field['type']) {

                case 'string':
                    $value = (string) $value;
                    break;
            }

            $formatted[$name] = $value;
        }

        return $formatted;
    }


    /**
     * @param string $key
     * @param int    $version
     * @return RunnableSelect
     */
    protected function buildValueFieldQuery($key, $version = null)
    {
        $q =  $this->builder->select()
            ->field('value')
            ->from('_value')
            ->where('record = r.`id`')
            ->where('field = ?', $key)
            ->orderBy('`version`', 'desc')
            ->limit(1);

        if($version) {
            $q->where('`version` <= ?', $version);
        }

        return $q;
    }

    public function create(Contracts\Entity $entity, Array $data, Array $options = [])
    {
        // Validate the data

        // Uuid is unique in is for internal use only
        $id = Uuid::uuid4();

        // Id is fixed and can come from the outside
        $key = isset($data['_id']) ? $data['_id'] : Uuid::uuid4();

        // Add a record
        $this->builder->insert()
            ->add('uuid', $id)
            ->add('id', $key)
            ->add('resource', $entity->name())
            ->add('version', 1)
            ->add('deleted', 0)
            ->into('_record')
            ->run();

        // Add values for each field
        foreach($entity->fields() as $field) {

            $value = array_key_exists($field['name'], $data) ? $data[$field['name']] : null;

            $this->insertValue($key, $field['id'], 1, $value);
        }

    }

    public function upsert(Contracts\Entity $entity, Array $existing, Array $data, Array $options = [])
    {
        // TODO: Implement upsert() method.
    }

    public function update(Contracts\Entity $entity, $id, Array $data, Array $options = [])
    {
        // Validate the data

        // Uuid is unique in is for internal use only
        $uuid = Uuid::uuid4();

        // Get the incremented version
        $version = $this->getNextVersion($entity, $id);

        // Update the version of the record
        $this->insertRecord($id, $entity->name(), $version);

        // Key the fields by name, for easy lookup
        $fields = array_reduce($entity->fields(), function($current, $next) {
            $current[$next['name']] = $next;
            return $current;
        }, []);


        // Create an updated value for each data key
        foreach($data as $key => $value) {
            $this->insertValue($id, $fields[$key]['id'], $version, $value);
        }
    }

    public function updateWhere(Contracts\Entity $entity, Array $query, Array $data, Array $options = [])
    {
        // TODO: Implement updateWhere() method.
    }

    public function delete(Contracts\Entity $entity, $id, Array $options = [])
    {
        // Get the incremented version
        $version = $this->getNextVersion($entity, $id);

        // Update the version of the record
        $this->insertRecord($id, $entity->name(), $version, 1);
    }

    public function deleteWhere(Contracts\Entity $entity, Array $query, Array $options = [])
    {
        // TODO: Implement deleteWhere() method.
    }

    /**
     * @param Entity $entity
     * @param $id
     * @return mixed
     */
    protected function getNextVersion(Entity $entity, $id)
    {
        // Get the latest record to get the version number
        $latest = $this->first($entity, [
            ['_id', '=', $id]
        ]);

        return $latest['_version'] + 1;
    }

    /**
     * @param $record
     * @param $field
     * @param $version
     * @param $value
     */
    protected function insertValue($record, $field, $version, $value)
    {
        $this->builder->insert()
            ->add('uuid', Uuid::uuid4())
            ->add('record', $record)
            ->add('field', $field)
            ->add('version', $version)
            ->add('value', $value)
            ->into('_value')
            ->run();
    }

    /**
     * @param $id
     * @param $resource
     * @param $version
     */
    protected function insertRecord($id, $resource, $version, $deleted = 0)
    {
        // Uuid is unique in is for internal use only
        $uuid = Uuid::uuid4();

        // Update the version of the record
        $this->builder->insert()
            ->add('uuid', $uuid)
            ->add('id', $id)
            ->add('resource', $resource)
            ->add('version', $version)
            ->add('deleted', $deleted)
            ->into('_record')
            ->run();
    }


}
