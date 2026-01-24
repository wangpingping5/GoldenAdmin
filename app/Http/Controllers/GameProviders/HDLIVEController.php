<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class HDLIVEController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const HDLIVE_PROVIDER = 'hdlive';
        const HDLIVE_GAMEKEY = 'live';
        const HDLIVE_GAME_IDENTITY = [
            //==== CASINO ====
            'hdlive' => ['thirdname' =>'hdlive','type' => 'casino'],
        ];
        public static function getGameObj($uuid)
        {
            foreach (HDLIVEController::HDLIVE_GAME_IDENTITY as $ref => $value)
            {
                $gamelist = HDLIVEController::getgamelist($ref);
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
        * CLEINT IP
        */
        public static function getIp($userid){
            $activity = \App\Models\UserActivity::where('user_id', $userid)->orderby('created_at', 'desc')->first();
            return $activity->ip_address;
        }

        /*
        * GENERATE HASH
        */
        public static function generateHash($data){
            //$opKey = 'f3fff4d3-cbf6-46ef-b710-eea686fad487';
            $secretKey = config('app.hdlive_secretkey');
            if($data == null || count($data) == 0)
            {
                $result = $secretKey;
            }
            else
            {
                $body = json_encode($data);
                // json String + secretKey
                $result = $body.$secretKey;
            }

            // SHA-256 make hash
            $hash_data = hash('sha256', $result, true);
            
            // Base64 encoding
            $hash = base64_encode($hash_data);

            return $hash;
        }
        /*
        * FROM CONTROLLER, API
        */
        public static function sendRequest($sub_url, $param) {

            $url = config('app.hdlive_api') ;
            $agent = config('app.hdlive_agent');
            try {       
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $agent
                    ])->withBody(json_encode($param),'application/json')->post($url . $sub_url, $param);
                if ($response->ok()) {
                    $res = $response->json();
                    return $res;
                }
                else
                {
                    Log::error('HDLive Request : response is not okay. api=>' . $sub_url . ', param =>' . json_encode($param) . ', body=>' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('HDLive Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('HDLive Request :  Excpetion. PARAMS= ' . json_encode($param));
            }
            return null;
        }
        public static function moneyInfo($userid) {
            
            $params = [
                'username' => self::HDLIVE_PROVIDER  . sprintf("%04d",$userid),
                'ipAddress' => HDLIVEController::getIp($userid)
            ];

            $data = HDLIVEController::sendRequest('/balance', $params);
            return $data;
        }
        
        public static function getUserBalance($href, $user) {   

            $balance = -1;
            $data = HDLIVEController::moneyInfo($user->id);

            if ($data && $data['code'] == 0)
            {
                $balance = $data['balance_holdem'];
            }
            return intval($balance);
        }
        
        public static function getgamelist($href)
        {
            // $gameList = \Illuminate\Support\Facades\Redis::get($href.'list');
            // if ($gameList)
            // {
            //     $games = json_decode($gameList, true);
            //     if ($games!=null && count($games) > 0){
            //         return $games;
            //     }
            // }
            // $category = HDLIVEController::HDLIVE_GAME_IDENTITY[$href];
            

            // $params = [
            //     'vendorKey' => $category['thirdname'],
            //     'skin' => HDLIVEController::HDLIVE_GAMEKEY,
            // ];

            // $gameList = [];
            // $data = HDLIVEController::sendRequest('/games', $params);

            // if ($data && $data['code'] == 0)
            // {
            //     foreach ($data['games'] as $game)
            //     {
            //         $gamename = 'Unknown';
            //         $gametitle = 'Unknown';
            //         if(isset($game['names']))
            //         {
            //             $gamename = isset($game['names']['ko'])?$game['names']['ko']:$game['names']['en'];
            //             $gametitle = $game['names']['en'];
            //         }
            //         $icon = '';
            //         if(isset($game['image']))
            //         {
            //             $icon = $game['image'];
            //         }
            //         array_push($gameList, [
            //             'provider' => self::HDLIVE_PROVIDER,
            //             'href' => $href,
            //             'gamecode' => $game['id'],
            //             'symbol' => $game['key'],
            //             'name' => preg_replace('/\s+/', '', $gamename),
            //             'title' => $gametitle,
            //             'type' => 'table',
            //             'icon' => $icon,
            //             'view' => 0
            //         ]);
            //     }
            //     //add casino lobby
            //     array_push($gameList, [
            //         'provider' => self::HDLIVE_PROVIDER,
            //         'href' => $href,
            //         'gamecode' => $category['thirdname'],
            //         'symbol' => 'gclobby',
            //         'name' => 'Lobby',
            //         'title' => 'Lobby',
            //         'type' => 'table',
            //         'icon' => '/frontend/Default/ico/gold/EVOLUTION_Lobby.jpg',
            //         'view' => 1
            //     ]);
            // }

            // //add Unknown Game item
            // array_push($gameList, [
            //     'provider' => self::HDLIVE_PROVIDER,
            //     'href' => $href,
            //     'symbol' => 'Unknown',
            //     'gamecode' => 'Unknown',
            //     'enname' => 'UnknownGame',
            //     'name' => 'UnknownGame',
            //     'title' => 'UnknownGame',
            //     'icon' => '',
            //     'type' => 'table',
            //     'view' => 0
            // ]);
            // \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            // return $gameList;
            return [[
                'provider' => self::HDLIVE_PROVIDER,
                'href' => $href,
                'gamecode' => 'holdem',
                'symbol' => 'holdem',
                'name' => 'Holdem',
                'title' => '홀덤',
                'type' => 'table',
                'icon' => '/frontend/Default/ico/gold/Holdem.png',
                'view' => 1
            ]];
        }

        public static function makegamelink($gamecode, $user) 
        {
            $user_code = self::HDLIVE_PROVIDER  . sprintf("%04d",$user->id);
            $params = [
                'gameKey' => $gamecode,
                'lobbyKey' => HDLIVEController::HDLIVE_GAMEKEY,
                'username' => $user_code,
                'siteUsername' => $user_code,
                'nickname' => $user->username,
                'platform' => 'web',
                'amount' => (float)$user->balance,
                'ipAddress' => HDLIVEController::getIp($user->id),
                'requestKey' => $user->generateCode(24)
            ];

            $data = HDLIVEController::sendRequest('/play', $params);
            $url = null;

            if ($data && $data['code'] == 0)
            {
                $url = $data['url'];
            }
            else
            {
                Log::error('HDLIVEMakeLink : geturl, msg=  ' . $data['msg']);
            }

            return $url;
        }
        
        public static function withdrawAll($href, $user)
        {
            $balance = HDLIVEController::getuserbalance($href,$user);
            if ($balance < 0)
            {
                return ['error'=>true, 'amount'=>$balance, 'msg'=>'getuserbalance return -1'];
            }
            if ($balance > 0)
            {
                $params = [
                    'username' => self::HDLIVE_PROVIDER  . sprintf("%04d",$user->id),
                    'ipAddress' => HDLIVEController::getIp($user->id),
                    'requestKey' => $user->generateCode(24)
                ];

                $data = HDLIVEController::sendRequest('/withdraw', $params);
                if ($data==null || $data['code'] != 0)
                {
                    return ['error'=>true, 'amount'=>0, 'msg'=>'data not ok'];
                }
            }
            return ['error'=>false, 'amount'=>$balance];
        }

        public static function makelink($gamecode, $userid)
        {
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('HDLIVEMakeLink : Does not find user ' . $userid);
                return null;
            }

            $game = HDLIVEController::getGameObj($gamecode);
            if ($game == null)
            {
                Log::error('HDLIVEMakeLink : Game not find  ' . $gamecode);
                return null;
            }
            $user_code = self::HDLIVE_PROVIDER  . sprintf("%04d",$user->id);
            $balance = 0;

            //유저정보 조회
            $data = HDLIVEController::moneyInfo($user->id);
            if($data == null || $data['code'] != 0)
            {
                //새유저 창조
                $params = [
                    'username' => $user_code,
                    'nickname' => $user_code,
                    'siteUsername' => $user_code,
                    'ipAddress' => HDLIVEController::getIp($user->id),
                    'requestKey' => $user->generateCode(24)
                ];

                $data = HDLIVEController::sendRequest('/register', $params);
                if ($data==null || $data['code'] != 0)
                {
                    Log::error('HDLIVEMakeLink : Player Create, msg=  ' . $data['msg']);
                    return null;
                }
            }
            else
            {
                $balance = $data['balance_holdem'];
            }

            if ($balance != $user->balance)
            {
                //withdraw all balance
                $data = HDLIVEController::withdrawAll($game['href'], $user);
                if ($data['error'])
                {
                    return null;
                }
                //Add balance

                // if ($user->balance > 0)
                // {
                //     $params = [
                //         'username' => $user_code,
                //         "amount" =>  intval($user->balance),
                //         'ipAddress' => HDLIVEController::getIp($user->id),
                //         "requestKey" => $user->generateCode(24)
                //     ];
    
                //     $data = HDLIVEController::sendRequest('/deposit', $params);
                //     if ($data==null || $data['code'] != 0)
                //     {
                //         Log::error('HDLIVEMakeLink : addMemberPoint result failed. ' . ($data==null?'null':json_encode($data)));
                //         return null;
                //     }
                    
                // }
            }
            
            return '/followgame/hdlive/'.$gamecode;

        }

        public static function getgamelink($gamecode)
        {
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [HDLIVEController::HDLIVE_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($nIdx)
        {
            $params = [
                'beginDate' => date('Y-m-d H:i:s'),
                'start_idx' => $nIdx,
                'limit' => 2000
            ];

            $data = HDLIVEController::sendRequest('/transaction', $params);

            return $data;
        }
        public static function processGameRound($frompoint=-1, $checkduplicate=false)
        {
            $count = 0;

            $timepoint = 0;
            if ($frompoint == -1)
            {
                $tpoint = \App\Models\Settings::where('key', HDLIVEController::HDLIVE_PROVIDER . 'timepoint')->first();
                if ($tpoint)
                {
                    $timepoint = $tpoint->value;
                }
            }
            else
            {
                $timepoint = $frompoint;
            }

            $count = 0;
            $data = null;
            $newtimepoint = $timepoint;
            $totalCount = 0;
            $data = HDLIVEController::gamerounds($timepoint + 1);
            if ($data == null || $data['code'] != 0 || $data['count'] == 0)
            {
                Log::error('HDLIVE gamerounds failed : Start_Idx : ' . $timepoint);
                return [$count,0];
            }
            
            if (isset($data['data']) && count($data['data']) > 0)
            {
                foreach ($data['data'] as $round)
                {
                    $bet = $round['BetMoney'];
                    $win = $round['WinMoney'];
                    if($bet == 0 && $win == 0)
                    {
                        continue;
                    }
                    $balance = $round['STMoney'] - $bet + $win;
                    
                    $time = date('Y-m-d H:i:s',strtotime($round['StartTime']));

                    $userid = intval(preg_replace('/'. self::HDLIVE_PROVIDER  .'(\d+)/', '$1', $round['user_id'])) ;
                    if($userid == 0){
                        $userid = intval(preg_replace('/'. self::HDLIVE_PROVIDER . 'user' .'(\d+)/', '$1', $round['user_id'])) ;
                    }

                    $gameObj = self::getGameObj($round['pGame']);
            
                    if (!$gameObj)
                    {
                        $gameObj = self::getGameObj($round['nGame']);
                        if (!$gameObj)
                        {
                            Log::error('HDLIVE Game could not found : '. $round['gameId']);
                            continue;
                        }
                    }
                    
                    $shop = \App\Models\ShopUser::where('user_id', $userid)->first();
                    $category = \App\Models\Category::where('href', $gameObj['href'])->first();

                    if($round['nIdx'] > $timepoint){
                        $timepoint = $round['nIdx'];
                    }
                    // if ($checkduplicate)
                    // {
                        $checkGameStat = \App\Models\StatGame::where([
                            'user_id' => $userid, 
                            'bet' => $bet, 
                            'win' => $win, 
                            'date_time' => $time,
                            'roundid' => $round['pGame'] . '#' . $round['game_idx'] . '#' . $round['nIdx'],
                        ])->first();
                        if ($checkGameStat)
                        {
                            continue;
                        }
                    // }
                    $gamename = $gameObj['name'] . '_hdlive';
                    \App\Models\StatGame::create([
                        'user_id' => $userid, 
                        'balance' => $balance, 
                        'bet' => $bet, 
                        'win' => $win, 
                        'game' => $gamename, 
                        'type' => 'table',
                        'percent' => 0, 
                        'percent_jps' => 0, 
                        'percent_jpg' => 0, 
                        'profit' => 0, 
                        'denomination' => 0, 
                        'date_time' => $time,
                        'shop_id' => $shop?$shop->shop_id:-1,
                        'category_id' => $category?$category->original_id:0,
                        'game_id' =>  $gameObj['gamecode'],
                        // 'status' =>  $gameObj['isFinished']==true ? 1 : 0,
                        'roundid' => $round['pGame'] . '#' . $round['game_idx'] . '#' . $round['nIdx'],
                    ]);
                    $count = $count + 1;
                }   
            }
            if ($frompoint == -1)
            {

                if ($tpoint)
                {
                    $tpoint->update(['value' => $timepoint]);
                }
                else
                {
                    \App\Models\Settings::create(['key' => HDLIVEController::HDLIVE_PROVIDER .'timepoint', 'value' => $timepoint]);
                }
            }
           
            return [$count, $timepoint];
        }

        public static function getgamedetail(\App\Models\StatGame $stat)
        {
            $betrounds = explode('#',$stat->roundid);
            if (count($betrounds) < 3)
            {
                return null;
            }
            $game_idx = $betrounds[1];
            $user = \App\Models\User::where('id', $stat->user_id)->first();
            $params = [
                'game_idx' => $game_idx,
                'requestKey' => $user->generateCode(24),
                'game' => 'holdem'
            ];

            $data = HDLIVEController::sendRequest('/logdetail', $params);
            if ($data==null || $data['gamelog'] == null)
            {
                return null;
            }
            $user_code = self::HDLIVE_PROVIDER  . sprintf("%04d",$user->id);

            $betdetails = null;
            $gametype = 'hdlive';
            $result = $data['gamelog'];
            $betdetails = null;
            if($data['gamelog_detail'])
            {
                $details = [];
                foreach($data['gamelog_detail'] as $transaction)
                {
                    if($transaction['siteUsername'] == $user_code)
                    {
                        $details['bet_money'] = $transaction['BetMoney'];
                        $details['win_money'] = $transaction['WinMoney'];
                        break;
                    }
                }
                $details['detail'] = $data['gamelog_detail'];
            }
                    
            $betdetails = $details;// json_encode($details);

            return [
                'type' => $gametype,
                'result' => $result,
                'bets' => $betdetails,
                'stat' => $stat
            ];
        }


        public static function getAgentBalance()
        {

            $balance = -1;

            $params = [];

            $data = HDLIVEController::sendRequest('/partner/balance', $params);
            if ($data==null || $data['code'] != 0)
            {
                return null;
            }
            if ($data && $data['code'] == 0)
            {
                $balance = $data['balance'];
            }
            return intval($balance);
        }


    }

}
