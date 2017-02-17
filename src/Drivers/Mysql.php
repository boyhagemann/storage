<?php namespace Boyhagemann\Storage\Drivers;

use Boyhagemann\Storage\Contracts;
use Boyhagemann\Storage\Exceptions\ResourceNotFound;
use Boyhagemann\Storage\Exceptions\ResourceWithVersionNotFound;
use Kir\MySQL\Builder\RunnableSelect;
use Kir\MySQL\Databases\MySQL as Builder;
use PDO;

class Mysql implements Contracts\Record
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

    /**
     * @param array $query
     * @param array $options
     * @return array
     */
    public function findResources(Array $query = [], Array $options = [])
    {
        return $this->builder->select()
            ->from('_resource')
            ->fetchRows();
    }

    /**
     * @param $id
     * @param $version
     * @return []
     */
    public function getResource($id, $version)
    {
        $resource = $this->builder->select()
            ->from('_resource')
            ->where('name = ?', $id)
            ->where('`version` = ?', $version)
            ->fetchRow();

        if(!$resource) {
            throw new ResourceWithVersionNotFound(sprintf('Resource with id "%s" and version "%d" does not exist', $id, $version));
        }

        return $resource;
    }

    /**
     * @param $id
     * @return []
     */
    public function getLatestResource($id)
    {
        $resource = $this->builder->select()
            ->from('_resource')
            ->where('name = ?', $id)
            ->orderBy('`version`', 'desc')
            ->limit(1)
            ->fetchRow();

        if(!$resource) {
            throw new ResourceNotFound(sprintf('No resource found with id "%s"', $id));
        }

        return $resource;
    }

    /**
     * @param array $query
     * @param array $options
     * @return array
     */
    public function findFields(Array $query = [], Array $options = [])
    {
        // Find the values with an optional version
        $dataVersion = isset($options['version']) ? $options['version'] : null;

        $q = $this->builder->select()
            ->field('`key`', '_id')
            ->field($this->buildFieldsForFieldTable('name', $dataVersion), 'name')
            ->field($this->buildFieldsForFieldTable('order', $dataVersion), 'order')
            ->from('f', '_field')
            ->groupBy('`key`')
            ->orderBy('`order`');

        foreach ($query as $statement) {
            $field = $this->buildFieldsForFieldTable($statement[0], $dataVersion);
            $q->where(sprintf('(%s) %s ?', (string) $field, $statement[1]), $statement[2]);
        }

        return $q;
    }

    /**
     * @param $resource
     * @param null $version
     * @param array $query
     * @param array $options
     * @return RunnableSelect
     * @throws \Exception
     */
    protected function buildFindQuery($resource, $version = null, Array $query = [], Array $options = [])
    {
        // Check if the resource with an optional version really exists
        $version
            ? $this->getResource($resource, $version)
            : $this->getLatestResource($resource);

        // Get the fields for this resource, to create a record
        $fields = $this->findFields([
            ['resource', '=', $resource],
        ], compact('version'));

        // Cannot continue if there are no fields
        if(!$fields) {
            throw new \Exception(sprintf('No fields found for resource "%s", version "%s"', $resource, $version));
        }

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
        foreach ($fields as $field) {
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
     * @param $resource
     * @param null $version
     * @param array $query
     * @param array $options
     * @return \array[]
     */
    public function findRecords($resource, $version = null, Array $query = [], Array $options = [])
    {
        return $this->buildFindQuery($resource, $version, $query, $options)->fetchRows();
    }

    /**
     * @param $resource
     * @param null $version
     * @param array $query
     * @param array $options
     * @return \mixed[]
     */
    public function firstRecord($resource, $version = null, Array $query = [], Array $options = [])
    {
        return $this->buildFindQuery($resource, $version, $query, $options)->fetchRow() ?: null;
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

    /**
     * @param string $name
     * @param int    $version
     * @return RunnableSelect
     */
    protected function buildFieldsForFieldTable($name, $version = null)
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
