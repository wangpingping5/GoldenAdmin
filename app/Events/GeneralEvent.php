<?php

namespace App\Events;


class GeneralEvent
{
    /**
     * @var Returns
     */
    protected $eventString;

    public function __construct($string)
    {
        $this->eventString = $string;
    }

    public function getEventString()
    {
        return $this->eventString;
    }
}
