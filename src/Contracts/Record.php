<?php namespace Boyhagemann\Storage\Contracts;

interface Record
{
    /**
     * @param $resource
     * @param $version
     * @param array $query
     * @param array $options
     * @return array
     */
    public function findRecords($resource, $version = null, Array $query = [], Array $options = []);
}