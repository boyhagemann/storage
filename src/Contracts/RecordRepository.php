<?php namespace Boyhagemann\Storage\Contracts;

interface RecordRepository
{
    /**
     * @param Entity $entity
     * @param array $query
     * @param array $options
     * @return Collection
     */
    public function find(Entity $entity, Array $query = [], Array $options = []);

    /**
     * @param Entity $entity
     * @param array $query
     * @param array $options
     * @return Record|null
     */
    public function first(Entity $entity, Array $query = [], Array $options = []);

    /**
     * @param Entity $entity
     * @param $id
     * @param array $options
     * @return Record
     */
    public function get(Entity $entity, $id, Array $options = []);

    public function insert(Entity $entity, Array $data, Array $options = []);
    public function upsert(Entity $entity, $id, Array $data, Array $options = []);

    public function update(Entity $entity, $id, Array $data, Array $options = []);
    public function updateWhere(Entity $entity, Array $query, Array $data, Array $options = []);

    public function delete(Entity $entity, $id, Array $options = []);
    public function deleteWhere(Entity $entity, Array $query, Array $options = []);
}
