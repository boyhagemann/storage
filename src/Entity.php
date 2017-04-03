<?php namespace Boyhagemann\Storage;

class Entity extends \ArrayObject implements Contracts\Entity
{
    public function __construct($uuid, $id, $version, Array $fields = [])
    {
        $this->exchangeArray(compact('uuid', 'id', 'version', 'fields'));
    }

    public function uuid()
    {
        return $this->offsetGet('uuid');
    }

    public function id()
    {
        return $this->offsetGet('id');
    }

    public function version()
    {
        return $this->offsetGet('version');
    }

//    public function name()
//    {
//        return $this->offsetGet('name');
//    }

    public function fields()
    {
        return $this->offsetGet('fields');
    }

    public function toArray()
    {
        return $this->getArrayCopy();
    }

}