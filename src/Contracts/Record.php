<?php namespace Boyhagemann\Storage\Contracts;

interface Record
{
    public function find(Entity $entity, Array $query = [], Array $options = []);
    public function first(Entity $entity, Array $query = [], Array $options = []);

    public function create(Entity $entity, Array $data, Array $options = []);
    public function upsert(Entity $entity, Array $existing, Array $data, Array $options = []);

    public function update(Entity $entity, $id, Array $data, Array $options = []);
    public function updateWhere(Entity $entity, Array $query, Array $data, Array $options = []);

    public function delete(Entity $entity, $id, Array $options = []);
    public function deleteWhere(Entity $entity, Array $query, Array $options = []);
}
