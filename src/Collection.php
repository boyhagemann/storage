<?php namespace Boyhagemann\Storage;

use Boyhagemann\Storage\Contracts;

class Collection extends \ArrayObject implements Contracts\Collection
{
    /**
     * Collection constructor.
     *
     * @param Contracts\Arrayable[] $data
     */
    public function __construct(Array $data)
    {
        $this->exchangeArray($data);
    }

    /**
     * @return Contracts\Arrayable[]
     */
    public function all()
    {
        return $this->getArrayCopy();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_map(function(Contracts\Arrayable $item) {
            return $item->toArray();
        }, $this->getArrayCopy());
    }


}