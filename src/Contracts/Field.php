<?php namespace Boyhagemann\Storage\Contracts;

interface Field extends Arrayable
{
    const TYPE_STRING = 'string';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';
    const TYPE_TEXT = 'text';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';

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

}
