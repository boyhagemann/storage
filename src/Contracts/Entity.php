<?php namespace Boyhagemann\Storage\Contracts;

interface Entity
{
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
     * @return array
     */
    public function fields();

    /**
     * @return array
     */
    public function toArray();

}
