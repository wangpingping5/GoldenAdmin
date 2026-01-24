<?php 
namespace App\Http\Controllers\GameParsers\PowerBall
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class BasePB
    {
        const provider = 'powerball';
        public $game = 0;
        public $ROUND_URL = 'https://ntry.com/data/json/games/eos_powerball/3min/recent_result.json';
        public $GAME_TIME = 170;
        public $GAME_PERIOD = 180;
        public $LAST_LIMIT_TIME = 10;
        public $VIDEO_URL = 'https://ntry.com/scores/eos_powerball/3min/main.php';
        public $HISTORY_TYPE = 'single'; // single/multi

        public function generateGameRounds($date)
        {
            //remove all old date
            $from_sl = $date . ' 00:00:00';
            $to_sl = $date . ' 23:59:59';
            \App\PowerBallModel\PBGameRound::where('s_time','>=', $from_sl)->where('s_time','<', $to_sl)->where('game_id',$this->game)->delete();

            //create
            $from_ts = strtotime($from_sl);
            $to_ts = strtotime($to_sl);
            $i = 1;
            for ($ts = $from_ts; $ts < $to_ts; $ts += $this->GAME_PERIOD, $i=$i+1)
            { 
                $currnt = time();
                $dno = substr(str_replace('-', '', $date),2) . sprintf('%06d', $i);
                $ets = $ts + $this->GAME_TIME;
                \App\PowerBallModel\PBGameRound::create(
                    [
                        'game_id' => $this->game,
                        'ground_no' => $dno,
                        'dround_no' => sprintf('%06d', $i),
                        'balls' => null,
                        'result' => null,
                        'status' => 0,
                        's_time' => date('Y-m-d H:i:s', $ts),
                        'e_time' => date('Y-m-d H:i:s', $ets),
                    ]
                );
            }
        }

        public function getRT($roundBalls)
        {
            $balls = explode('|', $roundBalls);
            $nball = $balls[0] + $balls[1] + $balls[2] + $balls[3] + $balls[4];
            $tball = $balls[5];
            $rt = '';
            if ($tball % 2 == 1) //파홀짝
            {
                $rt = $rt . '1|';
            }
            else
            {
                $rt = $rt . '2|';
            }

            if ($tball  < 5) //파언오
            {
                $rt = $rt . '3|';
            }
            else
            {
                $rt = $rt . '4|';
            }

            //파홀짝&파언오
            if ($tball % 2 == 1) //파홀짝
            {
                if ($tball  < 5) //파언오
                {
                    $rt = $rt . '5|';
                }
                else
                {
                    $rt = $rt . '6|';
                }
                
            }
            else
            {
                if ($tball  < 5) //파언오
                {
                    $rt = $rt . '7|';
                }
                else
                {
                    $rt = $rt . '8|';
                }
            }


            if ($nball % 2 == 1) //일홀짝
            {
                $rt = $rt . '9|';
            }
            else
            {
                $rt = $rt . '10|';
            }

            if ($nball  < 73) //일언오
            {
                $rt = $rt . '11|';
            }
            else
            {
                $rt = $rt . '12|';
            }

            //일홀짝&일언오
            if ($nball % 2 == 1) //일홀짝
            {
                if ($nball  < 73) //일언오
                {
                    $rt = $rt . '13|';
                }
                else
                {
                    $rt = $rt . '14|';
                }
                
            }
            else
            {
                if ($nball  < 73) //일언오
                {
                    $rt = $rt . '15|';
                }
                else
                {
                    $rt = $rt . '16|';
                }
            }

            //조합 파&홀
            if ($nball % 2 == 1) //일홀짝
            {
                if ($nball  < 73) //일언오
                {
                    if ($tball % 2 == 1)
                    {
                        $rt = $rt . '17';
                    }
                    else
                    {
                        $rt = $rt . '18';
                    }
                }
                else 
                {
                    if ($tball % 2 == 1)
                    {
                        $rt = $rt . '19';
                    }
                    else
                    {
                        $rt = $rt . '20';
                    }
                }
            }
            else
            {
                if ($nball  < 73) //일언오
                {
                    if ($tball % 2 == 1)
                    {
                        $rt = $rt . '21';
                    }
                    else
                    {
                        $rt = $rt . '22';
                    }
                }
                else 
                {
                    if ($tball % 2 == 1)
                    {
                        $rt = $rt . '23';
                    }
                    else
                    {
                        $rt = $rt . '24';
                    }
                }
            }


            return $rt;
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
        public function getRoundInfo()
        {
            try {
                $response = Http::get($this->ROUND_URL);
                if ($response->ok()) {
                    $data = $response->json();
                    if ($data != null)
                    {
                        return $this->parseRound($data);
                    }
                }
            }
            catch (\Exception $ex)
            {
                Log::channel('powerball_game')->info('getRoundInfo exception : ' . $ex->getMessage());
            }
            
            return null;
        }

        public function processRound()
        {
            $currTime = date('Y-m-d H:i:s');
            $pendingRounds = \App\PowerBallModel\PBGameRound::where('status',0)->where('game_id',$this->game)->where('e_time', '<', $currTime)->orderby('s_time')->get();
            if (count($pendingRounds) > 0) {
                $data = $this->getRoundInfo();
                if ($data!=null)
                {
                    foreach ($pendingRounds as $trend)
                    {
                        foreach ($data as $ga)
                        {
                            $ground_no = $ga['ground_no'];
                            if ($trend->ground_no == $ground_no)
                            {
                                //process this round
                                $trendResult = $ga['ball'];
                                $rt = $this->getRT($trendResult);
                                $trend->update([
                                    'balls' => $trendResult,
                                    'result' => $rt,
                                    'status' => 1
                                ]);

                                $rts = explode('|', $rt);

                                $totalBets = \App\PowerBallModel\PBGameBet::where([
                                    'game_id' => $trend->game_id,
                                    'ground_no' => $trend->ground_no,
                                    'status' => 0,
                                ])->whereIn('result', $rts)->get();
                                $users = [];
                                foreach ($totalBets as $bet)
                                {
                                    $bet->update([
                                        'win' => $bet->amount * $bet->rate
                                    ]);
                                }

                                //update user balance and add game_stat
                                $totalBets = \App\PowerBallModel\PBGameBet::where([
                                    'game_id' => $trend->game_id,
                                    'ground_no' => $trend->ground_no,
                                    'status' => 0,
                                ])->groupby('user_id')->selectRaw('user_id, sum(amount) as bet, sum(win) as win')->get();

                                $cat_id = 0;
                                $gameObj = \App\Models\Game::where('id', $this->game)->first();
                                if ($gameObj)
                                {
                                    $cats = $gameObj->categories;
                                    foreach ($cats as $ct)
                                    {
                                        $cat_id = $ct->category_id; 
                                    }
                                }
                                
                                foreach ($totalBets as $bet)
                                {
                                    $user = \App\Models\User::where('id', $bet->user_id)->first();
                                    if ($user)
                                    {
                                        $user->update([
                                            'balance' => $user->balance + $bet->win
                                        ]);
                                        $user = $user->fresh();
                                        \App\Models\StatGame::create([
                                            'user_id' => $user->id, 
                                            'balance' => intval($user->balance), 
                                            'bet' => $bet->bet, 
                                            'win' => $bet->win, 
                                            'game' =>  $gameObj->name, 
                                            'type' => 'pball',
                                            'percent' => 0, 
                                            'percent_jps' => 0, 
                                            'percent_jpg' => 0, 
                                            'profit' => 0, 
                                            'denomination' => 0, 
                                            'shop_id' => $user->shop_id,
                                            'category_id' => $cat_id,
                                            'game_id' => $this->game,
                                            'roundid' => $trend->game_id . '_' . $trend->ground_no,
                                        ]);
                                    }
                                }


                                $totalBets = \App\PowerBallModel\PBGameBet::where([
                                    'game_id' => $trend->game_id,
                                    'ground_no' => $trend->ground_no,
                                    'status' => 0,
                                ])->update(['status' => 1]);
                                break;
                            }
                        }
                    }
                    
                }
            }
        }

        public function gameDetail(\App\Models\StatGame $stat)
        {
            $rounds = explode('_',$stat->roundid);
            $dno = $rounds[1];
            $userbets = \App\PowerBallModel\PBGameBet::where([
                'game_id' => $this->game,
                'ground_no' => $dno,
                'user_id' => $stat->user_id
            ])->get();
            $trend = \App\PowerBallModel\PBGameRound::where(
                [
                    'game_id' => $this->game,
                    'ground_no' => $dno
                ]
            )->first();
            $gameObj = \App\Models\Game::where('id', $this->game)->first();
            return [
                'type' => 'powerball',
                'result' => $trend,
                'bets' => $userbets,
                'stat' => $stat,
                'game_name' => $gameObj->title
            ];
        }

        //web routes

        public function placeBet(\Illuminate\Http\Request $request)
        {
            $token = $request->token;
            $user = \App\Models\User::where('api_token', $token)->where('role_id',1)->first();
            if (!$user)
            {
                return response()->json([
                    'error' => true,
                    'bet_id' => '',
                    'msg' => trans('Could not find user')
                ], 200);
            }
            $game_id = $request->game_id;
            $ground_no = $request->ground_no;
            $result = $request->result;
            $amount = $request->amount;
            $res_bets = [];
            $totalBet = 0;
            
            $currentRound = \App\PowerBallModel\PBGameRound::where('status',0)->where('game_id', $game_id)->where('ground_no', $ground_no)->first();
            if (!$currentRound)
            {
                return response()->json([
                    'error' => true,
                    'bet_id' => '',
                    'msg' => '알수없는 게임회차'
                ], 200);
            }

            $currTime = time();
            $endTime = strtotime($currentRound->e_time);
            
            if ($this->LAST_LIMIT_TIME>0 && $endTime - $currTime  < $this->LAST_LIMIT_TIME)
            {
                return response()->json([
                    'error' => true,
                    'bet_id' => '',
                    'msg' => '베팅시간이 아닙니다'
                ], 200);
            }
            if ($amount <= 0)
            {
                return response()->json([
                    'error' => true,
                    'bet_id' => '',
                    'msg' => '베팅금액을 입력하세요'
                ], 200);
            }

            if ($user->balance < $amount)
            {
                return response()->json([
                    'error' => true,
                    'bet_id' => -1,
                    'msg' => '잔고가 부족합니다'
                ], 200);
            }
            $user->balance = $user->balance - abs($amount);
            $bet_id = substr(date('YmdHis', time()),2);
            $rate = 0;
            $pbresult = \App\PowerBallModel\PBGameResult::where([
                'game_id' => $game_id,
                'rt_no' => $result
            ])->first();
            if (!$pbresult)
            {
                $pbresult = \App\PowerBallModel\PBGameResult::where([
                    'game_id' => 0,
                    'rt_no' => $result
                ])->first();
                if (!$pbresult)
                {
                    return response()->json([
                        'error' => true,
                        'bet_id' => -1,
                        'msg' => '알수없는 베팅'
                    ], 200);
                }
            }

            $rate = $pbresult->rt_rate;

            $brecord = \App\PowerBallModel\PBGameBet::create(
                [
                    'user_id' => $user->id,
                    'game_id' => $game_id,
                    'ground_no' => $ground_no,
                    'bet_id' => $bet_id,
                    'result' => $result,
                    'rate' => $rate,
                    'amount' => $amount,
                    'win' => 0,
                    'status' => 0
                ]
            );
            $bet_id = $bet_id . sprintf('%06d', $brecord->id);
            $brecord->update([
                'bet_id' => $bet_id
            ]);

            $user->save();
            
            return response()->json([
                'error' => false,
                'bet_id'  => $bet_id,
                'msg' => 'OK'
            ], 200);

        }
        public function historyBet(\Illuminate\Http\Request $request)
        {
            $token = $request->token;
            $game_id = $request->game_id;
            $user = \App\Models\User::where('api_token', $token)->where('role_id',1)->first();
            if (!$user)
            {
                return response()->json([
                    'error' => true,
                    'bets'  => []
                ], 200);
            }
            if ($game_id == 0)
            {
                $userHistory = \App\PowerBallModel\PBGameBet::where([
                    'user_id' => $user->id,
                ])->orderby('created_at', 'desc')->limit(20)->get();
            }
            else
            {
                $userHistory = \App\PowerBallModel\PBGameBet::where([
                    'user_id' => $user->id,
                    'game_id' => $game_id,
                ])->orderby('created_at', 'desc')->limit(20)->get();
            }
            return response()->json([
                'error' => false,
                'bets'  => $userHistory
            ], 200);


        }
        public function userInfo(\Illuminate\Http\Request $request)
        {
            $token = $request->token;
            if ($token == '')
            {
                return response()->json([
                    'error' => true,
                    'servertime' => time(),
                ], 200);
            }
            $user = \App\Models\User::where('api_token', $token)->where('role_id',1)->first();
            if (!$user)
            {
                return response()->json([
                    'error' => true,
                    'servertime' => time(),
                ], 200);
            }
            return response()->json([
                'error' => false,
                'servertime'  => time(),
                'username' => $user->username,
                'balance' => $user->balance
            ], 200);


        }
        public function currentRound(\Illuminate\Http\Request $request)
        {
            $game_id = $request->game_id;
            $from_sl = date('Y-m-d H:i:s');
            $round = \App\PowerBallModel\PBGameRound::where('e_time','>=', $from_sl)->where(['game_id' => $game_id, 'status' => 0])->orderby('id', 'asc')->first();
            if (!$round)
            {
                return response()->json([
                    'error' => true,
                    'round'  => null
                ], 200);
            }
            $round = $round->toArray();
            $round['remindtime'] = strtotime($round['e_time']) - time();
            return response()->json([
                'error' => false,
                'round'  => $round
            ], 200);
        }

        public function recentRounds(\Illuminate\Http\Request $request)
        {
            $game_id = $request->game_id;
            $to_sl = date('Y-m-d H:i:s');
            $from_sl = date('Y-m-d H:i:s', strtotime("-1 days"));
            $rounds = \App\PowerBallModel\PBGameRound::where('e_time','>=', $from_sl)->where('e_time','<', $to_sl)->where('game_id',$game_id)->orderby('id', 'desc')->get();
            return response()->json([
                'error' => false,
                'rounds'  => $rounds
            ], 200);
            

        }
    }
}
