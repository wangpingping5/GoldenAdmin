<?php

namespace App\Events\Game;

use App\Models\Game;

class DeleteGame
{
    /**
     * @var Returns
     */
    protected $DeleteGame;

    public function __construct(Game $DeleteGame)
    {
        $this->DeleteGame = $DeleteGame;
    }

    /**
     * @Game Games
     */
    public function getDeleteGame()
    {
        return $this->DeleteGame;
    }
}
