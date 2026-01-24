<?php 
namespace App\Http\Controllers\GameParsers\PowerBall
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class Pow8885minPB extends BasePB
    {
        public $ROUND_URL = 'http://cms.pow888.com/logic_api/room_info/powerball_result';
        public $GAME_TIME = 370;
        public $GAME_PERIOD = 300;
        public $LAST_LIMIT_TIME = 70;
        public $VIDEO_URL = 'https://viewer.millicast.com/?streamId=nNhedt/PowerballTable1';
        public $HISTORY_TYPE = 'multi'; // single/multi

        public function __construct($gameid){
            $this->game = $gameid;
        }

        public function parseRound($rawdata)
        {
            //this is for pow888
            $rounds = [];
            foreach ($rawdata as $ga)
            {
                $dno = substr($ga['round'],0,6).sprintf('%06d', $ga['Todayround']);
                $normalLst = explode(', ', $ga['normalBallRsts']);
                $trendResult = $normalLst[0] .'|'.$normalLst[1] .'|' .$normalLst[2] .'|' .$normalLst[3] .'|'.$normalLst[4] .'|'.$ga['powerballRst'];
                $rounds[] = [
                    'ground_no' => $dno,
                    'ball' => $trendResult,
                ];
            }
            return $rounds;
        }
    }
}
