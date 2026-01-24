<?php

namespace App\Events\Game;

use App\Models\Game;

class PPGameVerified
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
