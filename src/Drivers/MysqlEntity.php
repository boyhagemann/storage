<?php namespace Boyhagemann\Storage\Drivers;

use Boyhagemann\Storage\Contracts;
use Boyhagemann\Storage\Entity;
use Boyhagemann\Storage\Exceptions\ResourceNotFound;
use Boyhagemann\Storage\Exceptions\ResourceWithVersionNotFound;
use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Databases\MySQL as Builder;
use PDO;

class MysqlEntity implements Contracts\EntityRepository
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
     * Mysql constructor.
     * @param $host
     * @param $database
     * @param $username
     * @param $password
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $builder = new Builder($pdo);

        $this->builder = $builder;
    }

    protected function buildFindQuery(Array $query = [], Array $options)
    {
        return $this->builder->select()
            ->from('_resource');
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
            ->from('_resource')
            ->where('name = ?', $id)
            ->orderBy('`version`', 'desc');

        if($version) {
            $q->where('`version` = ?', $version);
        }

        $resource = $q->fetchRow();

        if(!$resource) {

            $e = $version
                ? new ResourceWithVersionNotFound(sprintf('Resource with id "%s" and version "%d" does not exist', $id, $version))
                : new ResourceNotFound(sprintf('No resource found with id "%s"', $id));

            throw $e;
        }

        // Get the fields for this resource, to create a record
        $fields = $this->findFields([
            ['resource', '=', $id],
        ], compact('version'));

        // Cannot continue if there are no fields
        if(!$fields) {
            throw new \Exception(sprintf('No fields found for resource "%s", version "%s"', $resource, $version));
        }

        return new Entity($resource['_id'], $resource['version'], $resource['name'], $fields);
    }

    /**
     * @param array $query
     * @param array $options
     * @return array
     */
    public function findFields(Array $query = [], Array $options = [])
    {
        // Find the values with an optional version
        $version = isset($options['version']) ? $options['version'] : null;

        $q = $this->builder->select()
            ->field('`key`', '_id')
            ->field($this->buildFieldSelectQuery('name', $version), 'name')
            ->field($this->buildFieldSelectQuery('order', $version), 'order')
            ->from('f', '_field')
            ->groupBy('`key`')
            ->orderBy('`order`');

        foreach ($query as $statement) {
            $field = $this->buildFieldSelectQuery($statement[0], $version);
            $q->where(sprintf('(%s) %s ?', (string) $field, $statement[1]), $statement[2]);
        }

        return $q->fetchRows();
    }

    public function getVersions($id, Array $options = [])
    {
        // TODO: Implement getVersions() method.
    }

    public function create(Array $data)
    {
        // TODO: Implement create() method.
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
     * @param string $name
     * @param int    $version
     * @return RunnableSelect
     */
    protected function buildFieldSelectQuery($name, $version = null)
    {
        $q = $this->builder->select()
            ->field(sprintf('`%s`', $name))
            ->from('_field')
            ->where('`key` = f.`key`')
            ->orderBy('`version`', 'desc')
            ->limit(1);

        if($version) {
            $q->where('`version` <= ?', $version);
        }

        return $q;
    }


}
