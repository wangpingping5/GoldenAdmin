<?php 
namespace App\Http\Controllers\GameParsers\PowerBall
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class PH5minPB extends EOS5minPB
    {
        public $ROUND_URL = 'https://updownscore.com/api/last?g_type=powerball&_=1721741796849';
        public  $GAME_TIME = 300;
        public  $GAME_PERIOD = 300;
        public  $LAST_LIMIT_TIME = 22;
        public  $VIDEO_URL = 'https://dhpowerball.net/powerball/live.php';

        public function __construct($gameid){
            $this->game = $gameid;
        }
        
        public function parseRound($rawdata)
        {
            //this is for eospowerball3
            $rounds = [];
            if(isset($rawdata)){
                $d = explode('-', $rawdata['g_date']);
                $dt = sprintf('%04d%02d%02d', $d[0], $d[1], $d[2]);
                $dno = substr($dt, 2)  . sprintf('%06d', $rawdata['date_round']);
                $trendResult = str_replace(',', '|', $rawdata['n_ball']);
                // $trendResult = $ga['ball_1'] .'|'.$ga['ball_2'] .'|' .$ga['ball_3'] .'|' .$ga['ball_4'] .'|'.$ga['ball_5'] .'|'.$ga['powerball'];
                $rounds[] = [
                    'ground_no' => $dno,
                    'ball' => $trendResult,
                ];
            }
            return $rounds;
        }
    }
}
