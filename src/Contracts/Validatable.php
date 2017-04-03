<?php namespace Boyhagemann\Storage\Contracts;

interface Validatable
{
    /**
     * @return Validator
     */
    public function getValidator();
}