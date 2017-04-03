<?php namespace Boyhagemann\Storage\Validators;

use Boyhagemann\Storage\Contracts;
use Boyhagemann\Storage\Exceptions\Invalid;
use Particle\Validator\Validator;

class EntityValidator implements Contracts\Validator
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
            $v->required('name');
        });

        // Add the update validation rules
        $this->validator->context('update', function(Validator $v) {
            $v->required('_id');
        });
    }

    /**
     * @param array $data
     * @throws Invalid
     */
    public function validateCreate(Array $data)
    {
        $result = $this->validator->validate($data, 'create');

        if($result->isNotValid()) {
            throw new Invalid($result->getMessages());
        }
    }

    /**
     * @param string $id
     * @param array $data
     * @throws Invalid
     */
    public function validateUpdate($id, Array $data)
    {
        $result = $this->validator->validate($data, 'create');

        if($result->isNotValid()) {
            throw new Invalid($result->getMessages());
        }
    }

}