<?php namespace Boyhagemann\Storage\Contracts;

interface Entity extends Arrayable
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
//
//    /**
//     * @return string
//     */
//    public function name();

    /**
     * @return Field[]
     */
    public function fields();

}
