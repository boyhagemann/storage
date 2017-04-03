<?php namespace Boyhagemann\Storage\Drivers;

use Boyhagemann\Storage\Contracts;
use Boyhagemann\Storage\Contracts\Entity;
use Boyhagemann\Storage\Contracts\Validator;
use Boyhagemann\Storage\Exceptions\RecordNotChanged;
use Boyhagemann\Storage\Exceptions\RecordNotFound;
use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Databases\MySQL as Builder;
use Ramsey\Uuid\Uuid;
use PDO;

class MysqlRecord implements Contracts\Record, Contracts\Validatable
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
     * @var Contracts\Validator
     */
    protected $validator;

    /**
     * @var \Closure
     */
    protected $validatorCallback;

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
     * @param \Closure $callback
     */
    public function buildValidator(\Closure $callback)
    {
        $this->validatorCallback = $callback;
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

        // Do we need to handle one or more conditions?
        $conditions = isset($options['conditions']) ? $options['conditions'] : [];

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

        // Add the optional conditions to the query, only if we have any conditions
        $conditionalWhereDeleted = $this->wrapQueryInCondition($whereDeleted, $conditions);
        $conditionalVersionField = $this->wrapQueryInCondition($versionField, $conditions);

        // Only fetch the records that are not deleted
        $q = $this->builder->select()
            ->from('r', '_record')
            ->field('id', '_id')
            ->field($conditionalVersionField, '_version')
            ->where(sprintf('(%s) = ?', (string) $conditionalWhereDeleted), 0)
            ->groupBy('r.`id`');

        // Build a subquery for every field
        foreach ($entity->fields() as $field) {
            $fieldQuery = $this->buildValueFieldQuery($entity, $field['id'], $dataVersion, $conditions);
            $q->field($fieldQuery, $field['name']);
        }

        /**
         * @TODO make this nested
         */
        if(isset($query['and'])) {
            $where = $this->collectWhereStatements($entity, $query['and'], $dataVersion, $conditions);
            $this->applyAndWhere($q, $where);
        }

        if(isset($query['or'])) {
            $where = $this->collectWhereStatements($entity, $query['or'], $dataVersion, $conditions);
            $this->applyOrWhere($q, $where);
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

    protected function applyAndWhere(RunnableSelect $q, Array $where)
    {
        foreach($where as $statement) {
            $q->where($statement['path'], $statement['value']);
        }
    }

    protected function applyOrWhere(RunnableSelect $q, Array $where)
    {
        $whereString = implode(' OR ', array_map(function(Array $w) {
            return $w['path'];
        }, $where));
        $whereParams = array_map(function(Array $w) {
            return $w['value'];
        }, $where);
        array_unshift($whereParams, $whereString);

//        dd($whereParams);
        if($where) {
            call_user_func_array([$q, 'where'], $whereParams);
        }
    }

    protected function collectWhereStatements(Entity $entity, Array $query, $dataVersion, $conditions)
    {
        $where = [];

        // Build the where statements
        foreach ($query as $statement) {

            $key = $statement[0];
            $operator = $statement[1];
            $value = $statement[2];

            // Build the sql path based on the operator
            switch($operator) {

                case 'IN':
                    $path = '(%s) %s (?)';
                    break;

                default:
                    $path = '(%s) %s ?';
            }

            switch($key) {

                case '_id':
                    $where[] = [
                        'path' => sprintf('(%s) IN (?)', '`id`'),
                        'value' => (array) $statement[2],
                    ];
//                    dd($where);
                    break;

                default:

                    // Get the field ID to find the field type
                    $fieldId = strstr($key, '.')
                        ? substr($key, 0, strpos($key, '.'))
                        : $key;

                    // Find the field
                    $field = $this->findField($entity, $fieldId);

                    $fieldQuery = $this->buildValueFieldQuery($entity, $field['id'], $dataVersion, $conditions);

                    switch($field['type']) {

                        // Prepare the right value for the where statement
                        case 'json':
                            $jsonPath = strstr($key, '.') ? substr($key, strpos($key, '.') + 1) : null;
                            $path = $jsonPath
                                ? sprintf('JSON_CONTAINS(CAST((%s) AS JSON), ?, \'$.%s\')', '%s', $jsonPath)
                                : sprintf('JSON_CONTAINS(CAST((%s) AS JSON), ?)', '%s');
                            $value = json_encode($value);
                            break;
                    }

                    $where[] = [
                        'path' => sprintf($path, (string) $fieldQuery, $statement[1]),
                        'value' => $value,
                    ];
            }
        }

        return $where;
    }

    /**
     * @param Entity $entity
     * @param $id
     * @return array|null
     */
    protected function findField(Entity $entity, $id)
    {
        $found = array_values(array_filter($entity->fields(), function(Contracts\Field $field) use ($id) {
            return $field['id'] === $id;
        }));

        return $found ? $found[0] : null;
    }

    /**
     * Build a complex IFNULL query that gets row that matches the conditions or falls back
     * to the non-conditional row.
     *
     * @param RunnableSelect $query
     * @param array $conditions
     * @return RunnableSelect
     */
    protected function wrapQueryInCondition(RunnableSelect $query, Array $conditions)
    {
        // No conditions, just add a simple NULL conditions and return this
        if(!$conditions) {
            $query->where('conditions IS NULL');
            return $query;
        }

        // Start the conditional query with the same information of the original query
        $queryWithCondition = clone $query;

        // Build the matching condition query
        foreach($conditions as $key => $value) {
            $queryWithCondition->where(sprintf('JSON_CONTAINS(CAST(conditions AS JSON), \'%s\', \'$.%s\')', json_encode($value), $key));
        }

        // Now we have enough to build a complex IFNULL query to handle the conditions
        $q = $this->builder->select()
            ->field(sprintf('IFNULL((%s), (%s))', (string) $queryWithCondition, (string) $query->where('conditions IS NULL')))
            ->from('_value')
            ->limit(1);

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
     * @param array $query
     * @param array $options
     * @return array
     * @throws RecordNotFound
     */
    public function firstOrFail(Contracts\Entity $entity, Array $query = [], Array $options = [])
    {
        $item = $this->first($entity, $query, $options);

        if($item === null) {
            throw new RecordNotFound(sprintf('The item for entity "%s" and query "%s" is not found', $entity->id(), json_encode($query)));
        }

        return $item;
    }

    /**
     * @param Entity $entity
     * @param $id
     * @param array $options
     * @return array
     * @throws RecordNotFound
     */
    public function get(Entity $entity, $id, Array $options = [])
    {
        $record = static::first($entity, [
            'and' => [
                ['_id', '=', $id],
            ],
        ]);

        if(!$record) {
            throw new RecordNotFound(sprintf('The record with _id "%s" is not found', $id));
        }

        return $record;
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

                case 'json':
                    $value = json_decode($value, true);
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
    protected function buildValueFieldQuery(Entity $entity, $key, $version = null, Array $conditions = [])
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

    /**
     * @param Entity $entity
     * @param array $data
     * @param array $options
     * @return string
     */
    public function insert(Contracts\Entity $entity, Array $data, Array $options = [])
    {
        // Id is fixed and can come from the outside
        $id = isset($data['_id']) ? $data['_id'] : Uuid::uuid4();

        // Build the validator
        /** @var Contracts\Validator $validator */
        $validator = call_user_func($this->validatorCallback, $entity);

        // Validate the data
        $values = $validator->validateCreate($data);

        // Commit to the storage
        $this->store($entity, $id, $values);

        return $id;
    }

    /**
     * @param Entity $entity
     * @param $id
     * @param array $data
     * @param array $options
     * @return string|null
     **/
    public function upsert(Contracts\Entity $entity, $id, Array $data, Array $options = [])
    {
        try {
            return $this->update($entity, $id, $data, $options);
        }
        catch (RecordNotFound $e) {
            $data['_id'] = $id;
            return $this->insert($entity, $data, $options);
        }
    }

    /**
     * @param Entity $entity
     * @param $id
     * @param array $data
     * @param array $options
     */
    public function update(Contracts\Entity $entity, $id, Array $data, Array $options = [])
    {
        // Find the existing record or throw an exception
        $current = $this->get($entity, $id, $options);

        // Build the validator
        /** @var Contracts\Validator $validator */
        $validator = call_user_func($this->validatorCallback, $entity);


        // Validate the data
        $values = $validator->validateUpdate($id, $data);

        // Check if there are changes with the current data
        $this->checkChanges($current, $values);

        // We only need the changed values
        $changes = $this->getChanges($current, $values);

        // Commit to the storage
        $this->store($entity, $id, $changes);
    }

    /**
     * @param array $current
     * @param array $data
     * @throws RecordNotChanged
     */
    protected function checkChanges(Array $current, Array $data)
    {
        if(!$this->hasChanges($current, $data)) {
            throw new RecordNotChanged(sprintf('There provided data "%s" has no changes against "%s"',
                json_encode($data), json_encode($current)));
        }
    }

    /**
     * @param array $current
     * @param array $data
     * @return bool
     */
    public function hasChanges(Array $current, Array $data)
    {
        foreach ($data as $key => $value) {
            if(!array_key_exists($key, $current)) return true;
            if($current[$key] !== $value) return true;
        }

        return false;
    }

    /**
     * @param array $current
     * @param array $data
     * @return array
     */
    public function getChanges(Array $current, Array $data)
    {
        $values = array_intersect_key($current, $data);

        return array_diff($data, $values);
    }

    /**
     * @param Entity $entity
     * @param array $data
     * @return array
     */
    public function transform(Contracts\Entity $entity, Array $data)
    {
        // Key the fields by name, for easy lookup
        $fields = array_reduce($entity->fields(), function(Array $current, Contracts\Field $field) {
            $current[$field['name']] = $field;
            return $current;
        }, []);

        $transformed = [];
        foreach ($data as $key => $value) {
            $id = $fields[$key]['id'];
            $type = $fields[$key]['type'];

            switch($type) {

                case 'json':
                    $value = is_array($value) ? json_encode($value) : $value;
                    break;
            }

            $transformed[$id] = $value;
        }

        return $transformed;
    }

    /**
     * @param Entity $entity
     * @param string $id
     * @param array $data
     * @return array
     */
    protected function store(Contracts\Entity $entity, $id, Array $data)
    {
        // Get the incremented version
        $version = $this->getNextVersion($entity, $id);

        // Update the version of the record
        $this->insertRecord($id, $entity->id(), $version);

        // Transform the keys and values of the data for insertion
        $transformed = $this->transform($entity, $data);

        // Insert each data value
        foreach($transformed as $key => $value) {
            $this->insertValue($id, $key, $version, $value);
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
        $this->insertRecord($id, $entity->id(), $version, 1);
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
            'and' => [
                ['_id', '=', $id],
            ]
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
     * @param $entity
     * @param $version
     */
    protected function insertRecord($id, $entity, $version = 1, $deleted = 0)
    {
        // Update the version of the record
        $this->builder->insert()
            ->add('uuid', Uuid::uuid4())
            ->add('id', $id)
            ->add('entity', $entity)
            ->add('version', $version)
            ->add('deleted', $deleted)
            ->into('_record')
            ->run();
    }

    /**
     * @return Contracts\Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }


}
