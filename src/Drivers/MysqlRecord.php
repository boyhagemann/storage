<?php namespace Boyhagemann\Storage\Drivers;

use Boyhagemann\Storage\Contracts;
use Boyhagemann\Storage\Contracts\Entity;
use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Databases\MySQL as Builder;
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
            ->where('`key` = r.`key`')
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
            ->groupBy('r.`key`');

        // Build a subquery for every field
        foreach ($entity->fields() as $field) {
            $fieldQuery = $this->buildValueFieldQuery($field['_id'], $dataVersion);
            $q->field($fieldQuery, $field['name']);
        }

        // Build the where statements
        foreach ($query as $statement) {
            $fieldQuery = $this->buildValueFieldQuery($statement[0], $dataVersion);
            $q->where(sprintf('(%s) %s ?', (string) $fieldQuery, $statement[1]), $statement[2]);
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
        return $this->buildFindQuery($entity, $query, $options)->fetchRows();
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
     * @param string $key
     * @param int    $version
     * @return RunnableSelect
     */
    protected function buildValueFieldQuery($key, $version = null)
    {
        $q =  $this->builder->select()
            ->field('value')
            ->from('_value')
            ->where('record = r.`key`')
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
        // TODO: Implement create() method.
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
