<?php namespace Boyhagemann\Storage\Drivers;

use Boyhagemann\Storage\Contracts;
use Boyhagemann\Storage\Entity;
use Boyhagemann\Storage\Exceptions\Invalid;
use Boyhagemann\Storage\Exceptions\EntityNotFound;
use Boyhagemann\Storage\Exceptions\EntityWithVersionNotFound;
use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Databases\MySQL as Builder;
use PDO;
use Ramsey\Uuid\Uuid;

class MysqlEntity implements Contracts\EntityRepository, Contracts\Validatable
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
     * @var Contracts\FieldRepository
     */
    protected $fields;

    /**
     * MysqlEntity constructor.
     * @param PDO $pdo
     * @param Contracts\Validator $validator
     * @param Contracts\FieldRepository $fields
     */
    public function __construct(PDO $pdo, Contracts\Validator $validator, Contracts\FieldRepository $fields)
    {
        $this->pdo = $pdo;

        $builder = new Builder($pdo);

        $this->builder = $builder;

        $this->validator = $validator;

        $this->fields = $fields;
    }

    protected function buildFindQuery(Array $query = [], Array $options)
    {
        return $this->builder->select()
            ->from('_entity');
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
            ->from('_entity')
            ->where('id = ?', $id)
            ->orderBy('`version`', 'desc');

        if($version) {
            $q->where('`version` = ?', $version);
        }

        $entity = $q->fetchRow();

        if(!$entity) {

            $e = $version
                ? new EntityWithVersionNotFound(sprintf('Entity with id "%s" and version "%d" does not exist', $id, $version))
                : new EntityNotFound(sprintf('No entity found with id "%s"', $id));

            throw $e;
        }

        // Get the fields for this entity, to create a record
        $fields = $this->fields()->find([
            ['entity', '=', $id],
        ], compact('version'));



        return new Entity($entity['uuid'], $entity['id'], (int) $entity['version'], $fields);
    }

    /**
     * @return Contracts\FieldRepository
     */
    public function fields()
    {
        return $this->fields;
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
            ->into('_entity')
            ->addAll([
                'uuid' => Uuid::uuid4(),
                'id' => $id,
                'version' => 1,
            ])
            ->run();
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
