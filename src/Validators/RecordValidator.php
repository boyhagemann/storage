<?php namespace Boyhagemann\Storage\Validators;

use Boyhagemann\Storage\Contracts;
use Boyhagemann\Storage\Exceptions\Invalid;
use Particle\Validator\Chain;
use Particle\Validator\Validator;

class RecordValidator implements Contracts\Validator
{
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * RecordValidator constructor.
     * @param Contracts\Entity $entity
     */
    public function __construct(Contracts\Entity $entity)
    {
        $this->validator = new Validator();

        // Add the create validation rules
        $this->validator->context('create', function(Validator $v) use ($entity) {
            foreach($entity->fields() as $field) {
                $this->buildValidation($field, $v, 'create');
            }
        });

        // Add the update validation rules
        $this->validator->context('update', function(Validator $v) use ($entity) {
            foreach($entity->fields() as $field) {
                $this->buildValidation($field, $v, 'update');
            }
        });
    }

    /**
     * @param Contracts\Field $field
     * @param string $context
     * @param Validator $v
     */
    protected function buildValidation(Contracts\Field $field, Validator $v, $context)
    {
        $name = $field->name();

        /** @var Chain $element */
        $element = $field->isRequired() && $context === 'create' ? $v->required($name) : $v->optional($name);

        switch($field->type()) {

            case Contracts\Field::TYPE_STRING:
                $element->string();
                break;

            case Contracts\Field::TYPE_BOOLEAN:
                $element->bool();
                break;

            case Contracts\Field::TYPE_JSON:
                $element->isArray();
                break;

            case Contracts\Field::TYPE_INTEGER:
                $element->numeric();
                break;

            case Contracts\Field::TYPE_DATE:
                $element->datetime('Y-m-d');
                break;

            case Contracts\Field::TYPE_DATETIME:
                $element->datetime('Y-m-d H:i:s');
                break;

            case Contracts\Field::TYPE_FLOAT:
                $element->float();
                break;
        }
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