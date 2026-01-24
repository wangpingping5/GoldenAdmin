<?php

namespace App\Events\Jackpot;

use App\HappyHourUser;

class JackpotEdited
{
    /**
     * @var Jackpots
     */
    protected $editedJackpot;

    public function __construct(HappyHourUser $editedJackpot)
    {
        $this->editedJackpot = $editedJackpot;
    }

    /**
     * @Jackpot Jackpots
     */
    public function getEditedJackpot()
    {
        return $this->editedJackpot;
    }

}
