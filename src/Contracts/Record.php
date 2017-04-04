<?php namespace Boyhagemann\Storage\Contracts;

interface Record extends Arrayable 
{
    /**
     * @return string
     */
    public function uuid();

    /**
     * @return string
     */
    public function id();

    /**
     * @return int
     */
    public function version();

    /**
     * @return array
     */
    public function data();

}
