<?php namespace Boyhagemann\Storage\Drivers;

use Boyhagemann\Storage\Contracts;
use Boyhagemann\Storage\Exceptions\Invalid;
use Kir\MySQL\Builder\RunnableSelect;
use Particle\Validator\Validator;
use Kir\MySQL\Databases\MySQL as Builder;
use PDO;
use Ramsey\Uuid\Uuid;

class MysqlField implements Contracts\EntityRepository, Contracts\Validatable
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
        $q =  $this->builder->select()
            ->from('_field');

        foreach($query as $item) {
            $q->where(sprintf('%s %s ?', $item[0], $item[1]), $item[2]);
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
        return $this->buildFindQuery($query, $options)->fetchRows();
    }

    /**
     * @param array $query
     * @param array $options
     * @return array|null
     */
    public function first(Array $query = [], Array $options = [])
    {
        return $this->buildFindQuery($query, $options)->fetchRow() ?: null;
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

        return new Field($field['uuid'], (int) $field['version'], $field['id'], $fields);
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
        $this->getValidator()->validateCreate($data);

        // UUID is fixed and can come from the outside
        $id = isset($data['id']) ? $data['id'] : Uuid::uuid4();

        // Insert the new entity
        $this->builder->insert()
            ->into('_field')
            ->addAll([
                'uuid' => Uuid::uuid4(),
                'id' => $id,
                'entity' => $data['entity'],
                'name' => $data['name'],
                'type' => $data['type'],
                'version' => 1
            ])
            ->run();
    }

    public function validateUpdate($id, Array $data)
    {
        // TODO: Implement validateUpdate() method.
    }

    public function update($id, Array $data)
    {
        // TODO: Implement update() method.
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
