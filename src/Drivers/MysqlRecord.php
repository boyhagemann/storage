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

        // Only fetch the records that are not deleted
        $q = $this->builder->select()
            ->from('r', '_record')
            ->where(sprintf('(%s) = ?', (string) $whereDeleted), 0)
            ->groupBy('r.`id`');

        // Build a subquery for every field
        foreach ($entity->fields() as $field) {
            $fieldQuery = $this->buildValueFieldQuery($field['id'], $dataVersion);
            $q->field($fieldQuery, $field['name']);
        }

        // Build the where statements
        foreach ($query as $statement) {
            $fieldQuery = $this->buildValueFieldQuery($statement[0], $dataVersion);
            $q->where(sprintf('(%s) %s ?', (string) $fieldQuery, $statement[1]), $statement[2]);
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
        $formatted = array_map(function(Array $row) use ($entity) {
            return static::format($entity, $row);
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
        return $this->buildFindQuery($entity, $query, $options)->fetchRow() ?: null;
    }

    /**
     * @param Entity $entity
     * @param array $data
     * @return array
     */
    public static function format(Contracts\Entity $entity, Array $data)
    {
        $formatted = [];

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
        $id = Uuid::uuid4();
        $key = Uuid::uuid4();

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

            switch($field['type']) {

                case 'string':
                    $value = (string) $value;
                    break;
            }

            $this->builder->insert()
                ->add('uuid', Uuid::uuid4())
                ->add('record', $key)
                ->add('field', $field['id'])
                ->add('version', 1)
                ->add('value', $value)
                ->into('_value')
                ->run();
        }

    }

    public function upsert(Contracts\Entity $entity, Array $existing, Array $data, Array $options = [])
    {
        // TODO: Implement upsert() method.
    }

    public function update(Contracts\Entity $entity, $id, Array $data, Array $options = [])
    {
        // TODO: Implement update() method.
    }

    public function updateWhere(Contracts\Entity $entity, Array $query, Array $data, Array $options = [])
    {
        // TODO: Implement updateWhere() method.
    }

    public function delete(Contracts\Entity $entity, $id, Array $options = [])
    {
        // TODO: Implement delete() method.
    }

    public function deleteWhere(Contracts\Entity $entity, Array $query, Array $options = [])
    {
        // TODO: Implement deleteWhere() method.
    }


}
