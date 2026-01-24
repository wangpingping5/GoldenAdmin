<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class GOLDController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const GOLD_PROVIDER = 'gold';
        const GOLD_GAME_IDENTITY = [
            //==== CASINO ====
            'gold-evo' => ['thirdname' =>'Evolution_Casino','type' => 'casino'],
            'gold-dg' => ['thirdname' =>'Dream_Casino','type' => 'casino'],
            'gold-micro' => ['thirdname' =>'Micro_Casino','type' => 'casino'],
        ];
        public static function getGameObj($uuid)
        {
            foreach (GOLDController::GOLD_GAME_IDENTITY as $ref => $value)
            {
                $gamelist = GOLDController::getgamelist($ref);
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
        public static function sendRequest($param) {

            $url = config('app.gold_api') ;
            $op = config('app.gold_op');
            $token = config('app.gold_key');
            $param['agentCode'] = $op;
            $param['token'] = $token;
            try {       
                $response = Http::withBody(json_encode($param),'application/json')->post($url);
                
                if ($response->ok()) {
                    $res = $response->json();
                    return $res;
                }
                else
                {
                    Log::error('GOLD Request : response is not okay. ' . json_encode($param) . '===body==' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('GOLD Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('GOLD Request :  Excpetion. PARAMS= ' . json_encode($param));
            }
            return null;
        }
        public static function moneyInfo($user_code) {
            
            $params = [
                'method' => 'GetUserInfo',
                'userCode' => $user_code,
            ];

            $data = GOLDController::sendRequest($params);
            return $data;
        }
        
        public static function getUserBalance($href, $user) {   

            $balance = -1;
            $user_code = self::GOLD_PROVIDER  . sprintf("%04d",$user->id);
            $data = GOLDController::moneyInfo($user_code);

            if ($data && $data['status'] == 0)
            {
                $users = $data['users'];
                foreach($users as $useritem)
                {
                    if($useritem['userCode'] == $user_code)
                    {
                        $balance = $useritem['balance'];
                        break;
                    }
                }
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
            $category = GOLDController::GOLD_GAME_IDENTITY[$href];
            

            $params = [
                'method' => 'GetVendorGames',
                'vendorCode' => $category['thirdname'],
            ];

            $gameList = [];
            $data = GOLDController::sendRequest($params);

            if ($data && $data['status'] == 0)
            {
                foreach ($data['vendorGames'] as $game)
                {
                    $gamename = 'Unknown';
                    $gametitle = 'Unknown';
                    if(isset($game['gameName']))
                    {
                        $gamenames = json_decode($game['gameName'], true);
                        if($gamenames != null)
                        {
                            $gamename = isset($gamenames['ko'])?$gamenames['ko']:$gamenames['en'];
                            $gametitle = $gamenames['en'];
                        }
                    }
                    $icon = '';
                    if(isset($game['imageUrl']))
                    {
                        $icons = json_decode($game['imageUrl'], true);
                        $icon = isset($icons['en'])?$icons['en']:'';
                    }
                    array_push($gameList, [
                        'provider' => self::GOLD_PROVIDER,
                        'href' => $href,
                        'gamecode' => $game['gameCode'],
                        'symbol' => $game['gameCode'],
                        'name' => preg_replace('/\s+/', '', $gamename),
                        'title' => $gametitle,
                        'type' => 'table',
                        'icon' => $icon,
                        'view' => 0
                    ]);
                }
                //add casino lobby
                array_push($gameList, [
                    'provider' => self::GOLD_PROVIDER,
                    'href' => $href,
                    'gamecode' => $category['thirdname'],
                    'symbol' => 'gclobby',
                    'name' => 'Lobby',
                    'title' => 'Lobby',
                    'type' => 'table',
                    'icon' => '/frontend/Default/ico/gold/EVOLUTION_Lobby.jpg',
                    'view' => 1
                ]);
            }

            //add Unknown Game item
            array_push($gameList, [
                'provider' => self::GOLD_PROVIDER,
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

            $params = [
                'method' => 'GetGameUrl',
                'userCode' => self::GOLD_PROVIDER  . sprintf("%04d",$user->id),
                'nickname' => self::GOLD_PROVIDER  . sprintf("%04d",$user->id),
                "vendorCode" =>  $gamecode,
                "language" =>  'ko'
            ];

            $data = GOLDController::sendRequest($params);
            $url = null;

            if ($data && $data['status'] == 0)
            {
                $url = $data['launchUrl'];
            }

            return $url;
        }
        
        public static function withdrawAll($href, $user)
        {
            $balance = GOLDController::getuserbalance($href,$user);
            if ($balance < 0)
            {
                return ['error'=>true, 'amount'=>$balance, 'msg'=>'getuserbalance return -1'];
            }
            if ($balance > 0)
            {
                $params = [
                    'method' => 'Withdraw',
                    'userCode' => self::GOLD_PROVIDER  . sprintf("%04d",$user->id),
                    "amount" =>  $balance,
                ];

                $data = GOLDController::sendRequest($params);
                if ($data==null || $data['status'] != 0)
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
                Log::error('GOLDMakeLink : Does not find user ' . $userid);
                return null;
            }

            $game = GOLDController::getGameObj($gamecode);
            if ($game == null)
            {
                Log::error('GOLDMakeLink : Game not find  ' . $gamecode);
                return null;
            }
            $user_code = self::GOLD_PROVIDER  . sprintf("%04d",$user->id);
            $balance = 0;

            //유저정보 조회
            $data = GOLDController::moneyInfo($user_code);
            if($data == null || $data['status'] != 0)
            {
                //새유저 창조
                $params = [
                    'method' => 'CreateUser',
                    'userCode' => $user_code,
                ];

                $data = GOLDController::sendRequest($params);
                if ($data==null || $data['status'] != 0)
                {
                    return null;
                }
            }
            else
            {
                $balance = -1;
                $users = $data['users'];
                foreach($users as $useritem)
                {
                    if($useritem['userCode'] == $user_code)
                    {
                        $balance = intval($useritem['balance']);
                        break;
                    }
                }
                if ($balance == -1)
                {
                    return null;
                }
            }

            if ($balance != $user->balance)
            {
                //withdraw all balance
                $data = GOLDController::withdrawAll($game['href'], $user);
                if ($data['error'])
                {
                    return null;
                }
                //Add balance

                if ($user->balance > 0)
                {
                    $params = [
                        'method' => 'Deposit',
                        'userCode' => $user_code,
                        "amount" =>  intval($user->balance),
                    ];
    
                    $data = GOLDController::sendRequest($params);
                    if ($data==null || $data['status'] != 0)
                    {
                        Log::error('GOLDMakeLink : addMemberPoint result failed. ' . ($data==null?'null':json_encode($data)));
                        return null;
                    }
                    
                }
            }
            
            return '/followgame/gold/'.$gamecode;

        }

        public static function getgamelink($gamecode)
        {
            if (isset(self::GOLD_GAME_IDENTITY[$gamecode]))
            {
                $gamelist = GOLDController::getgamelist($gamecode);
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
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [GOLDController::GOLD_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($lastid)
        {
            
            $params = [
                'method' => 'ReportById',
                'startWagerId' => $lastid,
                'count' => 1000
            ];

            $data = GOLDController::sendRequest($params);

            return $data;
        }

        public static function processGameRound($frompoint=-1, $checkduplicate=false)
        {
            $timepoint = 0;
            if ($frompoint == -1)
            {
                $tpoint = \App\Models\Settings::where('key', self::GOLD_PROVIDER . 'timepoint')->first();
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
            $data = GOLDController::gamerounds($timepoint);
            if ($data == null || $data['status'] != 0)
            {
                if ($frompoint == -1)
                {

                    if ($tpoint)
                    {
                        $tpoint->update(['value' => $newtimepoint]);
                    }
                    else
                    {
                        \App\Models\Settings::create(['key' => self::GOLD_PROVIDER .'timepoint', 'value' => $newtimepoint]);
                    }
                }

                return [0,0];
            }
            $invalidRounds = [];
            if (isset($data['wagers']))
            {
                foreach ($data['wagers'] as $round)
                {
                    // if ($round['txn_type'] != 'CREDIT')
                    // {
                    //     continue;
                    // }

                    $gameObj = self::getGameObj($round['gameCode']);
                    
                    if (!$gameObj)
                    {
                        $gameObj = self::getGameObj($round['vendorCode']);
                        if (!$gameObj)
                        {
                            Log::error('GOLD Game could not found : '. $round['gameCode']);
                            continue;
                        }
                    }
                    $bet = $round['betAmount'];
                    $win = $round['payoutAmount'];
                    $balance = $round['afterBalance'];
                    
                    $time = date('Y-m-d H:i:s',strtotime($round['createdOn'] . ' +9 hours'));

                    $userid = intval(preg_replace('/'. self::GOLD_PROVIDER  .'(\d+)/', '$1', $round['userCode'])) ;
                    if($userid == 0){
                        $userid = intval(preg_replace('/'. self::GOLD_PROVIDER . 'user' .'(\d+)/', '$1', $round['userCode'])) ;
                    }

                    $shop = \App\Models\ShopUser::where('user_id', $userid)->first();
                    $category = \App\Models\Category::where('href', $gameObj['href'])->first();

                    // if ($checkduplicate)
                    // {
                        $checkGameStat = \App\Models\StatGame::where([
                            'user_id' => $userid, 
                            'bet' => $bet, 
                            'win' => $win, 
                            'date_time' => $time,
                            'roundid' => $round['vendorCode'] . '#' . $round['gameCode'] . '#' . $round['wagerId'],
                        ])->first();
                        if ($checkGameStat)
                        {
                            continue;
                        }
                    // }
                    $gamename = $gameObj['name'] . '_gold';
                    if($round['status'] == 2)  // 취소처리된 경우
                    {
                        $gamename = $gameObj['name'] . '_gold[C'.$ctime.']_' . $gameObj['href'];
                        // $win = $bet;
                    }
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
                        'roundid' => $round['vendorCode'] . '#' . $round['gameCode'] . '#' . $round['wagerId'],
                    ]);
                    $count = $count + 1;
                }
            }
            if($data['lastWagerId'] > -1){
                $timepoint = $data['lastWagerId'] + 1;
            }

            if ($frompoint == -1)
            {

                if ($tpoint)
                {
                    $tpoint->update(['value' => $timepoint]);
                }
                else
                {
                    \App\Models\Settings::create(['key' => self::GOLD_PROVIDER .'timepoint', 'value' => $timepoint]);
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
            $wager_id = $betrounds[2];
            
            $params = [
                'method' => 'GetWagerInfo',
                'wagerId' => $wager_id
            ];

            $data = GOLDController::sendRequest($params);
            if ($data==null || $data['status'] != 0)
            {
                return null;
            }


            $betdetails = null;
            $gametype = 'gold';
            $result = null;
            if($data['wager'])
            {
                if($data['wager']['detail'])
                {
                    $betdetails = json_decode($bet['detail'], true);
                }
                else
                {
                    $betdetails = [];
                }
                $betdetails['betType'] = ''; //$bet['type'];
                $betdetails['bet_money'] = $bet['betAmount'];
                $betdetails['win_money'] = $bet['payoutAmount'];
                $betdetails = json_encode($betdetails);
            }
            // foreach ($data['wager'] as $bet)
            // {
            //     if ($bet['txn_type'] == 'CREDIT' && $bet['txn_no'] == $txn_no)
            //     {
            //         $gametype = $bet['type'];
            //         $betdetails = json_decode($bet['detail'], true);
            //         $betdetails['betType'] = $bet['type'];
            //         $betdetails['bet_money'] = $bet['bet_money'];
            //         $betdetails['win_money'] = $bet['win_money'];
            //         $betdetails = json_encode($betdetails);
            //         break;
            //     }
            // }

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

            $params = [
                'method' => 'GetAgentInfo'
            ];

            $data = GOLDController::sendRequest($params);
            if ($data==null || $data['status'] != 0)
            {
                return null;
            }
            if ($data && $data['status'] == 0)
            {
                $balance = $data['balance'];
            }
            return intval($balance);
        }


    }

}
