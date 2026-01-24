<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class XMXController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const XMX_PROVIDER = 'xmx';
        const XMX_PP_HREF = 'xmx-pp';
        const XMX_GAME_IDENTITY = [
            'xmx-bbtec' => 6,
            'xmx-pp' => 8,
            'xmx-isoft' => 10,
            'xmx-star' => 11,
            'xmx-pgsoft' => 14,
            'xmx-bbin' => 15,
            'xmx-rtg' => 19,
            'xmx-mg' => 21,
            'xmx-cq9' => 23,
            'xmx-hbn' => 24,
            'xmx-playstar' => 29,
            'xmx-gameart' => 30,
            'xmx-ttg' => 32,
            'xmx-genesis' => 33,
            'xmx-tpg' => 34,
            'xmx-playson' => 36,
            'xmx-bng' => 37,
            'xmx-evoplay' => 40,
            'xmx-dreamtech' => 41,
            'xmx-ag' => 44,
            'xmx-theshow' => 47,
            'xmx-png' => 51,
        ];
        const XMX_IDENTITY_GAME = [
            6  => 'xmx-bbtec'      ,
            8  => 'xmx-pp'         ,
            10 => 'xmx-isoft'      ,
            11 => 'xmx-star'       ,
            14 => 'xmx-pgsoft'     ,
            15 => 'xmx-bbin'       ,
            19 => 'xmx-rtg'        ,
            21 => 'xmx-mg'         ,
            23 => 'xmx-cq9'        ,
            24 => 'xmx-hbn'        ,
            29 => 'xmx-playstar'   ,
            30 => 'xmx-gameart'    ,
            32 => 'xmx-ttg'        ,
            33 => 'xmx-genesis'    ,
            34 => 'xmx-tpg'        ,
            36 => 'xmx-playson'    ,
            37 => 'xmx-bng'        ,
            40 => 'xmx-evoplay'    ,
            41 => 'xmx-dreamtech'  ,
            44 => 'xmx-ag'         ,
            47 => 'xmx-theshow'    ,
            51 => 'xmx-png'        ,
        ];

        public static function getGameObj($uuid)
        {
            foreach (XMXController::XMX_IDENTITY_GAME as $ref)
            {
                $gamelist = XMXController::getgamelist($ref);
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

        public static function hashParam($params) {
            if (!$params || count($params) == 0) {
                return null;
            }
    
            $privateKey = config('app.xmx_key', '');
    
            $strParams = '';
            ksort($params);
            foreach ($params as $key => $value) {
                if ($key == 'hash') {
                    continue;
                }
                
                if ($value !== null) {
                    $strParams = "{$strParams}&{$key}={$value}";
                }
            }
    
            $hash = md5($privateKey . trim($strParams, "&"));
    
            return $hash;
        }

        /*
        */

        
        /*
        * FROM CONTROLLER, API
        */

        
        public static function getUserBalance($href, $user) {
            $url = config('app.xmx_api') . '/getAccountBalance';
            $op = config('app.xmx_op');
            $category = XMXController::XMX_GAME_IDENTITY[$href];
    
            $params = [
                'isRenew' => true,
                'operatorID' => $op,
                'thirdPartyCode' => $category,
                'time' => time()*1000,
                'userID' => self::XMX_PROVIDER . sprintf("%04d",$user->id),
                'vendorID' => 0,
            ];
            $balance = -1;

            try {
                $params['hash'] = XMXController::hashParam($params);
        
                $response = Http::asForm()->get($url, $params);
                
                if ($response->ok()) {
                    $res = $response->json();
        
                    if ($res['returnCode'] == 0) {
                        $balance = $res['thirdPartyBalance'];
                    }
                    else
                    {
                        Log::error('XMXgetuserbalance : return failed. ' . $user->id . ':' . $res['description']);
                    }
                }
                else
                {
                    Log::error('XMXgetuserbalance : response is not okay. ' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('XMXgetuserbalance : getAccountBalance Excpetion. exception= ' . $ex->getMessage());
                Log::error('XMXgamerounds : getAccountBalance Excpetion. PARAMS= ' . json_encode($params));
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
            $url = config('app.xmx_api') . '/getGameList';
            $op = config('app.xmx_op');

            $category = XMXController::XMX_GAME_IDENTITY[$href];

            $params = [
                'operatorID' => $op,
                'thirdPartyCode' => $category,
                'time' => time() * 1000,
                'vendorID' => 0,
            ];

            $params['hash'] = XMXController::hashParam($params);
            try {                
                $response = Http::asForm()->get($url, $params);
                if (!$response->ok())
                {
                    return [];
                }
            }
            catch (Exception $ex)
            {
                return [];
            }
            $data = $response->json();
            $gameList = [];
            if ($data['returnCode'] == 0)
            {
                foreach ($data['games'] as $game)
                {
                    if (in_array($href,['xmx-bng','xmx-playson']) && str_contains($game['id'],'_mob'))
                    {
                        continue;
                    }
                    if (strtolower($game['gt']) == 'slot')
                    {
                        array_push($gameList, [
                            'provider' => self::XMX_PROVIDER,
                            'href' => $href,
                            'gamecode' => $game['id'],
                            'enname' => $game['tEN'],
                            'name' => preg_replace('/\s+/', '', $game['tEN']),
                            'title' => $game['tKR'],
                            'icon' => $game['img'],
                            'type' => strtolower($game['gt']),
                            'view' => 1
                        ]);
                    }
                }

                //add Unknown Game item
                array_push($gameList, [
                    'provider' => self::XMX_PROVIDER,
                    'href' => $href,
                    'symbol' => 'Unknown',
                    'gamecode' => $href,
                    'enname' => 'UnknownGame',
                    'name' => 'UnknownGame',
                    'title' => 'UnknownGame',
                    'icon' => '',
                    'type' => 'slot',
                    'view' => 0
                ]);
            }
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
            
        }

        public static function makegamelink($gamecode, $user) 
        {

            $op = config('app.xmx_op');

            //generate session
            
            $params = [
                'operatorID' => $op,
                'userID' => self::XMX_PROVIDER . sprintf("%04d",$user->id),
                'time' => time()*1000,
                'vendorID' => 0,
            ];
            $params['hash'] = XMXController::hashParam($params);

            $url = config('app.xmx_api') . '/generateSession';
            $response = Http::asForm()->get($url, $params);
            if (!$response->ok())
            {
                Log::error('XMXGetLink : Game Session request failed. ' . $response->body());

                return null;
            }
            $data = $response->json();
            if ($data==null || $data['returnCode'] != 0)
            {
                Log::error('XMXGetLink : Game Session result failed. ' . ($data==null?'null':$data['description']));
                return null;
            }
            $session = $data['session'];

            //Create Game link

            $params = [
                'gameID' => $gamecode,
                'lang' => 'kr',
                'operatorID' => $op,
                'session' => $session,
                'time' => time()*1000,
                'vendorID' => 0,
            ];
            $params['hash'] = XMXController::hashParam($params);

            $url = config('app.xmx_api') . '/getGameUrl';
            $response = Http::asForm()->get($url, $params);
            if (!$response->ok())
            {
                Log::error('XMXGetLink : Game url request failed. ' . $response->body());

                return null;
            }
            $data = $response->json();
            if ($data==null || $data['returnCode'] != 0)
            {
                Log::error('XMXGetLink : Game url result failed. ' . ($data==null?'null':$data['description']));
                return null;
            }
            $url = $data['gameUrl'];

            return $url;
        }
        public static function subtractMemberPointAll( $user)
        {

            $op = config('app.xmx_op');
            //subtractMemberPointAll
            $params = [
                'operatorID' => $op,
                'userID' => self::XMX_PROVIDER . sprintf("%04d",$user->id),
                'time' => time()*1000,
                'vendorID' => 0,
            ];
            $balance = 0;
            $params['hash'] = XMXController::hashParam($params);
            try {
                $url = config('app.xmx_api') . '/subtractMemberPointAll';
                $response = Http::asForm()->get($url, $params);
                if (!$response->ok())
                {
                    Log::error('XMXWithdraw : subtractMemberPointAll request failed. ' . $response->body());

                    return ['error'=>true, 'amount'=>0, 'msg'=>'response not ok'];
                }
                $data = $response->json();
                if ($data==null || $data['returnCode'] != 0)
                {
                    Log::error('XMXWithdraw : subtractMemberPointAll result failed. PARAMS=' . json_encode($params));
                    Log::error('XMXWithdraw : subtractMemberPointAll result failed. ' . ($data==null?'null':$data['description']));
                    return ['error'=>true, 'amount'=>0, 'msg'=>'data not ok'];
                }
                $balance = $data['memberBalance'];
            }
            catch (\Exception $ex)
            {
                Log::error('XMXWithdraw : subtractMemberPointAll Exception. Exception=' . $ex->getMessage());
                Log::error('XMXWithdraw : subtractMemberPointAll Exception. PARAMS=' . json_encode($params));
                return ['error'=>true, 'amount'=>0, 'msg'=>'exception'];
            }
            return ['error'=>false, 'amount'=>$balance];
        }
        public static function withdrawAll($href, $user)
        {
            $category = XMXController::XMX_GAME_IDENTITY[$href];
            $balance = XMXController::getuserbalance($href, $user);
            if ($balance < 0)
            {
                return ['error'=>true, 'amount'=>$balance, 'msg'=>'getuserbalance return -1'];
            }
            if ($balance > 0)
            {
                $op = config('app.xmx_op');//transferPointG2M
                $params = [
                    'amount' => $balance,
                    'operatorID' => $op,
                    'thirdPartyCode' => $category,
                    'transactionID' => uniqid(self::XMX_PROVIDER),
                    'userID' => self::XMX_PROVIDER . sprintf("%04d",$user->id),
                    'time' => time()*1000,
                    'vendorID' => 0,
                ];
                $params['hash'] = XMXController::hashParam($params);
                try {
                    $url = config('app.xmx_api') . '/transferPointG2M';
                    $response = Http::asForm()->get($url, $params);
                    if (!$response->ok())
                    {
                        Log::error('XMXWithdraw : transferPointG2M request failed. ' . $response->body());

                        return ['error'=>true, 'amount'=>0, 'msg'=>'response not ok'];
                    }
                    $data = $response->json();
                    if ($data==null || $data['returnCode'] != 0)
                    {
                        Log::error('XMXWithdraw : transferPointG2M result failed. PARAMS=' . json_encode($params));
                        Log::error('XMXWithdraw : transferPointG2M result failed. ' . ($data==null?'null':$data['description']));
                        return ['error'=>true, 'amount'=>0, 'msg'=>'data not ok'];
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('XMXWithdraw : transferPointG2M Exception. Exception=' . $ex->getMessage());
                    Log::error('XMXWithdraw : transferPointG2M Exception. PARAMS=' . json_encode($params));
                    return ['error'=>true, 'amount'=>0, 'msg'=>'exception'];
                }

                //subtractMemberPoint
                $params = [
                    'amount' => $balance,
                    'operatorID' => $op,
                    'transactionID' => uniqid(self::XMX_PROVIDER),
                    'userID' => self::XMX_PROVIDER . sprintf("%04d",$user->id),
                    'time' => time()*1000,
                    'vendorID' => 0,
                ];
                $params['hash'] = XMXController::hashParam($params);
                try {
                    $url = config('app.xmx_api') . '/subtractMemberPoint';
                    $response = Http::asForm()->get($url, $params);
                    if (!$response->ok())
                    {
                        Log::error('XMXWithdraw : subtractMemberPoint request failed. ' . $response->body());

                        return ['error'=>true, 'amount'=>0, 'msg'=>'response not ok'];
                    }
                    $data = $response->json();
                    if ($data==null || $data['returnCode'] != 0)
                    {
                        Log::error('XMXWithdraw : subtractMemberPoint result failed. ' . ($data==null?'null':$data['description']));
                        return ['error'=>true, 'amount'=>0, 'msg'=>'data not ok'];
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('XMXWithdraw : subtractMemberPoint Exception. Exception=' . $ex->getMessage());
                    Log::error('XMXWithdraw : subtractMemberPoint Exception. PARAMS=' . json_encode($params));
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
                Log::error('XMXMakeLink : Does not find user ' . $userid);
                return null;
            }

            $game = XMXController::getGameObj($gamecode);
            if ($game == null)
            {
                Log::error('XMXMakeLink : Game not find  ' . $game);
                return null;
            }

            $op = config('app.xmx_op');

            //create ximax account
            $params = [
                'operatorID' => $op,
                'userID' => self::XMX_PROVIDER . sprintf("%04d",$user->id),
                'time' => time()*1000,
                'vendorID' => 0,
                'walletID' =>config('app.xmx_prefix') . sprintf("%04d",$user->id),
            ];
            $params['hash'] = XMXController::hashParam($params);

            $url = config('app.xmx_api') . '/createAccount';
            $response = Http::asForm()->get($url, $params);
            if (!$response->ok())
            {
                Log::error('XMXmakelink : createAccount request failed. ' . $response->body());
                Log::error('XMXmakelink : createAccount PARAM. '.$url.' PARAM=' . json_encode($params));
                return null;
            }
            $data = $response->json();
            if ($data==null || ($data['returnCode'] != 0 && $data['returnCode'] != 23))
            {
                Log::error('XMXmakelink : createAccount result failed. ' . ($data==null?'null':$data['description']));
                return null;
            }


            $balance = XMXController::getuserbalance($game['href'], $user);
            if ($balance == -1)
            {
                return null;
            }

            if ($balance != $user->balance)
            {
                //withdraw all balance
                $data = XMXController::withdrawAll($game['href'], $user);
                if ($data['error'])
                {
                    return null;
                }
                //Add balance

                if ($user->balance > 0)
                {
                    //addMemberPoint
                    $params = [
                        'amount' => $user->balance,
                        'operatorID' => $op,
                        'transactionID' => uniqid(self::XMX_PROVIDER),
                        'userID' => self::XMX_PROVIDER . sprintf("%04d",$user->id),
                        'time' => time()*1000,
                        'vendorID' => 0,
                    ];
                    $params['hash'] = XMXController::hashParam($params);
                    try {
                        $url = config('app.xmx_api') . '/addMemberPoint';
                        $response = Http::asForm()->timeout(30)->get($url, $params);
                        if (!$response->ok())
                        {
                            Log::error('XMXmakelink : addMemberPoint request failed. ' . $response->body());

                            return null;
                        }
                        $data = $response->json();
                        if ($data==null || $data['returnCode'] != 0)
                        {
                            Log::error('XMXmakelink : addMemberPoint result failed. ' . ($data==null?'null':$data['description']));
                            return null;
                        }
                    }
                    catch (\Exception $ex)
                    {
                        Log::error('XMXmakelink : addMemberPoint Exception. exception=' . $ex->getMessage());
                        Log::error('XMXmakelink : addMemberPoint PARAM. PARAM=' . json_encode($params));
                        return null;
                    }

                    //transferM2G
                    $category = XMXController::XMX_GAME_IDENTITY[$game['href']];
                    $params = [
                        'amount' => $user->balance,
                        'operatorID' => $op,
                        'thirdPartyCode' => $category,
                        'transactionID' => uniqid(self::XMX_PROVIDER),
                        'userID' => self::XMX_PROVIDER . sprintf("%04d",$user->id),
                        'time' => time()*1000,
                        'vendorID' => 0,
                    ];
                    $params['hash'] = XMXController::hashParam($params);
                    try 
                    {
                        $url = config('app.xmx_api') . '/transferPointM2G';
                        $response = Http::asForm()->timeout(30)->get($url, $params);
                        if (!$response->ok())
                        {
                            Log::error('XMXmakelink : transferPointM2G request failed. ' . $response->body());

                            return null;
                        }
                        $data = $response->json();
                        if ($data==null || $data['returnCode'] != 0)
                        {
                            Log::error('XMXmakelink : transferPointM2G result failed. ' . ($data==null?'null':$data['description']));
                            return null;
                        }
                    }
                    catch (\Exception $ex)
                    {
                        Log::error('XMXmakelink : transferPointM2G Exception. exception=' . $ex->getMessage());
                        Log::error('XMXmakelink : transferPointM2G PARAM. PARAM=' . json_encode($params));
                        return null;
                    }
                }
            }
            
            return '/followgame/xmx/'.$gamecode;

        }

        public static function getgamelink($gamecode)
        {
            $user = auth()->user();
            // if ($user->playing_game != null) //already playing game.
            // {
            //     return ['error' => true, 'data' => '이미 실행중인 게임을 종료해주세요. 이미 종료했음에도 불구하고 이 메시지가 계속 나타난다면 매장에 문의해주세요.'];
            // }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [XMXController::XMX_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($thirdparty, $pageIdx, $startDate, $endDate)
        {
            
            $op = config('app.xmx_op');
            $params = [
                'endDate' => $endDate,
                'operatorID' => $op,
                'startDate' => $startDate,
                'thirdPartyCode' => $thirdparty,
                'pageSize' => 1000,
                'pageStart' => $pageIdx,
                'time' => time()*1000,
                'vendorID' => 0,
            ];
            $params['hash'] = XMXController::hashParam($params);
            try
            {
                $url = config('app.xmx_api') . '/getBetWinHistoryAll';
                $response = Http::timeout(30)->asForm()->get($url, $params);
                if (!$response->ok())
                {
                    Log::error('XMXgamerounds : getBetWinHistoryAll request failed. PARAMS= ' . json_encode($params));
                    Log::error('XMXgamerounds : getBetWinHistoryAll request failed. ' . $response->body());

                    return null;
                }
                $data = $response->json();
                if ($data==null || $data['returnCode'] != 0)
                {
                    Log::error('XMXgamerounds : getBetWinHistoryAll result failed. PARAMS=' . json_encode($params));
                    Log::error('XMXgamerounds : getBetWinHistoryAll result failed. ' . ($data==null?'null':$data['description']));
                    return null;
                }

                return $data;
            }
            catch (\Exception $ex)
            {
                Log::error('XMXgamerounds : getBetWinHistoryAll Excpetion. exception= ' . $ex->getMessage());
                Log::error('XMXgamerounds : getBetWinHistoryAll Excpetion. PARAMS= ' . json_encode($params));
            }
            return null;
        }

        public static function processGameRound($from='', $to='', $checkduplicate=false)
        {
            $count = 0;

            foreach (XMXController::XMX_GAME_IDENTITY as $catname => $thirdId)
            {
                $category = \App\Models\Category::where([
                    'provider'=> XMXController::XMX_PROVIDER,
                    'href' => $catname,
                    'shop_id' => 0,
                    'site_id' => 0,
                    ])->first();

                if (!$category)
                {
                    continue;
                }
                $roundfrom = '';
                $roundto = '';

                if ($from == '')
                {
                    $roundfrom = date('Y-m-d H:i:s',strtotime('-12 hours'));
                    $lastround = \App\Models\StatGame::where('category_id', $category->original_id)->orderby('date_time', 'desc')->first();
                    if ($lastround)
                    {
                        $d = strtotime($lastround->date_time);
                        if ($d > strtotime("-12 hours"))
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
                    Log::error('XMX processGameRound : '. $roundto . '>' . $roundfrom);
                    return [0, 0];
                }

                do
                {
                    $curend_timeStamp = $start_timeStamp + 3599; // 1hours
                    if ($curend_timeStamp > $end_timeStamp)
                    {
                        $curend_timeStamp = $end_timeStamp;
                    }

                    $curPage = 1;
                    $data = null;
                    do
                    {
                        $data = XMXController::gamerounds($thirdId, $curPage, date('Y-m-d H:i:s', $start_timeStamp), date('Y-m-d H:i:s', $curend_timeStamp));
                        if ($data == null)
                        {
                            Log::error('XMX gamerounds failed : '. date('Y-m-d H:i:s', $start_timeStamp) . '~' . date('Y-m-d H:i:s', $curend_timeStamp));
                            sleep(60);
                            continue;
                        }
                        
                        if (isset($data['totalDataSize']) && $data['totalDataSize'] > 0)
                        {
                            foreach ($data['history'] as $round)
                            {
                                $bet = 0;
                                $win = 0;
                                $gameName = $round['gameID'];
                                if ($catname == 'xmx-cq9')
                                {
                                    if ($round['transType'] == 'BET')
                                    {
                                        continue;
                                    }
                                    $betdata = json_decode($round['history'],true);
                                    $bet = $betdata['bet'];
                                    $win = $betdata['win'];
                                    $balance = $betdata['balance'];
                                }
                                else if ($catname == 'xmx-bng' || $catname == 'xmx-playson')
                                {
                                    if ($round['transType'] == 'WIN')
                                    {
                                        continue;
                                    }

                                    $betdata = json_decode($round['history'],true);
                                    $bet = $betdata['bet']??0;
                                    $win = $betdata['win'];
                                    $balance = $betdata['balance_after'];
                                    if ($bet==0 && $win==0)
                                    {
                                        continue;
                                    }
                                }
                                else if ($catname == 'xmx-hbn')
                                {
                                    if ($round['transType'] == 'BET')
                                    {
                                        continue;
                                    }

                                    $betdata = json_decode($round['history'],true);
                                    $bet = $betdata['stake'];
                                    $win = $betdata['payout'];
                                    $balance = -1;
                                    $gameName = $betdata['gameKeyName'];
                                }
                                else if ($catname == 'xmx-pp')
                                {
                                    if ($round['transType'] == 'WIN')
                                    {
                                        continue;
                                    }

                                    $betdata = explode(',', $round['history']);
                                    $bet = $betdata[9];
                                    $win = $betdata[10];
                                    $balance = -1;
                                }
                                else
                                {
                                    if ($round['transType'] == 'BET')
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
                                $time = $round['transTime'];

                                $userid = intval(preg_replace('/'. self::XMX_PROVIDER .'(\d+)/', '$1', $round['userID'])) ;
                                $shop = \App\Models\ShopUser::where('user_id', $userid)->first();

                                if ($checkduplicate)
                                {
                                    $checkGameStat = \App\Models\StatGame::where([
                                        'user_id' => $userid, 
                                        'bet' => $bet, 
                                        'win' => $win, 
                                        'date_time' => $time,
                                        'roundid' => $round['gameID'] . '_' . $round['roundID'],
                                    ])->first();
                                    if ($checkGameStat)
                                    {
                                        continue;
                                    }
                                }

                                \App\Models\StatGame::create([
                                    'user_id' => $userid, 
                                    'balance' => $balance, 
                                    'bet' => $bet, 
                                    'win' => $win, 
                                    'game' =>$gameName . '_xmx', 
                                    'type' => 'slot',
                                    'percent' => 0, 
                                    'percent_jps' => 0, 
                                    'percent_jpg' => 0, 
                                    'profit' => 0, 
                                    'denomination' => 0, 
                                    'date_time' => $time,
                                    'shop_id' => $shop?$shop->shop_id:0,
                                    'category_id' => isset($category)?$category->id:0,
                                    'game_id' => $catname,
                                    'roundid' => $round['gameID'] . '_' . $round['roundID'],
                                ]);
                                $count = $count + 1;
                            }
                        }
                        $curPage = $curPage + 1;
                    } while ($data!=null && $curPage <= $data['totalPageSize']);

                    $start_timeStamp = $curend_timeStamp;

                }while ($start_timeStamp<$end_timeStamp);

            }
            
            
            return [$count, 0];
        }

        public static function syncpromo()
        {
            $anyuser = \App\Models\User::where('role_id', 1)->whereNull('playing_game')->first();           
            if (!$anyuser)
            {
                return ['error' => true, 'msg' => 'not found any available user.'];
            }
            $op = config('app.xmx_op');
            try{
                $gamelist = XMXController::getgamelist(self::XMX_PP_HREF);
                $len = count($gamelist);
                if ($len > 10) {$len = 10;}
                if ($len == 0)
                {
                    return ['error' => true, 'msg' => 'not found any available game.'];
                }
                $rand = mt_rand(0,$len);
                
                $gamecode = $gamelist[$rand]['gamecode'];
                    //create ximax account
                $params = [
                    'operatorID' => $op,
                    'userID' => self::XMX_PROVIDER . sprintf("%04d",$anyuser->id),
                    'time' => time()*1000,
                    'vendorID' => 0,
                    'walletID' =>config('app.xmx_prefix') . sprintf("%04d",$anyuser->id),
                ];
                $params['hash'] = XMXController::hashParam($params);

                $url = config('app.xmx_api') . '/createAccount';
                $response = Http::asForm()->get($url, $params);
                if (!$response->ok())
                {
                    Log::error('XMXmakelink : createAccount request failed. ' . $response->body());

                    return ['error' => true, 'msg' => 'createAccount failed.'];
                }
                $data = $response->json();
                if ($data==null || ($data['returnCode'] != 0 && $data['returnCode'] != 23))
                {
                    Log::error('XMXmakelink : createAccount result failed. ' . ($data==null?'null':$data['description']));
                    return ['error' => true, 'msg' => 'createAccount failed.'];
                }

                $url = XMXController::makegamelink($gamecode, $anyuser);
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

                    $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/promo/race/winners/?'.$gamecode.'&' . $mgckey , ['latestIdentity' => $raceIds]);
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
            catch (\Exception $ex)
            {
                return ['error' => true, 'msg' => 'server exception.' . $ex->getMessage()];
                
            }
            
        }

    }

}
