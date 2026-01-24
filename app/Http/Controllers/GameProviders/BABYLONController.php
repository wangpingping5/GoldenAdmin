<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class BABYLONController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const BABYLON_PROVIDER = 'babylon';
        const BABYLON_GAME_IDENTITY = [
            //==== CASINO ====
            'babylon-evo' => ['thirdname' =>'1','type' => 'casino'],
            'babylon-dg' => ['thirdname' =>'DREAMGAME','type' => 'casino'],
            'babylon-micro' => ['thirdname' =>'MICRO','type' => 'casino'],
        ];
        public static function getGameObj($uuid)
        {
            foreach (BABYLONController::BABYLON_GAME_IDENTITY as $ref => $value)
            {
                $gamelist = BABYLONController::getgamelist($ref);
                if ($gamelist)
                {
                    foreach($gamelist as $game)
                    {
                        if ($game['gamecode'] == $uuid)
                        {
                            return $game;
                            break;
                        }
                    }
                }
            }
            return null;
        }

        /*
        */

        
        /*
        * FROM CONTROLLER, API
        */
        public static function sendRequest($api, $param, $header=null) {

            $url = config('app.babylon_api') . $api ;
            $op = config('app.babylon_op');
            $token = config('app.babylon_key');
            $param['agentid'] = $op;
            $param['apikey'] = $token;
            try {
                if ($header){
                    $response = Http::withHeaders($header)->get($url, $param);
                }
                else
                {
                    $response = Http::get($url, $param);
                }
                
                
                if ($response->ok()) {
                    $res = $response->json();
                    return $res;
                }
                else
                {
                    Log::error('BABYLON Request : response is not okay. ' . $url . ':==:' . json_encode($param) . '===body==' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('BABYLON Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('BABYLON Request :  Excpetion. PARAMS= ' . $url . ':==:' . json_encode($param));
            }
            return null;
        }
        public static function signup($user_code) 
        {
            $params = [
                'userid' => $user_code,
                'nickname' => $user_code,
            ];
            $data = BABYLONController::sendRequest('/auth/signup', $params);
            return $data;
        }
        public static function signin($user_code) 
        {
            $params = [
                'userid' => $user_code,
            ];
            $data = BABYLONController::sendRequest('/auth/signin', $params);
            return $data;
        }
        
        public static function getUserBalance($href, $user) {   

            $balance = -1;

            $data = BABYLONController::signin(self::BABYLON_PROVIDER . sprintf("%04d",$user->id));
            try
            {
                if ($data && $data['result'] == 1)
                {
                    $access_token = $data['access_token'];
                    $header = [
                        'Authorization' => 'Bearer ' . $access_token
                    ];
                    $data = BABYLONController::sendRequest('/money/info', null, $header);
                    if ($data && $data['result'] == 1)
                    {
                        $balance = $data['data']['account'];
                    }
                }
            }
            catch (\Exception $ex)
            {
                Log::error('BABYLON getUserBalance :  Excpetion. exception= ' . $ex->getMessage());
            }
            return intval($balance);
        }
        
        public static function getgamelist($href)
        {
            $gameList = \Illuminate\Support\Facades\Redis::get($href.'list');
            if ($gameList)
            {
                $games = json_decode($gameList, true);
                if ($games!=null && count($games) > 0){
                    return $games;
                }
            }
            $category = BABYLONController::BABYLON_GAME_IDENTITY[$href];
            

            $params = [
                'thirdpartycode' => $category['thirdname'],
            ];

            $gameList = [];
            $data = BABYLONController::sendRequest('/game/gamelist',$params);

            if ($data && $data['result'] == 1)
            {
                foreach ($data['datas']['list'] as $game)
                {
                    $view = 0;
                    if ($href=='babylon-evo' && $game['code'] == 'top_games')
                    {
                        $view = 1;
                    }
                    array_push($gameList, [
                        'provider' => self::BABYLON_PROVIDER,
                        'href' => $href,
                        'gamecode' => $game['code'],
                        'symbol' => $game['code'],
                        'name' => preg_replace('/\s+/', '', $game['name_eng']),
                        'title' => $game['name_kor'],
                        'type' => 'table',
                        'icon' => $game['img_1'],
                        'view' => $view
                    ]);
                }

            }

            //add Unknown Game item
            array_push($gameList, [
                'provider' => self::BABYLON_PROVIDER,
                'href' => $href,
                'symbol' => 'Unknown',
                'gamecode' => 'Unknown',
                'enname' => 'UnknownGame',
                'name' => 'UnknownGame',
                'title' => 'UnknownGame',
                'icon' => '',
                'type' => 'table',
                'view' => 0
            ]);
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
        }

        public static function makegamelink($gamecode, $user) 
        {
            $gameObj = BABYLONController::getGameObj($gamecode);
            if (!$gameObj)
            {
                return null;
            }
            $thirdpartycode = BABYLONController::BABYLON_GAME_IDENTITY[$gameObj['href']];
            $params = [
                'thirdpartycode' => $thirdpartycode['thirdname'],
                'userid' => self::BABYLON_PROVIDER . sprintf("%04d",$user->id),
                "gamecode" =>  $gamecode,
                'platform' => 'pc'
            ];

            $data = BABYLONController::sendRequest('/game/play',$params);
            $url = null;

            if ($data && $data['res']['result'] == 1)
            {
                $url = $data['res']['data']['link'];
            }
            else
            {
                Log::error('BABYLON makegamelink :  error= ' . json_encode($params));
            }

            return $url;
        }
        
        public static function withdrawAll($href, $user)
        {
            $balance = BABYLONController::getuserbalance($href,$user);
            if ($balance < 0)
            {
                return ['error'=>true, 'amount'=>$balance, 'msg'=>'getuserbalance return -1'];
            }
            if ($balance > 0)
            {
                $data = BABYLONController::signin(self::BABYLON_PROVIDER . sprintf("%04d",$user->id));
                try
                {
                    if ($data && $data['result'] == 1)
                    {
                        $access_token = $data['access_token'];
                        $header = [
                            'Authorization' => 'Bearer ' . $access_token
                        ];
                        $param = [
                            'amount' => $balance
                        ];
                        $data = BABYLONController::sendRequest('/money/withdrawal', $param, $header);
                        if ($data==null || $data['result'] != 1)
                        {
                            return ['error'=>true, 'amount'=>0, 'msg'=>'data not ok'];
                        }
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('BABYLON withdrawall :  Excpetion. exception= ' . $ex->getMessage());
                }
            }
            return ['error'=>false, 'amount'=>$balance];
        }

        public static function makelink($gamecode, $userid)
        {
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('GOLDMakeLink : Does not find user ' . $userid);
                return null;
            }

            $game = BABYLONController::getGameObj($gamecode);
            if ($game == null)
            {
                Log::error('GOLDMakeLink : Game not find  ' . $gamecode);
                return null;
            }

            //create babylon account
            $data = BABYLONController::signup(self::BABYLON_PROVIDER . sprintf("%04d",$user->id));
            
            if ($data==null || $data['result'] != 1)
            {
                return null;
            }
            
            
            $balance = BABYLONController::getuserbalance($game['href'], $user);
            if ($balance == -1)
            {
                return null;
            }

            if ($balance != $user->balance)
            {
                //withdraw all balance
                $data = BABYLONController::withdrawAll($game['href'], $user);
                if ($data['error'])
                {
                    return null;
                }
                //Add balance

                if ($user->balance > 0)
                {
                    $params = [
                        "amount" =>  intval($user->balance),
                    ];

                    $data = BABYLONController::signin(self::BABYLON_PROVIDER . sprintf("%04d",$user->id));
                    $access_token = $data['access_token'];
                    $header = [
                        'Authorization' => 'Bearer ' . $access_token
                    ];
                    $data = BABYLONController::sendRequest('/money/deposit',$params, $header);
                    if ($data==null || $data['result'] != 1)
                    {
                        Log::error('BABYLONMakeLink : addMemberPoint result failed. ' . ($data==null?'null':json_encode($data)));
                        return null;
                    }
                    
                }
            }
            
            return '/followgame/babylon/'.$gamecode;

        }

        public static function getgamelink($gamecode)
        {
            if (isset(self::BABYLON_GAME_IDENTITY[$gamecode]))
            {
                $gamelist = BABYLONController::getgamelist($gamecode);
                if (count($gamelist) > 0)
                {
                    foreach ($gamelist as $g)
                    {
                        if ($g['view'] == 1)
                        {
                            $gamecode = $g['gamecode'];
                            break;
                        }
                    }
                    
                }
            }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [BABYLONController::BABYLON_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($pageIdx, $startDate, $endDate)
        {
            
            $params = [
                'page' => $pageIdx,
                'page_size' => 1000,
                'startdate' => $startDate,
                'enddate' => $endDate
            ];

            $data = BABYLONController::sendRequest('/game/betlist',$params);

            return $data;
        }

        public static function processGameRound($from='', $to='')
        {
            $count = 0;


            $category_ids = \App\Models\Category::where([
                'provider'=> BABYLONController::BABYLON_PROVIDER,
                'shop_id' => 0,
                'site_id' => 0,
                ])->pluck('original_id')->toArray();
            if (count($category_ids) == 0)
            {
                return [$count, 0];
            }
            $roundfrom = '';
            $roundto = '';

            if ($from == '')
            {
                $roundfrom = date('Y-m-d H:i:s',strtotime('-12 hours'));
                $lastround = \App\Models\StatGame::whereIn('category_id', $category_ids)->orderby('date_time', 'desc')->first();
                if ($lastround)
                {
                    $d = strtotime($lastround->date_time);
                    if ($d > strtotime("-2 hours"))
                    {
                        $roundfrom = date('Y-m-d H:i:s',strtotime($lastround->date_time. ' +1 seconds'));
                    }
                }
            }
            else
            {
                $roundfrom = $from;
            }

            if ($to == '')
            {
                $roundto = date('Y-m-d H:i:s');
            }
            else
            {
                $roundto = $to;
            }

            $start_timeStamp = strtotime($roundfrom);
            $end_timeStamp = strtotime($roundto);
            if ($end_timeStamp < $start_timeStamp)
            {
                Log::error('BABYLON processGameRound : '. $roundto . '>' . $roundfrom);
                return [0, 0];
            }

            do
            {
                $curend_timeStamp = $start_timeStamp + 3600; // 1hours
                if ($curend_timeStamp > $end_timeStamp)
                {
                    $curend_timeStamp = $end_timeStamp;
                }
                $curPage = 1;
                $data = null;
                do
                {
                    // Log::info('BABYLON processGameRound : '. date('Y-m-d H:i:s', $start_timeStamp) . '~' . date('Y-m-d H:i:s', $curend_timeStamp));

                    $data = BABYLONController::gamerounds($curPage, date('Y-m-d H:i:s', $start_timeStamp), date('Y-m-d H:i:s', $curend_timeStamp));
                    if ($data == null)
                    {
                        Log::error('BABYLON gamerounds failed : '. date('Y-m-d H:i:s', $start_timeStamp) . '~' . date('Y-m-d H:i:s', $curend_timeStamp));
                        return [0,0];
                    }
                    
                    if (isset($data['datas']) && count($data['datas']) > 0)
                    {
                        foreach ($data['datas'] as $round)
                        {
                            if ($round['actions'] != 'betting' && $round['actions'] != 'win' )
                            {
                                continue;
                            }

                            $bet = 0;
                            $win = 0;
                            $balance = -1;
                            if ($round['actions'] == 'betting')
                            {
                                $bet = abs($round['amount']);
                            }
                            else if ($round['actions'] == 'win')
                            {
                                $win = abs($round['amount']);
                                if ($win == 0)
                                {
                                    continue;
                                }
                            }

                            $balance = $round['tamount'];
                            $gameId = $round['game_code'];
                            $game = BABYLONController::getGameObj($gameId);
                            if (!$game)
                            {
                                $game = BABYLONController::getGameObj('Unknown');
                            }
                            
                            
                            $time = date('Y-m-d H:i:s',strtotime($round['wdate']));
                            $type = ($round['game_type'] == 'slot')?'slot':'table';
                            $category = \App\Models\Category::where('provider', self::BABYLON_PROVIDER)->where('href', $game['href'])->where(['shop_id'=>0, 'site_id'=>0])->first();

                            $userid = intval(preg_replace('/'. self::BABYLON_PROVIDER .'(\d+)/', '$1', $round['userid']));
                            if ($userid == 0)
                            {
                                continue;
                            }
                            $shop = \App\Models\ShopUser::where('user_id', $userid)->first();

                            $checkGameStat = \App\Models\StatGame::where([
                                'user_id' => $userid, 
                                'bet' => $bet, 
                                'win' => $win, 
                                'date_time' => $time,
                                'roundid' =>  $round['round_id'],
                            ])->first();
                            if ($checkGameStat)
                            {
                                continue;
                            }

                            \App\Models\StatGame::create([
                                'user_id' => $userid, 
                                'balance' => $balance, 
                                'bet' => $bet, 
                                'win' => $win, 
                                'game' =>$game['name'] . '_babylon', 
                                'type' => $type,
                                'percent' => 0, 
                                'percent_jps' => 0, 
                                'percent_jpg' => 0, 
                                'profit' => 0, 
                                'denomination' => 0, 
                                'date_time' => $time,
                                'shop_id' => $shop?$shop->shop_id:0,
                                'category_id' => isset($category)?$category->id:0,
                                'game_id' => $round['game_code'],
                                'roundid' => $round['round_id'],
                            ]);
                            $count = $count + 1;
                        }
                    }
                    else
                    {
                        break;
                    }
                    $curPage = $curPage + 1;
                } while ($data!=null);
                // sleep(60);
                $start_timeStamp = $curend_timeStamp;
            }while ($start_timeStamp<$end_timeStamp);
            return [$count, 0];
        }

        


        public static function getAgentBalance()
        {

            $balance = -1;

            $data = BABYLONController::sendRequest('/money/agentinfo',null);

            if ($data && $data['result'] == 1)
            {
                $balance = $data['data']['account'];
            }
            return intval($balance);
        }


    }

}
