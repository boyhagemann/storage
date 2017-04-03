<?php namespace Boyhagemann\Storage\Drivers;

use Boyhagemann\Storage\Contracts;
use Boyhagemann\Storage\Exceptions\Invalid;
use Boyhagemann\Storage\Field;
use Kir\MySQL\Builder\RunnableSelect;
use Particle\Validator\Validator;
use Kir\MySQL\Databases\MySQL as Builder;
use PDO;
use Ramsey\Uuid\Uuid;

class MysqlField implements Contracts\FieldRepository, Contracts\Validatable
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
     * @var Validator
     */
    protected $validator;

    /**
     * MysqlField constructor.
     * @param PDO $pdo
     * @param Contracts\Validator $validator
     */
    public function __construct(PDO $pdo, Contracts\Validator $validator)
    {
        $this->pdo = $pdo;

        $builder = new Builder($pdo);

        $this->builder = $builder;

        $this->validator = $validator;
    }

    /**
     * @param array $query
     * @param array $options
     * @return RunnableSelect
     */
    protected function buildFindQuery(Array $query = [], Array $options)
    {
        // Find the values with an optional version
        $version = isset($options['version']) ? $options['version'] : null;

        $q = $this->builder->select()
            ->field('`id`')
            ->field($this->buildFieldSelectQuery('entity', $version), 'entity')
            ->field($this->buildFieldSelectQuery('name', $version), 'name')
            ->field($this->buildFieldSelectQuery('order', $version), 'order')
            ->field($this->buildFieldSelectQuery('type', $version), 'type')
            ->field($this->buildFieldSelectQuery('required', $version), 'required')
            ->field($this->buildFieldSelectQuery('collection', $version), 'collection')
            ->from('f', '_field')
            ->groupBy('`id`')
            ->orderBy('`order`');

        foreach ($query as $statement) {
            $field = $this->buildFieldSelectQuery($statement[0], $version);
            $q->where(sprintf('(%s) %s ?', (string) $field, $statement[1]), $statement[2]);
        }

        return $q;
    }

    /**
     * @param string $name
     * @param int    $version
     * @return RunnableSelect
     */
    protected function buildFieldSelectQuery($name, $version = null)
    {
        $q = $this->builder->select()
            ->field(sprintf('`%s`', $name))
            ->from('_field')
            ->where('`id` = f.`id`')
            ->orderBy('`version`', 'desc')
            ->limit(1);

        if($version) {
            $q->where('`version` <= ?', $version);
        }

        return $q;
    }

    /**
     * @param array $query
     * @param array $options
     * @return array
     */
    public function find(Array $query = [], Array $options = [])
    {
        $rows = $this->buildFindQuery($query, $options)->fetchRows();

        return array_map([$this, 'wrap'], $rows);
    }

    /**
     * @param array $query
     * @param array $options
     * @return array|null
     */
    public function first(Array $query = [], Array $options = [])
    {
        $row = $this->buildFindQuery($query, $options)->fetchRow();

        return $row ? $this->wrap($row) : null;
    }

    /**
     * @param $id
     * @param $version
     * @return []
     */
    public function get($id, $version = null)
    {
        $q = $this->builder->select()
            ->from('_field')
            ->where('id = ?', $id)
            ->orderBy('`version`', 'desc');

        if($version) {
            $q->where('`version` = ?', $version);
        }

        $field = $q->fetchRow();

        if(!$field) {

            $e = $version
                ? new FieldWithVersionNotFound(sprintf('Field with id "%s" and version "%d" does not exist', $id, $version))
                : new FieldNotFound(sprintf('No field found with id "%s"', $id));

            throw $e;
        }

        return new Field($field);
    }

    /**
     * @param array $data
     * @return Field
     */
    protected function wrap(Array $data)
    {
        return new Field($data);
    }

    public function getVersions($id, Array $options = [])
    {
        // TODO: Implement getVersions() method.
    }

    /**
     * @param array $data
     * @throws Invalid
     */
    public function create(Array $data)
    {
        // Validate the data first
        $values = $this->getValidator()->validateCreate($data);

        // UUID is fixed and can come from the outside
        $id = isset($data['id']) ? $data['id'] : Uuid::uuid4();

        // Transform the values to be compatible with mysql
        $mappedValues = array_map(function($value) {
            return $this->prepareValueForInsertion($value);
        }, $values);

        // Insert the new entity
        $this->builder->insert()
            ->into('_field')
            ->addAll($mappedValues + [
                'uuid' => Uuid::uuid4(),
                'id' => $id,
                'version' => 1
            ])
            ->run();
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function prepareValueForInsertion($value)
    {
        switch(gettype($value)) {

            case 'boolean':
                return (int) $value;
        }

        return $value;
    }

    public function update($id, Array $data)
    {
        // Validate the data first
        $values = $this->getValidator()->validateUpdate($id, $data);

        // Transform the values to be compatible with mysql
        $mappedValues = array_map(function($value) {
            return $this->prepareValueForInsertion($value);
        }, $values);
    }

    public function updateWhere(Array $query, Array $data)
    {
        // TODO: Implement updateWhere() method.
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
    }

    public function deleteWhere(Array $query)
    {
        // TODO: Implement deleteWhere() method.
    }

    /**
     * @return Contracts\Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }


}
