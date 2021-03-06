<?php namespace Boyhagemann\Storage\Validators;

use Boyhagemann\Storage\Contracts;
use Boyhagemann\Storage\Exceptions\Invalid;
use Particle\Validator\Validator;

class FieldValidator implements Contracts\Validator
{
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * FieldValidator constructor.
     */
    public function __construct()
    {
        $this->validator = new Validator();

        // Add the create validation rules
        $this->validator->context('create', function(Validator $v) {
            $v->required('entity');
            $v->required('name');
            $v->required('required')->bool();
            $v->required('collection')->bool();
            $v->required('type')->inArray(['string', 'number', 'bool', 'json']);
        });

        // Add the update validation rules
        $this->validator->context('update', function(Validator $v) {
            $v->optional('required')->bool();
            $v->optional('collection')->bool();
        });
    }

    /**
     * @param array $data
     * @throws Invalid
     * @return array
     */
    public function validateCreate(Array $data)
    {
        $result = $this->validator->validate($data, 'create');

        if($result->isNotValid()) {
            throw new Invalid($result->getMessages());
        }

        return $result->getValues();
    }

    /**
     * @param string $id
     * @param array $data
     * @throws Invalid
     * @return array
     */
    public function validateUpdate($id, Array $data)
    {
        $result = $this->validator->validate($data, 'update');

        if($result->isNotValid()) {
            throw new Invalid($result->getMessages());
        }

        return $result->getValues();
    }

}