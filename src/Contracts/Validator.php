<?php namespace Boyhagemann\Storage\Contracts;

use Boyhagemann\Storage\Exceptions\Invalid;

interface Validator
{
    /**
     * @param array $data
     * @throws Invalid
     */
    public function validateCreate(Array $data);

    /**
     * @param string $id
     * @param array $data
     * @throws Invalid
     */
    public function validateUpdate($id, Array $data);
}