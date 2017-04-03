<?php namespace Boyhagemann\Storage\Contracts;

use Boyhagemann\Storage\Exceptions\Invalid;

interface Validator
{
    /**
     * @param array $data
     * @throws Invalid
     * @return array
     */
    public function validateCreate(Array $data);

    /**
     * @param string $id
     * @param array $data
     * @throws Invalid
     * @return array
     */
    public function validateUpdate($id, Array $data);
}