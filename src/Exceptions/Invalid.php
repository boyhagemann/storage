<?php namespace Boyhagemann\Storage\Exceptions;

class Invalid extends \Exception {

    /**
     * @var array
     */
    protected $messages;

    /**
     * InvalidData constructor.
     * @param array $messages
     */
    public function __construct(Array $messages)
    {
        $this->message = sprintf('Invalid data provided: "%s"', json_encode($messages));

        $this->messages = $messages;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}