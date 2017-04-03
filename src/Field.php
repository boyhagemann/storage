<?php namespace Boyhagemann\Storage;

class Field extends \ArrayObject implements Contracts\Field
{
    public function __construct(Array $data)
    {
        $this->exchangeArray($data);
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
        return (int) $this->offsetGet('version');
    }

    public function name()
    {
        return $this->offsetGet('name');
    }

    public function type()
    {
        return $this->offsetGet('type');
    }

    public function isRequired()
    {
        return $this->offsetGet('required') ? true : false;
    }

    public function isCollection()
    {
        return $this->offsetGet('collection') ? true : false;
    }

    public function toArray()
    {
        return $this->getArrayCopy();
    }


}