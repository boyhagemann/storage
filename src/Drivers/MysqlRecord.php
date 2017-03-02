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

        $conditions = isset($options['conditions']) ? $options['conditions'] : [];
//        $conditionWhere = $this->buildWhereForConditions($conditions);
//        $conditionValue = stripslashes(json_encode($conditions);


        // Build the record deleted where clause
        $whereDeleted = $this->builder->select()
            ->field('deleted')
            ->from('_record')
            ->where('`id` = r.`id`')
//            ->where($conditionWhere)
            ->orderBy('`version`', 'desc')
            ->limit(1);

        // Check if the record is deleted for this version
        if($dataVersion) {
            $whereDeleted->where('`version` <= ?', $dataVersion);
        }

        $conditionalWhereDeleted = $this->wrapQueryInCondition($whereDeleted, $conditions);

        // Show the latest available version of the record
        $versionField = $this->builder->select()
            ->from('_record')
            ->field('`version`')
            ->where('`id` = r.`id`')
            ->orderBy('`version`', 'desc')
            ->limit(1);

        $conditionalVersionField = $this->wrapQueryInCondition($versionField, $conditions);

        // Only fetch the records that are not deleted
        $q = $this->builder->select()
            ->from('r', '_record')
            ->field('id', '_id')
            ->field($conditionalVersionField, '_version')
            ->where(sprintf('(%s) = ?', (string) $conditionalWhereDeleted), 0)
//            ->where($conditionWhere)
            ->groupBy('r.`id`');

        // Build a subquery for every field
        foreach ($entity->fields() as $field) {
            $fieldQuery = $this->buildValueFieldQuery($field['id'], $dataVersion, $conditions);
            $q->field($fieldQuery, $field['name']);
        }

        // Build the where statements
        foreach ($query as $statement) {

            switch($statement[0]) {

                case '_id':
                    $q->where(sprintf('`id` %s ?', $statement[1]), $statement[2]);
                    break;

                default:
                    $fieldQuery = $this->buildValueFieldQuery($statement[0], $dataVersion, $conditions);
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

//        $q->debug();

        return $q;
    }

    /**
     * @param RunnableSelect $query
     * @param array $conditions
     * @return RunnableSelect
     */
    protected function wrapQueryInCondition(RunnableSelect $query, Array $conditions)
    {
        if(!$conditions) {
            $query->where('conditions IS NULL');
            return $query;
        }

        $queryWithCondition = clone $query;

        foreach($conditions as $key => $value) {
            $queryWithCondition->where(sprintf('JSON_CONTAINS(CAST(conditions AS JSON), \'%s\', \'$.%s\')', $value, $key));
        }

        $q = $this->builder->select()
            ->field(sprintf('IFNULL((%s), (%s))', (string) $queryWithCondition, (string) $query->where('conditions IS NULL')))
            ->from('_value')
            ->limit(1);

        return $q;
    }

    /**
     * @param array $conditions
     * @return string
     */
    protected function buildWhereForConditions(Array $conditions) {

        return sprintf('conditions = \'%s\' OR conditions IS NULL', json_encode($conditions));
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
     * @param array $conditions
     * @return RunnableSelect
     */
    protected function buildValueFieldQuery($key, $version = null, Array $conditions = [])
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

        return $this->wrapQueryInCondition($q, $conditions);
    }

    public function insert(Contracts\Entity $entity, Array $data, Array $options = [])
    {
        // Validate the data

        // Id is fixed and can come from the outside
        $id = isset($data['_id']) ? $data['_id'] : Uuid::uuid4();

        // Add a record
        $this->insertRecord($id, $entity->name());

        // Add values for each field
        foreach($entity->fields() as $field) {

            $value = array_key_exists($field['name'], $data) ? $data[$field['name']] : null;

            $this->insertValue($id, $field['id'], 1, $value);
        }

    }

    public function upsert(Contracts\Entity $entity, Array $existing, Array $data, Array $options = [])
    {
        // TODO: Implement upsert() method.
    }

    public function update(Contracts\Entity $entity, $id, Array $data, Array $options = [])
    {
        // Validate the data

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
    protected function insertRecord($id, $resource, $version = 1, $deleted = 0)
    {
        // Update the version of the record
        $this->builder->insert()
            ->add('uuid', Uuid::uuid4())
            ->add('id', $id)
            ->add('resource', $resource)
            ->add('version', $version)
            ->add('deleted', $deleted)
            ->into('_record')
            ->run();
    }


}
