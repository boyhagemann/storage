<?php namespace Boyhagemann\Storage\Contracts;

interface Driver
{
    // Resource
    public function findResources(Array $query = [], Array $options = []);
    public function getResource($id, $version);
    public function getLatestResource($id);

    // Field
    public function findFields(Array $query = [], Array $options = []);

    // Data
    public function findRecords($resource, $version = null, Array $query = [], Array $options = []);
    public function firstRecord($resource, $version = null, Array $query = [], Array $options = []);
}
