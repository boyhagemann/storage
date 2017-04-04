<?php namespace Boyhagemann\Storage;

class Record extends \ArrayObject implements Contracts\Record
{
    public function __construct(Array $data)
    {
        $this->exchangeArray($data);
    }

    public function uuid()
    {
        return $this->offsetGet('_uuid');
    }

    public function id()
    {
        return $this->offsetGet('_id');
    }

    public function version()
    {
        return $this->offsetGet('_version');
    }

    public function data()
    {
        return array_diff_key($this->getArrayCopy(), array_flip(['_uuid', '_id', '_version']));
    }

    public function toArray()
    {
        return $this->getArrayCopy();
    }


}