<?php namespace Boyhagemann\Storage\Contracts;

interface Field
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
     * @return string
     */
    public function name();

    /**
     * @return string
     */
    public function type();

    /**
     * @return bool
     */
    public function isRequired();

    /**
     * @return bool
     */
    public function isCollection();

    /**
     * @return array
     */
    public function toArray();

}
