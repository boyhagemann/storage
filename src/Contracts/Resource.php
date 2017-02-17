<?php namespace Boyhagemann\Storage\Contracts;

interface Resource
{
    public function setDriver(Driver $driver);
    public function getDriver();

    public function find(Array $query = [], Array $options = []);
}