<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class KTENController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const KTEN_PROVIDER = 'kten';
        const KTEN_PREFIX = 'kten';
        const KTEN_PP_HREF = 'kten-pp';
        const KTEN_PPVERIFY_PROVIDER = 'vrf';
        const KTEN_GAME_IDENTITY = [
            //==== SLOT ====
            'kten-pp' => ['thirdname' =>'Pragmatic','type' => 'slot'],
            'kten-cq9' => ['thirdname' =>'Cq9','type' => 'slot'],
            'kten-hbn' => ['thirdname' =>'Habanero','type' => 'slot'],
            'kten-playson' => ['thirdname' =>'Playson','type' => 'slot'],
            'kten-bng' => ['thirdname' =>'Booongo','type' => 'slot'],
            'kten-dragon' => ['thirdname' =>'Dragongaming','type' => 'slot'],
            'kten-playstar' => ['thirdname' =>'Playstar','type' => 'slot'],
            'kten-gameart' => ['thirdname' =>'Gameart','type' => 'slot'],
            'kten-dreamtech' => ['thirdname' =>'Dreamtech','type' => 'slot'],
            'kten-mg' => ['thirdname' =>'Microgaming','type' => 'slot'],
            'kten-rtg' => ['thirdname' =>'Rtg','type' => 'slot'],
            'kten-pgsoft' => ['thirdname' =>'Pgs','type' => 'slot'],
            'kten-playngo' => ['thirdname' =>'Playngo','type' => 'slot'],
            'kten-evoplay' => ['thirdname' =>'Evoplay','type' => 'slot'],

            //==== CASINO ====
            'kten-og' => ['thirdname' =>'Og','type' => 'casino'],
            'kten-ppl' => ['thirdname' =>'Pragmatic','type' => 'casino'],
            'kten-mgl' => ['thirdname' =>'Microgaming','type' => 'casino'],
            'kten-dg' => ['thirdname' =>'Dreamgame','type' => 'casino'],
            'kten-asia' => ['thirdname' =>'Ag','type' => 'casino'],
        ];

        public static function getGameObj($uuid)
        {
            foreach (KTENController::KTEN_GAME_IDENTITY as $ref => $value)
            {
                $gamelist = KTENController::getgamelist($ref);
                if ($gamelist)
                {
                    foreach($gamelist as $game)
                    {
                        if ($game['gamecode'] == $uuid)
                        {
                            return $game;
                            break;
                        }
                        if ($ref == 'kten-hbn'){                            
                            if ($game['symbol'] == $uuid)
                            {
                                return $game;
                                break;
                            }
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

        
        public static function getUserBalance($href, $user, $prefix=self::KTEN_PREFIX) {
            $url = config('app.kten_api') . '/api/getAccountBalance';
            $op = config('app.kten_op');
            $token = config('app.kten_key');
    
            $params = [
                'agentId' => $op,
                'token' => $token,
                'time' => time(),
                'userId' => $prefix . sprintf("%04d",$user->id),
            ];
            $balance = -1;

            try {       
                $response = Http::get($url, $params);
                
                if ($response->ok()) {
                    $res = $response->json();
        
                    if ($res['errorCode'] == 0) {
                        $balance = $res['balance'];
                    }
                    else
                    {
                        Log::error('KTENgetuserbalance : return failed. ' . $res['errorCode']);
                    }
                }
                else
                {
                    Log::error('KTENgetuserbalance : response is not okay. ' . json_encode($params) . '===body==' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('KTENgetuserbalance : getAccountBalance Excpetion. exception= ' . $ex->getMessage());
                Log::error('KTENgamerounds : getAccountBalance Excpetion. PARAMS= ' . json_encode($params));
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
            $category = KTENController::KTEN_GAME_IDENTITY[$href];
            $type = $category['type'];
            
            if ($type=='slot')
            {
                $url = config('app.kten_api') . '/api/getGameList';
            }
            elseif ($type=='casino')
            {
                $url = config('app.kten_api') . '/api/getLobbyList';
            }
            $op = config('app.kten_op');
            $token = config('app.kten_key');


            $params = [
                'agentId' => $op,
                'token' => $token,
                'thirdname' => $category['thirdname'],
                'time' => time(),
            ];

    
            $response = Http::get($url, $params);
            if (!$response->ok())
            {
                return [];
            }
            $data = $response->json();
            $gameList = [];

            $pp_exgames = ['cs5moneyroll', 'sc7piggiesai', 'scpandai','SMG_bikiniParty_Microgaming'];

            foreach ($data as $game)
            {
                if (strtolower($game['game_type']) == $type) 
                {
                    $view = 1;
                    if ($href == 'kten-og' && $game['game_id'] != 'ogplus')
                    {
                        $view = 0; // hide other tables
                    }

                    if ($href == 'kten-ppl' && $game['game_id'] != '101')
                    {
                        $view = 0; // hide other tables
                    }
                    if ($href == 'kten-asia' && $game['game_id'] != 'SG_Ag')
                    {
                        $view = 0; // hide other tables
                    }
                    $korname = $game['cp_game_name_kor'];
                    if ($href == 'kten-bng')
                    {
                        $korname = preg_replace('/bng/', '', $korname);
                        $korname = preg_replace('/_/', ' ', $korname);
                    }
                    if ($href == 'kten-playson')
                    {
                        $korname = preg_replace('/pls/', '', $korname);
                        $korname = preg_replace('/_/', ' ', $korname);
                    }

                    if ($korname == '')
                    {
                        $korname = $game['cp_game_name_en'];
                    }
                    if (in_array($game['game_id'], $pp_exgames))
                    {
                        continue;
                    }
                    if ($href == 'kten-hbn'){
                        $symbol = explode('_', $game['game_id'])[0];
                    }else{
                        $symbol = $game['game_id'];
                    }
                    array_push($gameList, [
                        'provider' => self::KTEN_PROVIDER,
                        'href' => $href,
                        'gamecode' => $game['game_id'],
                        'symbol' => $symbol,
                        'enname' => $game['cp_game_name_en'],
                        'name' => preg_replace('/\s+/', '', $game['cp_game_name_en']),
                        'title' => $korname,
                        'icon' => $game['thumbnail'],
                        'type' => (($type=='casino')?'table':'slot'),
                        'view' => $view
                    ]);
                }
            }

            //add Unknown Game item
            array_push($gameList, [
                'provider' => self::KTEN_PROVIDER,
                'href' => $href,
                'symbol' => 'Unknown',
                'gamecode' => $href,
                'enname' => 'UnknownGame',
                'name' => 'UnknownGame',
                'title' => 'UnknownGame',
                'icon' => '',
                'type' => (($type=='casino')?'table':'slot'),
                'view' => 0
            ]);
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
            
        }

        public static function makegamelink($gamecode, $user, $prefix=self::KTEN_PREFIX) 
        {

            $op = config('app.kten_op');
            $token = config('app.kten_key');
            

            //Create Game link

            $params = [
                'gameId' => $gamecode,
                'agentId' => $op,
                'token' => $token,
                'time' => time(),
                'userId' => $prefix . sprintf("%04d",$user->id),
            ];

            $url = config('app.kten_api') . '/api/getGameUrl';
            $response = Http::get($url, $params);
            if (!$response->ok())
            {
                Log::error('KTENGetLink : Game url request failed. status=' . $response->status());
                Log::error('KTENGetLink : Game url request failed. param=' . json_encode($params));

                return null;
            }
            $data = $response->json();
            if ($data==null || $data['errorCode'] != 0)
            {
                Log::error('KTENGetLink : Game url result failed. ' . ($data==null?'null':$data['errorCode']));
                return null;
            }
            $url = $data['gameUrl'];

            return $url;
        }
        
        public static function withdrawAll($href, $user, $prefix=self::KTEN_PREFIX)
        {
            $balance = KTENController::getuserbalance($href,$user,$prefix);
            if ($balance < 0)
            {
                return ['error'=>true, 'amount'=>$balance, 'msg'=>'getuserbalance return -1'];
            }
            if ($balance > 0)
            {
                $op = config('app.kten_op');
                $token = config('app.kten_key');

                $params = [
                    'amount' => $balance,
                    'agentId' => $op,
                    'token' => $token,
                    'transactionID' => uniqid($prefix),
                    'userId' => $prefix . sprintf("%04d",$user->id),
                    'time' => time(),
                ];

                try {
                    $url = config('app.kten_api') . '/api/subtractMemberPoint';
                    $response = Http::get($url, $params);
                    if (!$response->ok())
                    {
                        Log::error('KTENWithdraw : subtractMemberPoint request failed. ' . $response->body());

                        return ['error'=>true, 'amount'=>0, 'msg'=>'response not ok'];
                    }
                    $data = $response->json();
                    if ($data==null || $data['errorCode'] != 0)
                    {
                        Log::error('KTENWithdraw : subtractMemberPoint result failed. PARAMS=' . json_encode($params));
                        Log::error('KTENWithdraw : subtractMemberPoint result failed. ' . ($data==null?'null':$data['errorCode']));
                        return ['error'=>true, 'amount'=>0, 'msg'=>'data not ok'];
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('KTENWithdraw : subtractMemberPoint Exception. Exception=' . $ex->getMessage());
                    Log::error('KTENWithdraw : subtractMemberPoint Exception. PARAMS=' . json_encode($params));
                    return ['error'=>true, 'amount'=>0, 'msg'=>'exception'];
                }
            }
            return ['error'=>false, 'amount'=>$balance];
        }

        public static function makelink($gamecode, $userid)
        {
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('KTENMakeLink : Does not find user ' . $userid);
                return null;
            }

            $game = KTENController::getGameObj($gamecode);
            if ($game == null)
            {
                Log::error('KTENMakeLink : Game not find  ' . $gamecode);
                return null;
            }

            $op = config('app.kten_op');
            $token = config('app.kten_key');

            //check kten account
            $params = [
                'agentId' => $op,
                'token' => $token,
                'userId' => self::KTEN_PREFIX . sprintf("%04d",$user->id),
                'time' => time(),
            ];
            $alreadyUser = 1;
            try
            {

                $url = config('app.kten_api') . '/api/checkUser';
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('KTENmakelink : checkUser request failed. ' . $response->body());
                    return null;
                }
                $data = $response->json();
                if ($data==null || ($data['errorCode'] != 0 && $data['errorCode'] != 4))
                {
                    Log::error('KTENmakelink : checkUser result failed. ' . ($data==null?'null':$data['msg']));
                    return null;
                }
                if ($data['errorCode'] == 4)
                {
                    $alreadyUser = 0;
                }
            }
            catch (\Exception $ex)
            {
                Log::error('KTENcheckuser : checkUser Exception. Exception=' . $ex->getMessage());
                Log::error('KTENcheckuser : checkUser Exception. PARAMS=' . json_encode($params));
                return null;
            }
            if ($alreadyUser == 0){
                //create kten account
                $params = [
                    'agentId' => $op,
                    'token' => $token,
                    'userId' => self::KTEN_PREFIX . sprintf("%04d",$user->id),
                    'time' => time(),
                    'email' => self::KTEN_PREFIX . sprintf("%04d",$user->id) . '@masu.com',
                    'password' => '111111'
                ];
                try
                {

                    $url = config('app.kten_api') . '/api/createAccount';
                    $response = Http::asForm()->post($url, $params);
                    if (!$response->ok())
                    {
                        Log::error('KTENmakelink : createAccount request failed. ' . $response->body());
                        return null;
                    }
                    $data = $response->json();
                    if ($data==null || $data['errorCode'] != 0)
                    {
                        Log::error('KTENmakelink : createAccount result failed. ' . ($data==null?'null':$data['msg']));
                        return null;
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('KTENcheckuser : createAccount Exception. Exception=' . $ex->getMessage());
                    Log::error('KTENcheckuser : createAccount Exception. PARAMS=' . json_encode($params));
                    return null;
                }
            }
            
            $balance = KTENController::getuserbalance(null, $user);
            if ($balance == -1)
            {
                return null;
            }

            if ($balance != $user->balance)
            {
                //withdraw all balance
                $data = KTENController::withdrawAll(null, $user);
                if ($data['error'])
                {
                    return null;
                }
                //Add balance

                if ($user->balance > 0)
                {
                    //addMemberPoint
                    $params = [
                        'amount' => floatval($user->balance),
                        'agentId' => $op,
                        'token' => $token,
                        'transactionID' => uniqid(self::KTEN_PREFIX),
                        'userId' => self::KTEN_PREFIX . sprintf("%04d",$user->id),
                        'time' => time(),
                    ];
                    try {
                        $url = config('app.kten_api') . '/api/addMemberPoint';
                        $response = Http::get($url, $params);
                        if (!$response->ok())
                        {
                            Log::error('KTENmakelink : addMemberPoint request failed. ' . $response->body());

                            return null;
                        }
                        $data = $response->json();
                        if ($data==null || $data['errorCode'] != 0)
                        {
                            Log::error('KTENmakelink : addMemberPoint result failed. ' . ($data==null?'null':$data['errorCode']));
                            return null;
                        }
                    }
                    catch (\Exception $ex)
                    {
                        Log::error('KTENmakelink : addMemberPoint Exception. exception=' . $ex->getMessage());
                        Log::error('KTENmakelink : addMemberPoint PARAM. PARAM=' . json_encode($params));
                        return null;
                    }
                }
            }
            
            return '/followgame/kten/'.$gamecode;

        }

        public static function getgamelink($gamecode)
        {
            if (isset(self::KTEN_GAME_IDENTITY[$gamecode]) && self::KTEN_GAME_IDENTITY[$gamecode]['type']=='casino')
            {
                $gamelist = KTENController::getgamelist($gamecode);
                if (count($gamelist) > 0)
                {
                    $gamecode = $gamelist[0]['gamecode'];
                }
            }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [KTENController::KTEN_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($lastid, $pageIdx)
        {
            
            $op = config('app.kten_op');
            $token = config('app.kten_key');


            $params = [
                'agentId' => $op,
                'token' => $token,
                'pageSize' => 1000,
                'pageStart' => $pageIdx,
                'time' => time(),
                'lastid' => $lastid
            ];
            try
            {
                $url = config('app.kten_api') . '/api/getBetWinHistoryAll';
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('KTENgamerounds : getBetWinHistoryAll request failed. PARAMS= ' . json_encode($params));
                    Log::error('KTENgamerounds : getBetWinHistoryAll request failed. ' . $response->body());

                    return null;
                }
                $data = $response->json();
                if ($data==null || $data['errorCode'] != 0)
                {
                    Log::error('KTENgamerounds : getBetWinHistoryAll result failed. PARAMS=' . json_encode($params));
                    Log::error('KTENgamerounds : getBetWinHistoryAll result failed. ' . ($data==null?'null':$data['errorCode']));
                    return null;
                }

                return $data;
            }
            catch (\Exception $ex)
            {
                Log::error('KTENgamerounds : getBetWinHistoryAll Excpetion. exception= ' . $ex->getMessage());
                Log::error('KTENgamerounds : getBetWinHistoryAll Excpetion. PARAMS= ' . json_encode($params));
            }
            return null;
        }

        public static function processGameRound($frompoint=-1, $checkduplicate=false)
        {
            $timepoint = 0;
            if ($frompoint == -1)
            {
                $tpoint = \App\Models\Settings::where('key', self::KTEN_PROVIDER . 'timepoint')->first();
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
            $curPage = 1;
            $data = null;
            $newtimepoint = $timepoint;
            $totalCount = 0;
            do
            {
                $data = KTENController::gamerounds($timepoint, $curPage);
                if ($data == null)
                {
                    if ($frompoint == -1)
                    {

                        if ($tpoint)
                        {
                            $tpoint->update(['value' => $newtimepoint]);
                        }
                        else
                        {
                            \App\Models\Settings::create(['key' => self::KTEN_PROVIDER .'timepoint', 'value' => $newtimepoint]);
                        }
                    }

                    return [0,0];
                }

                if (isset($data['totalPageSize']) && $data['totalPageSize'] > 0)
                {
                    foreach ($data['data'] as $round)
                    {
                        $bet = 0;
                        $win = 0;
                        $gameName = $round['gameId'];
                        $thirdname = $round['cp_name'];
                        $gameObj = self::getGameObj($gameName);
                        
                        if ($thirdname == 'Og' && !$gameObj)
                        {
                            $gameName = 'ogplus_' . $gameName;
                            $gameObj = self::getGameObj($gameName);
                        }
                        if ($thirdname == 'Habanero' && !$gameObj)
                        {
                            $gameName = explode('_', $gameName)[0];
                            $gameObj = self::getGameObj($gameName);
                        }
                        
                        if (!$gameObj)
                        {
                            
                            
                            foreach (KTENController::KTEN_GAME_IDENTITY as $ref => $value)
                            {
                                if ($value['thirdname'] == $thirdname)
                                {
                                    $gameObj = self::getGameObj($ref);
                                    break;
                                }
                            }
                            if (!$gameObj)
                            {
                                Log::error('KTEN Game could not found : '. $thirdname . ' -' . $gameName);
                                continue;
                            }
                        }
                        //only check thirdname
                        if (self::KTEN_GAME_IDENTITY[$gameObj['href']]['thirdname'] != $thirdname)
                        {
                            Log::error('KTEN Game category is mismatched : '. self::KTEN_GAME_IDENTITY[$gameObj['href']]['thirdname'] . ' -' . $thirdname);
                        }


                        $type = $gameObj['type'];
                        $catname = $gameObj['href'];
                        if ($catname == 'kten-cq9')
                        {
                            if ($round['type'] == 'WIN')
                            {
                                continue;
                            }
                            $betdata = json_decode($round['details'],true);
                            $bet = $betdata['bet'];
                            $win = $betdata['win'];
                            $balance = $betdata['balance'];
                        }
                        else if ($catname == 'kten-bng' || $catname == 'kten-playson')
                        {
                            if ($round['type'] == 'WIN')
                            {
                                continue;
                            }
                            $betdata = json_decode($round['details'],true);
                            $bet = $betdata['bet']??0;
                            $win = $betdata['win'];
                            $balance = $betdata['balance_after'];
                        }
                        else if ($catname == 'kten-hbn')
                        {
                            if ($round['type'] == 'WIN')
                            {
                                continue;
                            }
                            if (!isset($round['details']))
                            {
                                Log::error('KTEN HBN round : '. json_encode($round));
                                continue;
                            }

                            $betdata = json_decode($round['details'],true);
                            $bet = $betdata['Stake'];
                            $win = $betdata['Payout'];
                            $balance = $betdata['BalanceAfter'];
                        }
                        // else if ($catname == 'kten-playngo')
                        // {
                        //     if ($round['type'] == 'WIN')
                        //     {
                        //         continue;
                        //     }
                        //     if (!isset($round['details']))
                        //     {
                        //         Log::error('KTEN PLAYNGO round : '. json_encode($round));
                        //         continue;
                        //     }

                        //     $betdata = json_decode($round['details'],true);
                        //     if (!$betdata)
                        //     {
                        //         Log::error('KTEN PLAYNGO round : '. json_encode($round));
                        //         continue;
                        //     }
                        //     $bet = $betdata['RoundLoss'];
                        //     $win = $betdata['Amount'];
                        //     $balance = $betdata['Balance'];
                        // }
                        // else if ($catname == 'kten-pp')
                        // {
                        //     if ($round['type'] == 'WIN')
                        //     {
                        //         continue;
                        //     }
                        //     if (!isset($round['details']))
                        //     {
                        //         Log::error('KTEN PP round has no details : '. json_encode($round));
                        //         continue;
                        //     }

                        //     $betdata = json_decode($round['details'],true);
                        //     $bet = intval($betdata['bet']);
                        //     $win = intval($betdata['win']);
                        //     $balance = intval($betdata['balance']);
                        // }
                        else if ($catname == 'kten-og')
                        {
                            if ($round['type'] == 'WIN')
                            {
                                continue;
                            }

                            $betdata = json_decode($round['details'],true);
                            if (!$betdata)
                            {
                                Log::error('KTEN OG round : '. json_encode($round));
                                continue;
                            }
                            $bet = $betdata['bettingamount'];
                            $win = $bet + $betdata['winloseamount'];
                            $balance = $betdata['balance'];
                        }
                        else
                        {
                            if ($round['type'] == 'BET')
                            {
                                $bet = $round['amount'];
                            }
                            else
                            {
                                $win = $round['amount'];
                            }

                            $balance = -1;
                        }
                        if (is_null($win))
                        {
                            $win = 0;
                        }
                        if ($bet==0 && $win==0)
                        {
                            continue;
                        }
                        $time = $round['trans_time'];

                        $userid = intval(preg_replace('/'. self::KTEN_PREFIX .'(\d+)/', '$1', $round['mem_id'])) ;
                        if ($userid == 0)
                        {
                            $userid = intval(preg_replace('/'. self::KTEN_PPVERIFY_PROVIDER .'(\d+)/', '$1', $round['mem_id'])) ;
                            continue;
                        }
                        $shop = \App\Models\ShopUser::where('user_id', $userid)->first();
                        $category = \App\Models\Category::where('href', $catname)->first();

                        // if ($checkduplicate)
                        // {
                            $checkGameStat = \App\Models\StatGame::where([
                                'user_id' => $userid, 
                                'bet' => $bet, 
                                'win' => $win, 
                                'date_time' => $time,
                                'roundid' => $round['gameId'] . '#' . $round['roundID'] . '#' . $round['id'],
                            ])->first();
                            if ($checkGameStat)
                            {
                                continue;
                            }
                        // }
                        
                        \App\Models\StatGame::create([
                            'user_id' => $userid, 
                            'balance' => $balance, 
                            'bet' => $bet, 
                            'win' => $win, 
                            'game' =>$gameObj['name'] . '_kten', 
                            'type' => $type,
                            'percent' => 0, 
                            'percent_jps' => 0, 
                            'percent_jpg' => 0, 
                            'profit' => 0, 
                            'denomination' => 0, 
                            'date_time' => $time,
                            'shop_id' => $shop?$shop->shop_id:-1,
                            'category_id' => $category?$category->original_id:0,
                            'game_id' =>  $gameObj['gamecode'],
                            'roundid' => $round['gameId'] . '#' . $round['roundID'] . '#' . $round['id'],
                        ]);
                        $count = $count + 1;
                    }
                    if (isset($data['lastid']))
                    {
                        $newtimepoint = $data['lastid'];
                    }
                    

                }
                $curPage = $curPage + 1;
                if (isset($data['totalPageSize']))
                {
                    $totalPage = $data['totalPageSize'];
                }
            }
            while ($curPage <= $totalPage);

            $timepoint = $newtimepoint;

            if ($frompoint == -1)
            {

                if ($tpoint)
                {
                    $tpoint->update(['value' => $timepoint]);
                }
                else
                {
                    \App\Models\Settings::create(['key' => self::KTEN_PROVIDER .'timepoint', 'value' => $timepoint]);
                }
            }
           
            return [$count, $timepoint];
        }


        public static function getAgentBalance()
        {

            $op = config('app.kten_op');
            $token = config('app.kten_key');

            //check kten account
            $params = [
                'agentId' => $op,
                'token' => $token,
                'time' => time(),
            ];

            try
            {

                $url = config('app.kten_api') . '/api/getAgentAccountBalance';
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('KTENAgentBalance : agentbalance request failed. ' . $response->body());
                    return -1;
                }
                $data = $response->json();
                if (($data==null) || ($data['errorCode'] != 0))
                {
                    Log::error('KTENAgentBalance : agentbalance result failed. ' . ($data==null?'null':$data['msg']));
                    return -1;
                }
                return $data['balance'];
            }
            catch (\Exception $ex)
            {
                Log::error('KTENAgentBalance : agentbalance Exception. Exception=' . $ex->getMessage());
                Log::error('KTENAgentBalance : agentbalance Exception. PARAMS=' . json_encode($params));
                return -1;
            }

        }

        public static function getgamedetail(\App\Models\StatGame $stat)
        {
            $betrounds = explode('#',$stat->roundid);
            if (count($betrounds) < 3)
            {
                return null;
            }
            $betId = $betrounds[2];
            
            $data = KTENController::gamerounds($betId, 1);
            if ($data==null || $data['errorCode'] != 0)
            {
                return null;
            }


            $betdetails = null;
            $gametype = 'slot';
            $result = null;
            foreach ($data['data'] as $bet)
            {
                if (isset($bet['id']) && ($bet['id'] == $betId))
                {
                    $gametype = $bet['gameType'];
                    $betdetails = $bet['details'];
                    break;
                }
            }

            return [
                'type' => $gametype,
                'result' => $result,
                'bets' => $betdetails,
                'stat' => $stat
            ];
        }

        public static function syncpromo()
        {
            $user = \App\Models\User::where('role_id', 1)->whereNull('playing_game')->first();           
            if (!$user)
            {
                return ['error' => true, 'msg' => 'not found any available user.'];
            }
            $gamelist = KTENController::getgamelist(self::KTEN_PP_HREF);
            $len = count($gamelist);
            if ($len > 10) {$len = 10;}
            if ($len == 0)
            {
                return ['error' => true, 'msg' => 'not found any available game.'];
            }
            $rand = mt_rand(0,$len);
                
            $gamecode = $gamelist[$rand]['gamecode'];
            $op = config('app.kten_op');
            $token = config('app.kten_key');

            //check kten account
            $params = [
                'agentId' => $op,
                'token' => $token,
                'userId' => self::KTEN_PREFIX . sprintf("%04d",$user->id),
                'time' => time(),
            ];
            $alreadyUser = 1;
            try
            {

                $url = config('app.kten_api') . '/api/checkUser';
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('KTENmakelink : checkUser request failed. ' . $response->body());
                    return ['error' => true, 'msg' => 'checkUser failed.'];
                }
                $data = $response->json();
                if ($data==null || ($data['errorCode'] != 0 && $data['errorCode'] != 4))
                {
                    Log::error('KTENmakelink : checkUser result failed. ' . ($data==null?'null':$data['msg']));
                    return ['error' => true, 'msg' => 'checkUser failed.'];
                }
                if ($data['errorCode'] == 4)
                {
                    $alreadyUser = 0;
                }
            }
            catch (\Exception $ex)
            {
                Log::error('KTENcheckuser : checkUser Exception. Exception=' . $ex->getMessage());
                Log::error('KTENcheckuser : checkUser Exception. PARAMS=' . json_encode($params));
                return ['error' => true, 'msg' => 'checkUser failed.'];
            }
            if ($alreadyUser == 0){
                //create kten account
                $params = [
                    'agentId' => $op,
                    'token' => $token,
                    'userId' => self::KTEN_PREFIX . sprintf("%04d",$user->id),
                    'time' => time(),
                    'email' => self::KTEN_PREFIX . sprintf("%04d",$user->id) . '@masu.com',
                    'password' => '111111'
                ];
                try
                {

                    $url = config('app.kten_api') . '/api/createAccount';
                    $response = Http::asForm()->post($url, $params);
                    if (!$response->ok())
                    {
                        Log::error('KTENmakelink : createAccount request failed. ' . $response->body());
                        return ['error' => true, 'msg' => 'createAccount failed.'];
                    }
                    $data = $response->json();
                    if ($data==null || $data['errorCode'] != 0)
                    {
                        Log::error('KTENmakelink : createAccount result failed. ' . ($data==null?'null':$data['msg']));
                        return ['error' => true, 'msg' => 'createAccount failed.'];
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('KTENcheckuser : createAccount Exception. Exception=' . $ex->getMessage());
                    Log::error('KTENcheckuser : createAccount Exception. PARAMS=' . json_encode($params));
                    return ['error' => true, 'msg' => 'createAccount failed.'];
                }
            }

            $url = KTENController::makegamelink($gamecode, $user);
            if ($url == null)
            {
                return ['error' => true, 'msg' => 'game link error '];
            }

            $parse = parse_url($url);
            $ppgameserver = $parse['scheme'] . '://' . $parse['host'];
        
            //emulate client
            $response = Http::withOptions(['allow_redirects' => false,'proxy' => config('app.ppproxy')])->get($url);
            if ($response->status() == 302)
            {
                $location = $response->header('location');
                $keys = explode('&', $location);
                $mgckey = null;
                foreach ($keys as $key){
                    if (str_contains( $key, 'mgckey='))
                    {
                        $mgckey = $key;
                    }
                    if (str_contains($key, 'symbol='))
                    {
                        $gamecode = $key;
                    }
                }
                if (!$mgckey){
                    return ['error' => true, 'msg' => 'could not find mgckey value'];
                }
                
                $promo = \App\Models\PPPromo::take(1)->first();
                if (!$promo)
                {
                    $promo = \App\Models\PPPromo::create();
                }
                $raceIds = [];
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/promo/active/?'.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->active = $response->body();
                    $json_data = $response->json();
                    if (isset($json_data['races']))
                    {
                        foreach ($json_data['races'] as $race)
                        {
                            $raceIds[$race['id']] = null;
                        }
                    }
                }
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/promo/tournament/details/?'.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->tournamentdetails = $response->body();
                }
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/promo/race/details/?'.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->racedetails = $response->body();
                }
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/promo/tournament/v3/leaderboard/?'.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->tournamentleaderboard = $response->body();
                }
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/promo/race/prizes/?'.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->raceprizes = $response->body();
                }
                
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->post($ppgameserver . '/gs2c/promo/race/v2/winners/?'.$gamecode.'&' . $mgckey, ['latestIdentity' => $raceIds]) ;
                if ($response->ok())
                {
                    $promo->racewinners = $response->body();
                }

                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/minilobby/games?' . $mgckey );
                if ($response->ok())
                {
                    $json_data = $response->json();
                    //disable not own games
                    $ownCats = \App\Models\Category::where(['href'=> 'pragmatic', 'shop_id'=>0,'site_id'=>0])->first();
                    $gIds = $ownCats->games->pluck('game_id')->toArray();
                    $ownGames = \App\Models\Game::whereIn('id', $gIds)->where('view',1)->get();

                    $lobbyCats = $json_data['lobbyCategories'];
                    $filteredCats = [];
                    foreach ($lobbyCats as $cat)
                    {
                        $lobbyGames = $cat['lobbyGames'];
                        $filteredGames = [];
                        foreach ($lobbyGames as $game)
                        {
                            foreach ($ownGames as $og)
                            {
                                if ($og->label == $game['symbol'])
                                {
                                    $filteredGames[] = $game;
                                    break;
                                }
                            }
                        }
                        $cat['lobbyGames'] = $filteredGames;
                        $filteredCats[] = $cat;
                    }
                    $json_data['lobbyCategories'] = $filteredCats;
                    $json_data['gameLaunchURL'] = "/gs2c/minilobby/start";
                    $promo->games = json_encode($json_data);
                }

                $promo->save();
                return ['error' => false, 'msg' => 'synchronized successfully.'];
            }
            else
            {
                return ['error' => true, 'msg' => 'server response is not 302.'];
            }          
            
        }
        public function ppverify($gamecode, \Illuminate\Http\Request $request){
            set_time_limit(0);
            $failed_url = '/gs2c/session/play-verify/'. $gamecode .'?lang=ko&mgckey=AUTHTOKEN@1788e7bf943b9d54907c1b8c7211c4307b9be071e1501f0b09a6b069b6e58~stylename@' . self::KTEN_PPVERIFY_PROVIDER.'-'.self::KTEN_PPVERIFY_PROVIDER . '~SESSION@d016e80c-2a3d-4e6a-b2a9-43b61b0c98dc~SN@5e05f7a7';
            $user = \Auth()->user();
            $op = config('app.kten_op');
            $token = config('app.kten_key');
            if($user == null){
                $this->ppverifyLog($gamecode, '', 'There is no user');
                return redirect($failed_url); 
            }
            //check kten account
            $params = [
                'agentId' => $op,
                'token' => $token,
                'userId' => self::KTEN_PPVERIFY_PROVIDER . sprintf("%04d",$user->id),
                'time' => time(),
            ];
            $alreadyUser = 1;
            try
            {

                $url = config('app.kten_api') . '/api/checkUser';
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    $this->ppverifyLog($gamecode, $user->id, 'KTENmakelink : checkUser request failed. ' . $response->body());
                    return redirect($failed_url);
                }
                $data = $response->json();
                if ($data==null || ($data['errorCode'] != 0 && $data['errorCode'] != 4))
                {
                    $this->ppverifyLog($gamecode, $user->id, 'KTENmakelink : checkUser result failed. ' . ($data==null?'null':$data['msg']));
                    return redirect($failed_url);
                }
                if ($data['errorCode'] == 4)
                {
                    $alreadyUser = 0;
                }
            }
            catch (\Exception $ex)
            {
                $this->ppverifyLog($gamecode, $user->id, 'KTENcheckuser : checkUser Exception. Exception=' . $ex->getMessage() . '. PARAMS=' . json_encode($params));
                return redirect($failed_url);
            }
            if ($alreadyUser == 0){
                //create kten account
                $params = [
                    'agentId' => $op,
                    'token' => $token,
                    'userId' => self::KTEN_PPVERIFY_PROVIDER . sprintf("%04d",$user->id),
                    'time' => time(),
                    'email' => self::KTEN_PPVERIFY_PROVIDER . sprintf("%04d",$user->id) . '@masu.com',
                    'password' => '111111'
                ];
                try
                {

                    $url = config('app.kten_api') . '/api/createAccount';
                    $response = Http::asForm()->post($url, $params);
                    if (!$response->ok())
                    {
                        $this->ppverifyLog($gamecode, $user->id, 'KTENmakelink : createAccount request failed. ' . $response->body());
                        return redirect($failed_url);
                    }
                    $data = $response->json();
                    if ($data==null || $data['errorCode'] != 0)
                    {
                        $this->ppverifyLog($gamecode, $user->id, 'KTENmakelink : createAccount result failed. ' . ($data==null?'null':$data['msg']));
                        return redirect($failed_url);
                    }
                }
                catch (\Exception $ex)
                {
                    $this->ppverifyLog($gamecode, $user->id, 'KTENcheckuser : checkUser Exception. Exception=' . $ex->getMessage() . '. PARAMS=' . json_encode($params));
                    return redirect($failed_url);
                }
            }
            
            
            // 유저마지막 베팅로그 얻기
            $verify_log = \App\Models\PPGameVerifyLog::where('user_id', $user->id)->orderby('date_time', 'desc')->first();
            $last_bet_time = null;
            if($verify_log != null){
                $last_bet_time = $verify_log->date_time;
            }
            $pm_category = \App\Models\Category::where('href', 'pragmatic')->first();
            $cat_id = $pm_category->original_id;
            $stat_game = \App\Models\StatGame::where(['user_id' => $user->id, 'category_id' => $cat_id]);
            if($last_bet_time != null){
                $stat_game = $stat_game->where('date_time', '>', $last_bet_time);
            }
            $stat_game = $stat_game->orderby('date_time', 'desc')->first();
            if($stat_game != null){
                $game = \App\Models\Game::where('original_id', $stat_game->game_id)->where('shop_id', $user->shop_id)->first();
                if($game == null){
                    $this->ppverifyLog($gamecode, $user->id, 'there is no gamecode => ' . $gamecode);
                    return redirect($failed_url);
                }else{
                    $gamecode = $game->label;
                }
            }

            if ($stat_game != null)
            {
                //withdraw all balance
                // $data = KTENController::withdrawAll($gamecode, $user, self::KTEN_PPVERIFY_PROVIDER);
                // if ($data['error'])
                // {
                //     $this->ppverifyLog($gamecode, $user->id, 'withdrawAll error');
                //     return redirect($failed_url);
                // }
                //Add balance
                $adduserbalance = $stat_game->bet;   // 유저머니를 베트머니만큼 충전한다.
                if ($adduserbalance > 0)
                {
                    //addMemberPoint
                    $params = [
                        'amount' => floatval($adduserbalance),
                        'agentId' => $op,
                        'token' => $token,
                        'transactionID' => uniqid(self::KTEN_PPVERIFY_PROVIDER),
                        'userId' => self::KTEN_PPVERIFY_PROVIDER . sprintf("%04d",$user->id),
                        'time' => time(),
                    ];
                    try {
                        $url = config('app.kten_api') . '/api/addMemberPoint';
                        $response = Http::get($url, $params);
                        if (!$response->ok())
                        {
                            $this->ppverifyLog($gamecode, $user->id, 'KTENmakelink : addMemberPoint request failed. ' . $response->body());
                            return redirect($failed_url);
                        }
                        $data = $response->json();
                        if ($data==null || $data['errorCode'] != 0)
                        {
                            $this->ppverifyLog($gamecode, $user->id, 'KTENmakelink : addMemberPoint result failed. ' . ($data==null?'null':$data['errorCode']));
                            return redirect($failed_url);
                        }
                    }
                    catch (\Exception $ex)
                    {
                        $this->ppverifyLog($gamecode, $user->id, 'addMemberPoint exception=' . $ex->getMessage() . ', PARAM=' . json_encode($params));
                        return redirect($failed_url);
                    }
                }
            }
            ///////////////////////<--- End --->/////////////////////////

            $url = KTENController::makegamelink($gamecode, $user, self::KTEN_PPVERIFY_PROVIDER);
            if ($url == null)
            {
                $this->ppverifyLog($gamecode, $user->id, 'make game link error');
                return redirect($failed_url);
            }

            $parse = parse_url($url);
            $ppgameserver = $parse['scheme'] . '://' . $parse['host'];
        
            //emulate client
            $response = Http::withOptions(['allow_redirects' => false,'proxy' => config('app.ppproxy')])->get($url);
            if ($response->status() == 302)
            {
                $location = $response->header('location');
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($location);
                $datapath = $ppgameserver . '/gs2c/common/games-html5/games/vs/'. $gamecode . '/';
                $gameservice = $ppgameserver. '/gs2c/ge/v3/gameService';
                if ($response->ok())
                {
                    $content = $response->body();
                    preg_match("/\"datapath\":\"([^\"]*)/", $content, $match);
                    if(!empty($match) && isset($match[1]) && !empty($match[1])){
                        $datapath = $match[1];
                    }
                    preg_match("/\"gameService\":\"([^\"]*)/", $content, $match);
                    if(!empty($match) && isset($match[1]) && !empty($match[1])){
                        $gameservice = $match[1];
                    }
                }
                $keys = explode('&', $location);
                $mgckey = null;
                foreach ($keys as $key){
                    if (str_contains( $key, 'mgckey='))
                    {
                        $mgckey = $key;
                    }
                }
                if (!$mgckey){
                    $this->ppverifyLog($gamecode, $user->id, 'could not find mgckey value');
                    return redirect($failed_url);
                }

                $arr_b_ind_games = ['vs243lionsgold', 'vs10amm', 'vs10egypt', 'vs25asgard', 'vs9aztecgemsdx', 'vs10tut', 'vs243caishien', 'vs243ckemp', 'vs25davinci', 'vs15diamond', 'vs7fire88', 'vs20leprexmas', 'vs20leprechaun', 'vs25mustang', 'vs20santa', 'vs20pistols', 'vs25holiday', 'vs10bbextreme'];
                $arr_b_no_ind_games = ['vs7776secrets', 'vs10txbigbass', 'vs20terrorv', 'vs20drgbless', 'vs5drhs', 'vs20ekingrr', 'vswaysxjuicy', 'vs10goldfish','vs10floatdrg', 'vswaysfltdrg', 'vs20hercpeg', 'vs20honey', 'vs20hburnhs', 'vs4096magician', 'vs9chen', 'vs243mwarrior', 'vs20muertos', 'vs20mammoth', 'vs25peking', 'vswayshammthor', 'vswayslofhero', 'vswaysfrywld', 'vswaysluckyfish', 'vs10egrich', 'vs25rlbank', 'vs40streetracer', 'vs5spjoker', 'vs20superx', 'vs1024temuj', 'vs20doghouse', 'vs20tweethouse', 'vs20amuleteg', 'vs40madwheel', 'vs5trdragons', 'vs10vampwolf', 'vs20vegasmagic', 'vswaysyumyum', 'vs10jnmntzma','vs10kingofdth', 'vswaysrhino', 'vs20xmascarol', 'vswaysaztecking', 'vswaysrockblst','vs20maskgame'];
                $arr_b_gamble_games = ['vs20underground', 'vs40pirgold', 'vs40voodoo', 'vswayswwriches'];

                $cver = 99951;
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($datapath .'desktop/bootstrap.js');
                if ($response->ok())
                {
                    $content = $response->body();
                    preg_match("/UHT_REVISION={common:'(\d+)',desktop:'\d+',mobile/", $content, $match);
                    if(!empty($match) && isset($match[1]) && !empty($match[1])){
                        $cver = $match[1];
                    }
                }
                $data = [
                    'action' => 'doInit',
                    'symbol' => $gamecode,
                    'cver' => $cver,
                    'index' => 1,
                    'counter' => 1,
                    'repeat' => 0,
                    'mgckey' => explode('=', $mgckey)[1]
                ];
                $v_type = 'v3';
                $response = Http::withOptions(['proxy' => config('app.ppproxy')])->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->asForm()->post($gameservice, $data);
                if (!$response->ok())
                {
                    $this->ppverifyLog($gamecode, $user->id, 'doInit request error');
                    return redirect($failed_url);
                }

                if($stat_game != null){
                    $result = PPController::toJson($response->body());
                    $line = (int)$result['l'] ?? 20;
                    $bet = (float)$result['c'] ?? 100;
                    $arr_bets = isset( $result['sc']) ? explode(',', $result['sc']) : [];
                    $bl = $result['bl'] ?? -1;
                    $spinType = $result['na'] ?? 's';
                    $rs_p = $result['rs_p'] ?? -1;
                    $fsmax = $result['fsmax'] ?? -1;
                    $index = $result['index'] ?? 1;
                    $counter = $result['counter'] ?? 2;
                    $bw = -1;
                    $end = -1;
                    $bgt = -1;
                    $ind = 0;
                    $isRespin = false;
                    $pur_ind = -1;
                    $ppgamelog = \App\Models\PPGameLog::where(['user_id' => $user->id, 'game_id'=>$game->id, 'roundid' => $stat_game->roundid])->orderby('id', 'asc')->first();
                    if(!isset($ppgamelog)){
                        return redirect($ppgameserver . '/gs2c/session/verify?lang=ko&'.$mgckey);
                    }
                    $ppgamelog = json_decode($ppgamelog->str);
                    $pur = -1;
                    if(isset($ppgamelog->request) && isset($ppgamelog->request->pur) && $ppgamelog->request->pur >= 0){
                        $pur = $ppgamelog->request->pur;
                    }
                    if(isset($ppgamelog->request) && isset($ppgamelog->request->bl)){
                        $bl = $ppgamelog->request->bl;
                    }
                    if(isset($ppgamelog->request) && isset($ppgamelog->request->c)){
                        $bet = $ppgamelog->request->c;
                    }
                    if(isset($ppgamelog->request) && isset($ppgamelog->request->l)){
                        $line = $ppgamelog->request->l;
                    }
                    if(isset($ppgamelog->request) && isset($ppgamelog->request->ind)){
                        $pur_ind = $ppgamelog->request->ind;
                    }
                    while(true){
                        try
                        {
                            $data = [
                                'action' => 'doSpin',
                                'symbol' => $gamecode,
                                'index' => $index + 1,
                                'counter' => $counter + 1,
                                'repeat' => 0,
                                'mgckey' => explode('=', $mgckey)[1]
                            ];
                            if($spinType != 'b'){
                                $isRespin = false;
                            }
                            if($spinType == 's'){
                                $data['c'] = $bet;
                                $data['l'] = $line;
                                if($pur_ind >= 0){
                                    $data['ind'] = $pur_ind;
                                    if($gamecode != 'vs15godsofwar'){
                                        $pur_ind = -1;
                                    }
                                }
                            }else if($spinType == 'c'){
                                $data['action'] = 'doCollect';
                            }else if($spinType == 'cb'){
                                $data['action'] = 'doCollectBonus';
                            }else if($spinType == 'fso'){
                                $data['action'] = 'doFSOption';
                                $data['ind'] = 0;
                            }else if($spinType == 'm'){
                                $data['action'] = 'doMysteryScatter';
                                if($gamecode == 'vs10bookfallen'){
                                    $data['ind'] = 0;
                                }
                            }else if($spinType == 'go'){
                                $data['action'] = 'doGambleOption';
                                $data['g_a'] = 'stand';
                                $data['g_o_ind'] = -1;
                            }else if($spinType == 'b'){
                                $data['action'] = 'doBonus';
                                if($isRespin == false){
                                    $isRespin = true;
                                    $ind = 0;
                                }else{
                                    $ind++;
                                }
                                if($gamecode == 'vs7pigs'){
                                    $arr_i_pos = isset($result['i_pos']) ? explode(',', $result['i_pos']) : [1,1,1];
                                    $data['ind'] = $arr_i_pos[$ind];
                                }else if($gamecode == 'vs243queenie'){
                                    if($bw != 1){
                                        $data['ind'] = isset($result['wi']) ? ($result['wi'] + 1) : 1;
                                    }
                                }else if(in_array($gamecode, $arr_b_gamble_games)){
                                    $data['ind'] = 1;
                                }else if(in_array($gamecode, $arr_b_no_ind_games)){
                                    $data['ind'] = 0;
                                }else if(in_array($gamecode, $arr_b_ind_games)){
                                    if($bgt == 9 || $bgt == 23){
                                        $data['ind'] = 0;
                                    }else if($bgt == 11 || $bgt == 31){
                                        
                                    }else if($bgt == 21){
                                        if($end == 0){
                                            $data['ind'] = 0;
                                        }
                                    }else{
                                        $data['ind'] = $ind;
                                    }
                                }
                            }
                            if($bl >= 0){
                                $data['bl'] = $bl;
                            }
                            if($pur >= 0){
                                $data['pur'] = $pur;
                            }
                            $response = Http::withOptions(['proxy' => config('app.ppproxy')])->withHeaders([
                                'Accept' => '*/*',
                                'Content-Type' => 'application/x-www-form-urlencoded'
                                ])->asForm()->post($gameservice, $data);
                            if (!$response->ok())
                            {
                                $this->ppverifyLog($gamecode, $user->id, 'doSpin Error, Body => ' . $response->body());
                                if(in_array($bet, $arr_bets) == false){
                                    $this->ppverifyLog($gamecode, $user->id, 'doSpin BetMoney =' . $bet . ', BetList=' . json_encode($arr_bets));
                                }
                                break;
                            }
                            $result = PPController::toJson($response->body());
                            if(count($result) == 0){
                                $this->ppverifyLog($gamecode, $user->id, 'doSpin Error Data=' . json_encode($data));
                                break;
                            }
                            $spinType = $result['na'] ?? 's';
                            $rs_p = $result['rs_p'] ?? -1;
                            $fsmax = $result['fsmax'] ?? -1;
                            $index = $result['index'] ?? 1;
                            $counter = $result['counter'] ?? 2;
                            $bw = $result['bw'] ?? -1;
                            $end = $result['end'] ?? -1;
                            $bgt = $result['bgt'] ?? -1;
                            $pur = -1;
                            if($spinType == 's' && $rs_p == -1 && $fsmax == -1){
                                \App\Models\PPGameVerifyLog::create([
                                    'game_id' => $game->original_id, 
                                    'user_id' => $user->id, 
                                    'bet' => $stat_game->bet
                                ]);
                                sleep(5);
                                break;
                            }
                            
                        }
                        catch (\Exception $ex)
                        {
                            $this->ppverifyLog($gamecode, $user->id, 'doSpin Error Exception=' . $ex->getMessage() . ', Data=' . json_encode($data));
                            break;
                        }
                    }
                }
                return redirect($ppgameserver . '/gs2c/session/verify?lang=ko&'.$mgckey);
            }else{
                Log::error('server response is not 302.');
                return redirect($failed_url);
            }
        }
        public function ppverifyLog($gamecode, $user_id, $msg)
        {
            $strlog = '';
            $strlog .= "\n";
            $strlog .= date("Y-m-d H:i:s") . ' ';
            $strlog .= $user_id . '->' . $gamecode . ' : ' . $msg;
            $strlog .= "\n";
            $strlog .= ' ############################################### ';
            $strlog .= "\n";
            $strinternallog = '';
            if( file_exists(storage_path('logs/') . 'PPVerify.log') ) 
            {
                $strinternallog = file_get_contents(storage_path('logs/') . 'PPVerify.log');
            }
            file_put_contents(storage_path('logs/') . 'PPVerify.log', $strinternallog . $strlog);
        }

    }

}
