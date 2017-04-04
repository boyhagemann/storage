<?php namespace Boyhagemann\Storage\Contracts;

interface Collection
{
    /**
     * @return array
     */
    public function toArray();

    /**
     * @return Arrayable[]
     */
    public function all();

    /**
     * @return int
     */
    public function count();
}