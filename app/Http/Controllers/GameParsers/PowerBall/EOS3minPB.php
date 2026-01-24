<?php 
namespace App\Http\Controllers\GameParsers\PowerBall
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class EOS3minPB extends BasePB
    {
        public $ROUND_URL = 'https://ntry.com/data/json/games/eos_powerball/3min/recent_result.json';
        public  $GAME_TIME = 180;
        public  $GAME_PERIOD = 180;
        public  $LAST_LIMIT_TIME = 10;
        public  $VIDEO_URL = 'https://ntry.com/scores/eos_powerball/3min/main.php';

        public function __construct($gameid){
            $this->game = $gameid;
        }
        
        public function parseRound($rawdata)
        {
            //this is for eospowerball3
            $rounds = [];
            foreach ($rawdata as $ga)
            {
                $d = explode('-', $ga['reg_date']);
                $dt = sprintf('%04d%02d%02d', $d[0], $d[1], $d[2]);
                $dno = substr($dt, 2)  . sprintf('%06d', $ga['date_round']);
                $trendResult = $ga['ball_1'] .'|'.$ga['ball_2'] .'|' .$ga['ball_3'] .'|' .$ga['ball_4'] .'|'.$ga['ball_5'] .'|'.$ga['powerball'];
                $rounds[] = [
                    'ground_no' => $dno,
                    'ball' => $trendResult,
                ];
            }
            return $rounds;
        }

    }
}
