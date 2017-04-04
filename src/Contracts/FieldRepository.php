<?php namespace Boyhagemann\Storage\Contracts;

interface FieldRepository
{
    /**
     * @param $id
     * @param null $version
     * @return Field
     */
    public function get($id, $version = null);
    public function getVersions($id, Array $options = []);

    /**
     * @param array $query
     * @param array $options
     * @return Collection
     */
    public function find(Array $query = [], Array $options = []);

    /**
     * @param array $query
     * @param array $options
     * @return Field|null
     */
    public function first(Array $query = [], Array $options = []);

    public function create(Array $data);

    public function update($id, Array $data);
    public function updateWhere(Array $query, Array $data);

    public function delete($id);
    public function deleteWhere(Array $query);
}
