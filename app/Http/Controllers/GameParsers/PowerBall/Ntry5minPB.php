<?php 
namespace App\Http\Controllers\GameParsers\PowerBall
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class Ntry5minPB extends EOS5minPB
    {
        public $ROUND_URL = 'https://ntry.com/data/json/games/eos_powerball/5min/recent_result.json';
        public  $GAME_TIME = 300;
        public  $GAME_PERIOD = 300;
        public  $LAST_LIMIT_TIME = 22;
        public  $VIDEO_URL = 'https://bepick.net/live/ntry_power/scrap';
    }
}
