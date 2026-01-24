<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class GamePlayController extends \App\Http\Controllers\Controller
    {
        const GPGameList = [
            12 => 'TaiXiu',
            13 => 'XocDia',
            90 => 'WinPowerBall',
            91 => 'EOSPowerBall5',
            92 => 'EOSPowerBall3',
        ];
        //utility function
        public static function gamePlayTimeFormat($t=0)
        {
            if ($t==0)
            {
                $t = time();
            }
            $servertime = $t -  1356969600;
            return (int)$servertime;
        }

        public static function dateTimeFromGPTime($t)
        {
            $servertime = $t +  1356969600;
            return date('Y-m-d H:i:s', $servertime);
        }

        public static function generateGameTrend($date, $game)
        {
            //remove all old date
            $from_sl = $date . 'T00:00:00';
            $to_sl = $date . 'T23:59:59';
            if ($game != 0)
            {
                \App\Models\GPGameTrend::where('sDate','>=', $from_sl)->where('sDate','<', $to_sl)->where('p',$game)->delete();
            }
            else
            {
                \App\Models\GPGameTrend::where('sDate','>=', $from_sl)->where('sDate','<', $to_sl)->delete();
            }

            foreach (self::GPGameList as $p => $name)
            {
                if ($game==0 || $game==$p) {
                    $object = '\App\Models\Games\\' . $name . 'GP\Server';
                    if (!class_exists($object))
                    {
                        continue;
                    }
                    $gameObject = new $object();
                    if ($gameObject->SELF_GEN_TREND)
                    {
                        continue;
                    }

                    //create
                    $from_ts = strtotime($from_sl);
                    $to_ts = strtotime($to_sl);
                    $i = 1;
                    for ($ts = $from_ts; $ts < $to_ts; $ts += $gameObject->GAME_PERIOD, $i=$i+1)
                    { 
                        $currnt = time();
                        $dno = substr(str_replace('-', '', $date),2) . sprintf('%06d', $i);
                        $ets = $ts + $gameObject->GAME_TIME;
                        \App\Models\GPGameTrend::create(
                            [
                                'game_id' => strtolower($name),
                                'p' => $p,
                                'a' => 1,
                                'dno' => $dno,
                                'e' => GamePlayController::gamePlayTimeFormat($ets),
                                'sl' => GamePlayController::gamePlayTimeFormat($ts),
                                'rno' => null,
                                'rt' => null,
                                's' => 0,
                                'sDate' => explode('.',date(DATE_RFC3339_EXTENDED, $ts))[0],
                                'eDate' => explode('.',date(DATE_RFC3339_EXTENDED, $ets))[0],
                                'PartialResultTime' => 0
                            ]
                        );
                    }
                }
            }
        }

        public static function processOldTrends($game)
        {
            $currTime = time();
            if ($game==0)
            {
                $oldTrends = \App\Models\GPGameTrend::where('s',0)->where('e', '<', GamePlayController::gamePlayTimeFormat($currTime))->get();
            }
            else
            {
                $oldTrends = \App\Models\GPGameTrend::where('s',0)->where('e', '<', GamePlayController::gamePlayTimeFormat($currTime))->where('p', $game)->get();
            }
            if (count($oldTrends) == 0)
            {
                return;
            }
            foreach ($oldTrends as $trend){
                $p = $trend->p;
                $object = '\App\Models\Games\\' . self::GPGameList[$p] . 'GP\Server';
                if (!class_exists($object))
                {
                    continue;
                }
                $gameObject = new $object();
                if (!$gameObject->SELF_GEN_TREND)
                {
                    $trendResult = $gameObject->generateResult();
                    $trend->update([
                        'rno' => $trendResult['rno'],
                        'rt' => $trendResult['rt'],
                        's' => 1,
                    ]);
                }
                else
                {
                    $trend->update([
                        's' => 9,
                    ]);
                }
            }
        }

        //web api
        public function processCurrentTrend(\Illuminate\Http\Request $request)
        {
            $p = $request->p;
            $object = '\App\Models\Games\\' . self::GPGameList[$p] . 'GP\Server';
            if (!class_exists($object))
            {
                $result = $this->livebet($p, true);
                return response()->json([
                    's' => 0,
                    'live' => $result
                ]);
            }

            $gameObject = new $object();
            $result = $gameObject->processCurrentTrend();

            if ($result)
            {
                $topTrend = \App\Models\GPGameTrend::where('s',0)->where('p',$p)->orderby('sl')->limit(4)->get()->last();
                return response()->json([
                    's' => 1,
                    'old' => $result,
                    'new' => $topTrend
                ]);
            }
            else
            {
                $result = $this->livebet($p, $p<90);
                return response()->json([
                    's' => 0,
                    'live' => $result
                ]);
            }
        }
        public function livebet($game_p, $fake=false)
        {
            $trend = \App\Models\GPGameTrend::where('s',0)->where('p', $game_p)->orderby('sl')->first();
            if (!$trend)
            {
                return [
                    'p' => $game_p,
                    'a' => 1,
                    'data' => null,
                    'r' => 1
                ];
            }
            $totalBets = \App\Models\GPGameBet::where([
                'p' => $game_p,
                'dno' => $trend->dno,
                'status' => 0,
            ])->groupby('rt')->selectRaw('rt, sum(amount) as bet, count(rt) as players')->get();

            $object = '\App\Models\Games\\' . self::GPGameList[$game_p] . 'GP\Server';
            if (!class_exists($object))
            {
                return [
                    'p' => $game_p,
                    'a' => 1,
                    'data' => null,
                    'r' => 1
                ];
            }

            $gameObject = new $object();

            $data = [];
            $maxbet = 10000;
            foreach ($totalBets as $bet)
            {
                $t = [
                    'playerCount' => $bet->players,
                    'id' => $bet->rt,
                    'cont' => null,
                    'a' => intval($bet->bet),
                    'o' => 0,
                    'm' => null
                ];
                if ($bet->bet > $maxbet)
                {
                    $maxbet = $bet->bet;
                }
                array_unshift($data, $t);
            }
            if ($fake)
            {
                foreach ($gameObject->RATE as $id=>$o){
                    $split = 1;//mt_rand(1,2);
                    for ($n=0;$n<$split;$n++){
                        $t = [
                            'playerCount' => 0,
                            'id' => $id,
                            'cont' => null,
                            'a' => 0,
                            'o' => 0,
                            'm' => null
                        ];
                        $cur = GamePlayController::gamePlayTimeFormat();
                        $deltaTime = $cur - $trend->sl;
                        for ($i = 0;$i < $deltaTime;$i++)
                        {
                            $c = mt_rand(2,4);
                            $t['playerCount'] = $t['playerCount'] + $c;
                            $t['a'] = $t['a'] + $c * mt_rand($maxbet, $maxbet * 2);
                        }
                        array_unshift($data, $t);
                    }
                }
            }
            
            $result = [
                'p' => $game_p,
                'a' => 1,
                'data' => $data,
                'r' => 1
            ];
            return $result;
        }
        public function Livebetpool(\Illuminate\Http\Request $request)
        {  
            $data = json_decode($request->getContent());
            $p = $data->p;
            $result = $this->livebet($p, true);
            return response()->json(json_encode($result));
        }
        public function GetMemberDrawResult(\Illuminate\Http\Request $request)
        {
            $token = $request->token;
            $dno = $request->dno;
            $p = $request->P;
            $user = \App\Models\User::where('api_token', $token)->where('role_id',1)->first();
            if (!$user)
            {
                return response()->json([
                    'Code' => 0,
                    'Result'  => [
                        'TotalPayout' => 0,
                        'TotalBet' =>0,
                    ]
                ], 200);
            }
            $stat = \App\Models\StatGame::where([
                'user_id' => $user->id, 
                'roundid' => $p. '_' .$dno,
            ])->first();
            $totalPayout = 0;
            $totalBet = 0;
            if ($stat)
            {
                $totalPayout = $stat->win;
                $totalBet = $stat->bet;
            }
            return response()->json([
                'Code' => 0,
                'Result'  => [
                    'TotalPayout' => round($totalPayout,2),
                    'TotalBet' => round($totalBet,2),
                ]
            ], 200);
        }
        public function HistoryBet(\Illuminate\Http\Request $request)
        {
            $data = json_decode($request->getContent());
            $token = $data->token;
            $start = $data->s;
            $end = $data->e;
            $p = $data->p;
            $pg = $data->pg;
            $user = \App\Models\User::where('api_token', $token)->where('role_id',1)->first();
            if (!$user)
            {
                return response()->json([
                    'cmd' => 'HistoryBet',
                    'data'  => [
                        'p' => $p,
                        'bet' => [],
                        'totalpg' => 0,
                    ]
                ], 200);
            }
            $totalpg = 1;
            if ($pg == 0) $pg = 1;
            if ($start ==0 || $end == 0)
            {
                $userHistory = \App\Models\GPGameBet::where([
                    'user_id' => $user->id,
                    'p' => $p,
                ])->orderby('created_at', 'desc')->limit(10)->get()->toArray();
            }
            else
            {
                $start_date = GamePlayController::dateTimeFromGPTime($start);
                $end_date = GamePlayController::dateTimeFromGPTime($end);
                $userHistory = \App\Models\GPGameBet::where([
                    'user_id' => $user->id,
                    'p' => $p,
                    'status' => 1,
                ])->where('created_at','>=', $start_date)->where('created_at','<=', $end_date)->orderby('created_at', 'desc')->skip(($pg-1) * 10)->take(10)->get()->toArray();

                $totalpg = \App\Models\GPGameBet::where([
                    'user_id' => $user->id,
                    'p' => $p,
                    'status' => 1,
                ])->where('created_at','>=', $start_date)->where('created_at','<=', $end_date)->get()->count();
            }
            $totalCount = count($userHistory);
            $bets = [];
            for ($i = 0; $i < $totalCount; $i++)
            {
                $trend = \App\Models\GPGameTrend::where(
                    [
                        'p' => $p,
                        'dno' => $userHistory[$i]['dno']
                    ]
                )->first();
                $bets[] = [
                    'date' => GamePlayController::gamePlayTimeFormat(strtotime($userHistory[$i]['created_at'])),
                    'bno' => $userHistory[$i]['bet_id'],
                    'bs' => 4,
                    'amt' => round($userHistory[$i]['amount'],2),
                    'stk' => round($userHistory[$i]['amount'],2),
                    'cont' => "1@".$userHistory[$i]['dno']."@".$userHistory[$i]['rt']."@".$userHistory[$i]['o']."@1",
                    'o' => $userHistory[$i]['o'],
                    "win" => $userHistory[$i]['win'] - $userHistory[$i]['amount'],
                    "result" => ($trend==null)?'1|1|1':$trend->rno,
                    "dno" => $userHistory[$i]['dno'],
                    "bundleId"=> (string)($userHistory[$i]['id']),
                    "returnAmount"=> round($userHistory[$i]['win'],2),
                    "rt"=> (string)($userHistory[$i]['rt'])
                ];

            }
            return response()->json([
                'cmd' => 'HistoryBet',
                'data'  => [
                    'p' => $p,
                    'bet' => $bets,
                    'totalpg' => (int)(($totalpg / 10) + 1),
                ]
            ], 200);


        }
        public function MultiLimit(\Illuminate\Http\Request $request)
        {
            $lmt = $request->lmt;
            $p = $request->p;
            return response()->json([
                'cmd' => 'MultiLimit',
                'data'  => [
                    'p' => $p,
                    's' => 'SUCCESS',
                    'lmt' => $lmt
                ]
            ], 200);
        }
        public function GameSetting(\Illuminate\Http\Request $request)
        {
            $lmt = $request->lmt;
            $p = $request->p;
            $object = '\App\Models\Games\\' . self::GPGameList[$p] . 'GP\Server';
            if (!class_exists($object))
            {
                return response()->json([
                    'data'  => null
                ], 200);
            }

            $gameObject = new $object();
            $set = [];
            foreach ($gameObject->RATE as $id=>$o)
            {
                $set[] = [
                    'id' => $id,
                    'o' => $o,
                    'bet' => 1,
                    'lo' => $gameObject->MULTILIMIT[$lmt-1]['lo'],
                    'hi' => $gameObject->MULTILIMIT[$lmt-1]['hi'],
                ];
            }
            return response()->json([
                'data'  => [
                    'normal' => [
                        [
                            'set' => $set,
                            'p' => $gameObject->GAMEID,
                            'a' => 1,
                            'aname' => $gameObject->GAMENAME,
                            'offsite' => '',
                            's' => 1,
                            'stop' => 3,
                            'afreq' => 20,
                            'timezone' => 0,
                            'afmt' => $gameObject->AFMT,
                            'prt' => 0
                        ]
                    ]
                ]
            ], 200);
        }
        public function DrawResult(\Illuminate\Http\Request $request)
        {
            $data = json_decode($request->getContent());
            $p = $data->p;
            $pg = $data->pg;
            $date = $data->date;
            $dno = $data->dno;
            $enddate = $date - 86400; //for 1 day
            $trends = \App\Models\GPGameTrend::where('sl', '<', $date)->where('sl', '>=', $enddate)->where('p', $p)->orderby('sl','desc');
            if ($dno != '')
            {
                $trends = $trends->where('dno', $dno);
            }
            if ($pg==0) $pg = 1;
            $totalCount = (clone $trends)->get()->count();
            $drawsData = $trends->skip(($pg-1) * 10)->limit(10)->get();
            $draws = [];
            foreach ($drawsData as $d)
            {
                $draws[] = [
                    'p' => $p,
                    'a' => $d->a,
                    'dno' => $d->dno,
                    'rno' => $d->rno,
                    's' => $d->sl,
                    'rt' => $d->rt
                ];
            }
            return response()->json([
                'cmd' => 'DrawResult',
                'data'  => [
                    'p' => $p,
                    'draw' =>$draws,
                    'totalpg' => $totalCount
                ]
            ], 200);
        }
        public function WinLose(\Illuminate\Http\Request $request)
        {
            $data = json_decode($request->getContent());
            $p = $data->p;
            $s = $data->s;
            $start_date = GamePlayController::dateTimeFromGPTime($s);
            $e = $data->e;
            $end_date = GamePlayController::dateTimeFromGPTime($s);
            $token = $data->token;
            $user = \App\Models\User::where('api_token', $token)->where('role_id',1)->first();
            if (!$user)
            {
                return response()->json([
                    'cmd' => 'WinLose',
                    'data'  => [
                        [
                        'date' => date('m/d/y'),
                        'stk' => 0,
                        'totalw' => 0
                        ]
                    ]
                ], 200);
            }
            $game = \App\Models\Game::where('name', self::GPGameList[$p] . 'GP')->where('shop_id', 0)->first();
            $stat = \App\Models\StatGame::where([
                'user_id' => $user->id, 
                'game_id' => $game->original_id,
            ])->where('date_time','>=', $start_date)->where('date_time','>=', $end_date)->get();
            $stk = $stat->sum('bet');
            $totalw = $stat->sum('win');

            return response()->json([
                'cmd' => 'WinLose',
                'data'  => [
                    [
                    'date' => date('m/d/y', strtotime($start_date)),
                    'stk' => $stk,
                    'totalw' => $totalw - $stk,
                    ]
                ]
            ], 200);

        }
        public function OpenBet3(\Illuminate\Http\Request $request)
        {
            $token = $request->token;
            $p = $request->p;
            $user = \App\Models\User::where('api_token', $token)->where('role_id',1)->first();
            if (!$user)
            {
                return response()->json([
                    'cmd' => 'OpenBet',
                    'data'  => [
                        'p' => $p,
                        'bet' =>[],
                        'totalpg' => 0
                    ]
                ], 200);
            }
            $openBets = \App\Models\GPGameBet::where([
                'user_id' => $user->id,
                'p' => $p,
                'status' => 0,
            ])->get();
            $openBetData = [];
            foreach ($openBets as $bet)
            {
                $openBetData[] = [
                    'date' => GamePlayController::gamePlayTimeFormat(strtotime($bet->created_at)),
                    'bno' => $bet->bet_id,
                    'bs' => 1,
                    'amt' => round($bet->amount,2),
                    'stk' => round($bet->amount,2),
                    'cont' => "1@$bet->dno@$bet->rt@$bet->o@1",
                    'o' => $bet->o,
                    'win' => 0.00,
                    'returnAmount' => 0.00
                ];
            }

            return response()->json([
                'cmd' => 'OpenBet',
                'data'  => [
                    'p' => $p,
                    'bet' =>$openBetData,
                    'totalpg' => 0
                ]
            ], 200);

        }
        public function ServerTime(\Illuminate\Http\Request $request)
        {
            $currTime = time();
            return response()->json([
                'cmd' => 'ServerTime',
                'data'  => GamePlayController::gamePlayTimeFormat($currTime)
            ], 200);

        }
        public function UserInfo(\Illuminate\Http\Request $request)
        {
            $token = $request->token;
            $init = $request->init;
            $p = $request->p;
            $user = \App\Models\User::where('api_token', $token)->where('role_id',1)->first();
            if (!$user)
            {
                return response()->json([
                    'cmd' => 'UserInfo',
                    'data'  => null
                ], 200);
            }
            $object = '\App\Models\Games\\' . self::GPGameList[$p] . 'GP\Server';
            if (!class_exists($object))
            {
                return response()->json([
                    'cmd' => 'UserInfo',
                    'data'  => null
                ], 200);
            }

            $gameObject = new $object($user);
            $low_values = implode(',',array_column($gameObject->MULTILIMIT, 'lo'));
            $hi_values = implode(',',array_column($gameObject->MULTILIMIT, 'hi'));
            if ($init == 'true')
            {
                return response()->json([
                    'cmd' => 'UserInfo',
                    'data'  => [
                        'userid' => $user->username,
                        'curr' => 'KRW',
                        'wallet' => round($user->balance,1),
                        'lmt' => [
                            [
                            'p' => $gameObject->GAMEID,
                            'prof' => 0,
                            'mul' => $low_values . '|' .$hi_values,
                            ]
                        ],
                        'areas' => [
                            [
                                'p' => $gameObject->GAMEID,
                                'a' => 1,
                                'aname' => $gameObject->GAMENAME,
                                'offsite' => '',
                                's' => 1,
                                'stop' => 3,
                                'afreq' => 20,
                                'timezone' => 0,
                                'afmt' => $gameObject->AFMT,
                                'prt' => 0
                            ]
                        ],
                        'type' => 3,
                    ]
                ], 200);
            }
            else
            {
                return response()->json([
                    'cmd' => 'UserInfo',
                    'data'  => [
                        'userid' => $user->username,
                        'curr' => 'KRW',
                        'wallet' => round($user->balance,1),
                        'type' => 3,
                    ]
                ], 200);
            }

        }
        public function SpreadBet(\Illuminate\Http\Request $request)
        {
            $data = json_decode($request->getContent());
            $token = $data->token;
            $user = \App\Models\User::where('api_token', $token)->where('role_id',1)->first();
            if (!$user)
            {
                return response()->json([
                    'code' => 1,
                    'bets'  => []
                ], 200);
            }
            $gameid = $request->game;
            $res_bets = [];
            $totalBet = 0;
            
            foreach ($data->data as $bet )
            {
                // $currentTrend = \App\Models\GPGameTrend::where('s',0)->where('p', $bet->p)->orderby('sl')->first();
                $currentTrend = \App\Models\GPGameTrend::where('s',0)->where('p', $bet->p)->where('dno', $bet->dno)->first();
                if (!$currentTrend)
                {
                    continue;
                }

                $currTime = GamePlayController::gamePlayTimeFormat();
                
                $object = '\App\Models\Games\\' . self::GPGameList[$bet->p] . 'GP\Server';
                if (!class_exists($object))
                {
                    return response()->json([
                        'code' => -107,
                    ], 200);
                }

                $gameObject = new $object();

                if ($gameObject->LAST_LIMIT_TIME>0 && $currentTrend->e - $currTime  < $gameObject->LAST_LIMIT_TIME)
                {
                    return response()->json([
                        'code' => -107,
                    ], 200);
                }
                if ($bet->amt == 0)
                {
                    continue;
                }

                if ($user->balance < $bet->amt)
                {
                    break;
                }
                $user->balance = $user->balance - abs($bet->amt);
                $bet_id = substr(date('YmdHis', time()),2);
                $brecord = \App\Models\GPGameBet::create(
                    [
                        'user_id' => $user->id,
                        'game_id' => $currentTrend->game_id,
                        'p' => $bet->p,
                        'dno' => $bet->dno,
                        'bet_id' => $bet_id,
                        'rt' => $bet->id,
                        'o' => $gameObject->RATE[$bet->id],
                        'amount' => $bet->amt,
                        'win' => 0,
                        'status' => 0
                    ]
                );
                $bet_id = $bet_id . sprintf('%06d', $brecord->id);
                $brecord->update([
                    'bet_id' => $bet_id
                ]);
                $totalBet = $totalBet + $bet->amt;
                $res_bets[] = "$bet_id|$bet->p|1|$bet->dno|$bet->amt|$bet->amt|1|$bet->id";
            }

            $user->save();
            if (count($res_bets) == 0)
            {
                return response()->json([
                    'code' => -107,
                ], 200);
            }

            return response()->json([
                'code' => 0,
                'bets'  => $res_bets
            ], 200);

        }
        public function Trend(\Illuminate\Http\Request $request)
        {
            // $data = json_decode($request->getContent());
            $p = $request->p;
            $topTrend = \App\Models\GPGameTrend::where('s',0)->where('p',$p)->orderby('sl')->limit(4)->get()->last();
            if (!$topTrend)
            {
                return response()->json([
                    'cmd' => 'Trend',
                    'data'  => [
                        'draw' => null
                    ]
                ], 200);
            }

            $trends = \App\Models\GPGameTrend::where('sl', '<=', $topTrend->sl)->where('p',$p)->orderby('sl','desc')->limit(100)->get();
            return response()->json([
                'cmd' => 'Trend',
                'data'  => [
                    'draw' => $trends
                ]
            ], 200);
        }
        public function GetActiveProductsByVendor(\Illuminate\Http\Request $request)
        {
            return response()->json([
                'code' => 0,
                'productIds'  => [
                    1,3,4,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,33,32,34,35,36
                ]
            ], 200);

        }
        public function GetTrialPromotionInfo(\Illuminate\Http\Request $request)
        {
            return response()->json([
                'err' => [
                    'code' => -201,
                    'cont' => 'DATA NOT FOUND!'
                    ]
            ], 200);
        }

        public function powerball(\Illuminate\Http\Request $request)
        {
            $p = $request->p;
            $object = '\App\Models\Games\\' . self::GPGameList[$p] . 'GP\Server';
            if (!class_exists($object))
            {
                abort(404);
            }

            $gameObject = new $object();
            $videourl = $gameObject->VIDEO_URL;
            $width = $gameObject->VIDEO_WIDTH;
            $height = 800;
            return view('frontend.Default.games.winpowerball', compact('videourl','width','height'));
        }
    }
}
