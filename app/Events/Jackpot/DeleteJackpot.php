<?php

namespace App\Events\Jackpot;

use App\HappyHourUser;

class DeleteJackpot
{
    /**
     * @var Returns
     */
    protected $DeleteJackpot;

    public function __construct(HappyHourUser $DeleteJackpot)
    {
        $this->DeleteJackpot = $DeleteJackpot;
    }

    /**
     * @Jackpot Jackpots
     */
    public function getDeleteJackpot()
    {
        return $this->DeleteJackpot;
    }
}
