<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class SPGController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const SPG_PROVIDER = 'spg';
        const SPG_PP_HREF = 'spg-pp';
        const SPG_GAME_IDENTITY = [
            'spg-pp' => 'Pragmatic',
            'spg-cq9' => 'Cq9',
            'spg-hbn' => 'Habanero',
            'spg-playson' => 'Playson',
            'spg-bng' => 924,
        ];
        const SPG_IDENTITY_GAME = [
            'Pragmatic'  => 'spg-pp',
            'Cq9' => 'spg-cq9'  ,
            'Habanero'  =>  'spg-hbn',
            'Playson' => 'spg-playson'  ,
            924 => 'spg-bng'  ,
        ];

        public static function getGameObj($uuid)
        {
            foreach (SPGController::SPG_IDENTITY_GAME as $ref)
            {
                $gamelist = SPGController::getgamelist($ref);
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

        public static function getHmac(
            string $httpMethod,
            string $uri,
            $contents
            )
        {
            $secret = config('app.spg_secret');
            $apiKey = config('app.spg_key');

            $utf8EncodedContent = '';

            if ($httpMethod === 'GET' || $httpMethod === 'PATCH') {
                $params = "";
                if (is_array($contents)) {
                    foreach($contents as $key => $value) {
                        $params .= (empty($params) ? "?" : '&') . "{$key}={$value}";
                    }
                } else {
                    $params = $contents;
                }
                $uri .= $params;
            } else {
                if (is_array($contents)) {
                    $utf8EncodedContent = json_encode($contents);
                }
            }

            $timestamp = time();
            $nonce = uniqid(self::SPG_PROVIDER);
            $payload = SPGController::buildPayload($httpMethod, $uri, $utf8EncodedContent, $timestamp, $nonce);
            $hash = SPGController::getHmacHash($payload);
            return "Hmac {$apiKey}:{$hash}:{$nonce}:{$timestamp}";
        }

        public static function getHmacHash(string $payload) {
            $secret = config('app.spg_secret');
            return base64_encode(hash_hmac('sha256', $payload, base64_decode($secret), true));
        }

        public static function buildPayload(
            string $httpMethod,
            string $uri,
            string $utf8EncodedContent,
            int $timestamp, 
            string $nonce) {
            
            $apiKey = config('app.spg_key');
            $contentHash = '';

            if (strlen($utf8EncodedContent) > 0) {
                $decoded = utf8_decode($utf8EncodedContent);
                $contentHash = base64_encode(md5($decoded, true));
            }

            $encodedUri = strtolower(urlencode($uri));
            return "{$apiKey}{$httpMethod}{$encodedUri}{$timestamp}{$nonce}{$contentHash}";
        }

        /*
        */

        
        /*
        * FROM CONTROLLER, API
        */

        
        public static function getUserBalance($user) {
            $url = config('app.spg_api') . '/api/User';

            $params = [
                'username' => self::SPG_PROVIDER . sprintf("%04d",$user->id),
            ];
            $balance = -1;

            $hmac = SPGController::getHmac('GET', $url, $params);

            try {       
                $response = Http::withHeaders(['Authorization' => $hmac])->get($url, $params);
                
                if ($response->ok()) {
                    $res = $response->json();
        
                    if (isset($res['balance'])) {
                        $balance = $res['balance'];
                    }
                    else
                    {
                        Log::error('SPGgetuserbalance : return failed. ' . json_encode($res));
                    }
                }
                else
                {
                    Log::error('SPGgetuserbalance : response is not okay. ' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('SPGgetuserbalance : getAccountBalance Excpetion. exception= ' . $ex->getMessage());
                Log::error('SPGgamerounds : getAccountBalance Excpetion. PARAMS= ' . json_encode($params));
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

            $category = SPGController::SPG_GAME_IDENTITY[$href];

            $url = config('app.spg_api') . '/api/game/list/bymerchant/' . $category;


            $params = [
                'merchantId' => $category,
            ];

            $hmac = SPGController::getHmac('GET', $url, $params);
            $response = Http::withHeaders(['Authorization' => $hmac])->get($url, $params);
    
            if (!$response->ok())
            {
                return [];
            }
            $data = $response->json();
            $gameList = [];

            foreach ($data as $game)
            {
                if (in_array("16",$game['categoryID'])) //16 is slot
                {
                    array_push($gameList, [
                        'provider' => self::SPG_PROVIDER,
                        'href' => $category,
                        'gamecode' => $game['pageCode'],
                        'symbol' => $game['pageCode'],
                        'enname' => $game['name']['en'],
                        'name' => preg_replace('/\s+/', '', $game['name']['en']),
                        'title' => $game['name']['ko'],
                        'icon' => $game['imageFullPath'],
                        'type' => "slot",
                        'view' => 1
                    ]);
                }
            }

            //add Unknown Game item
            array_push($gameList, [
                'provider' => self::SPG_PROVIDER,
                'href' => $category,
                'symbol' => 'Unknown',
                'gamecode' => $href,
                'enname' => 'UnknownGame',
                'name' => 'UnknownGame',
                'title' => 'UnknownGame',
                'icon' => '',
                'type' => 'slot',
                'view' => 0
            ]);
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
            
        }

        public static function makegamelink($gamecode, $user) 
        {

            //Make user token
            $url = config('app.spg_api') . '/api/User/token?username=' . self::SPG_PROVIDER . sprintf("%04d",$user->id);
            $params = null;

            
            $token = '30c55f61ba8d47449449b52faf8a6f29';
            // try{
            //     $hmac = SPGController::getHmac('PATCH', $url, $params);
            //     $response = Http::withHeaders(['Authorization' => $hmac])->patch($url);
            //     if ($response->ok()) {
            //         $res = $response->json();
        
            //         if (isset($res['token'])) {
            //             $token = $res['token'];
            //         }
            //         else
            //         {
            //             Log::error('SPGToken : return failed. ' . json_encode($res));
            //             return null;
            //         }
            //     }
            //     else
            //     {
            //         Log::error('SPGToken : response is not okay. ' . $response->body());
            //         return null;
            //     }
            // }
            // catch (\Exception $ex)
            // {
            //     Log::error('SPGToken :  Exception. Exception=' . $ex->getMessage());
            //     Log::error('SPGToken :  Exception. PARAMS=' . json_encode($params));
            //     return null;
            // }

            //Create Game link
            $gameObj = SPGController::getGameObj($gamecode);
            if (!$gameObj)
            {
                return null;
            }
            $url = config('app.spg_api') . '/api/game/url';
            $params = [
                'token' => $token,
                'pageCode' => $gameObj['gamecode'],
                'systemCode' => $gameObj['href'],
            ];
            $gameurl = null;
            try{
                $hmac = SPGController::getHmac('GET', $url, $params);
                $response = Http::withHeaders(['Authorization' => $hmac])->get($url, $params);
                if ($response->ok()) {
                    $res = $response->json();
        
                    if (isset($res['url'])) {
                        $gameurl = $res['url'];
                    }
                    else
                    {
                        Log::error('SPGGameURL : return failed. ' . json_encode($res));
                    }
                }
                else
                {
                    Log::error('SPGGameURL : response is not okay. ' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('SPGGameURL :  Exception. Exception=' . $ex->getMessage());
                Log::error('SPGGameURL :  Exception. PARAMS=' . json_encode($params));
                return null;
            }

            return $gameurl;
        }
        
        public static function withdrawAll($user)
        {
            $balance = SPGController::getuserbalance($user);
            if ($balance < 0)
            {
                return ['error'=>true, 'amount'=>$balance, 'msg'=>'getuserbalance return -1'];
            }
            if ($balance > 0)
            {
                $params = [
                    'amount' => $balance,
                    'username' => self::SPG_PROVIDER . sprintf("%04d",$user->id),
                ];

                try {
                    $url = config('app.spg_api') . '/api/User/debit';
                    $hmac = SPGController::getHmac('POST', $url, $params);
                    $response = Http::withHeaders(['Authorization' => $hmac])->post($url, $params);
                    if (!$response->ok())
                    {
                        Log::error('SPGWithdraw : debit request failed. ' . $response->body());

                        return ['error'=>true, 'amount'=>0, 'msg'=>'response not ok'];
                    }
                    $data = $response->json();
                    if ($data==null)
                    {
                        Log::error('SPGWithdraw : debit result failed. PARAMS=' . json_encode($params));
                        Log::error('SPGWithdraw : debit result failed. ' . ($data==null?'null':$data['errorCode']));
                        return ['error'=>true, 'amount'=>0, 'msg'=>'data not ok'];
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('SPGWithdraw : debit Exception. Exception=' . $ex->getMessage());
                    Log::error('SPGWithdraw : debit Exception. PARAMS=' . json_encode($params));
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
                Log::error('SPGMakeLink : Does not find user ' . $userid);
                return null;
            }

            $game = SPGController::getGameObj($gamecode);
            if ($game == null)
            {
                Log::error('SPGMakeLink : Game not find  ' . $game);
                return null;
            }

            $secret = config('app.spg_op');
            $token = config('app.spg_key');

            //check kten account
            $params = [
                'agentId' => $secret,
                'token' => $token,
                'userId' => self::SPG_PROVIDER . sprintf("%04d",$user->id),
                'time' => time(),
            ];
            $alreadyUser = 1;
            try
            {

                $url = config('app.spg_api') . '/api/checkUser';
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('SPGmakelink : checkUser request failed. ' . $response->body());
                    return null;
                }
                $data = $response->json();
                if ($data==null || ($data['errorCode'] != 0 && $data['errorCode'] != 4))
                {
                    Log::error('SPGmakelink : checkUser result failed. ' . ($data==null?'null':$data['msg']));
                    return null;
                }
                if ($data['errorCode'] == 4)
                {
                    $alreadyUser = 0;
                }
            }
            catch (\Exception $ex)
            {
                Log::error('SPGcheckuser : checkUser Exception. Exception=' . $ex->getMessage());
                Log::error('SPGcheckuser : checkUser Exception. PARAMS=' . json_encode($params));
                return ['error'=>true, 'amount'=>0, 'msg'=>'exception'];
            }
            if ($alreadyUser == 0){
                //create kten account
                $params = [
                    'agentId' => $secret,
                    'token' => $token,
                    'userId' => self::SPG_PROVIDER . sprintf("%04d",$user->id),
                    'time' => time(),
                    'email' => self::SPG_PROVIDER . sprintf("%04d",$user->id) . '@masu.com',
                    'password' => '111111'
                ];
                try
                {

                    $url = config('app.spg_api') . '/api/createAccount';
                    $response = Http::asForm()->post($url, $params);
                    if (!$response->ok())
                    {
                        Log::error('SPGmakelink : createAccount request failed. ' . $response->body());
                        return null;
                    }
                    $data = $response->json();
                    if ($data==null || $data['errorCode'] != 0)
                    {
                        Log::error('SPGmakelink : createAccount result failed. ' . ($data==null?'null':$data['msg']));
                        return null;
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('SPGcheckuser : createAccount Exception. Exception=' . $ex->getMessage());
                    Log::error('SPGcheckuser : createAccount Exception. PARAMS=' . json_encode($params));
                    return ['error'=>true, 'amount'=>0, 'msg'=>'exception'];
                }
            }
            
            $balance = SPGController::getuserbalance($user);
            if ($balance == -1)
            {
                return null;
            }

            if ($balance != $user->balance)
            {
                //withdraw all balance
                $data = SPGController::withdrawAll($user);
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
                        'agentId' => $secret,
                        'token' => $token,
                        'transactionID' => uniqid(self::SPG_PROVIDER),
                        'userId' => self::SPG_PROVIDER . sprintf("%04d",$user->id),
                        'time' => time(),
                    ];
                    try {
                        $url = config('app.spg_api') . '/api/addMemberPoint';
                        $response = Http::get($url, $params);
                        if (!$response->ok())
                        {
                            Log::error('SPGmakelink : addMemberPoint request failed. ' . $response->body());

                            return null;
                        }
                        $data = $response->json();
                        if ($data==null || $data['errorCode'] != 0)
                        {
                            Log::error('SPGmakelink : addMemberPoint result failed. ' . ($data==null?'null':$data['description']));
                            return null;
                        }
                    }
                    catch (\Exception $ex)
                    {
                        Log::error('SPGmakelink : addMemberPoint Exception. exception=' . $ex->getMessage());
                        Log::error('SPGmakelink : addMemberPoint PARAM. PARAM=' . json_encode($params));
                        return null;
                    }
                }
            }
            
            return '/followgame/kten/'.$gamecode;

        }

        public static function getgamelink($gamecode)
        {
            $user = auth()->user();
            if ($user->playing_game != null) //already playing game.
            {
                return ['error' => true, 'data' => '이미 실행중인 게임을 종료해주세요. 이미 종료했음에도 불구하고 이 메시지가 계속 나타난다면 매장에 문의해주세요.'];
            }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [SPGController::SPG_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($thirdparty,$startDate)
        {
            
            $secret = config('app.spg_op');
            $token = config('app.spg_key');

            $endDate = date('Y-m-d H:i:s');

            $params = [
                'endDate' => $endDate,
                'agentId' => $secret,
                'token' => $token,
                'startDate' => $startDate,
                'thirdname' => $thirdparty,
                'pageSize' => 1000,
                'pageStart' => 1,
                'time' => time(),
            ];
            try
            {
                $url = config('app.spg_api') . '/api/getBetWinHistoryAll';
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('SPGgamerounds : getBetWinHistoryAll request failed. PARAMS= ' . json_encode($params));
                    Log::error('SPGgamerounds : getBetWinHistoryAll request failed. ' . $response->body());

                    return null;
                }
                $data = $response->json();
                if ($data==null || $data['errorCode'] != 0)
                {
                    Log::error('SPGgamerounds : getBetWinHistoryAll result failed. PARAMS=' . json_encode($params));
                    Log::error('SPGgamerounds : getBetWinHistoryAll result failed. ' . ($data==null?'null':$data['description']));
                    return null;
                }

                return $data;
            }
            catch (\Exception $ex)
            {
                Log::error('SPGgamerounds : getBetWinHistoryAll Excpetion. exception= ' . $ex->getMessage());
                Log::error('SPGgamerounds : getBetWinHistoryAll Excpetion. PARAMS= ' . json_encode($params));
            }
            return null;
        }

        public static function processGameRound()
        {
            $count = 0;

            foreach (SPGController::SPG_GAME_IDENTITY as $catname => $thirdId)
            {
                $category = \App\Models\Category::where([
                    'provider'=> SPGController::SPG_PROVIDER,
                    'href' => $catname,
                    'shop_id' => 0,
                    'site_id' => 0,
                    ])->first();

                if (!$category)
                {
                    continue;
                }
                $lasttime = date('Y-m-d H:i:s',strtotime('-12 hours'));
                $lastround = \App\Models\StatGame::where('category_id', $category->original_id)->orderby('date_time', 'desc')->first();
                if ($lastround)
                {
                    $d = strtotime($lastround->date_time);
                    if ($d > strtotime("-12 hours"))
                    {
                        $lasttime = date('Y-m-d H:i:s',strtotime($lastround->date_time. ' +1 seconds'));
                    }
                }
                $data = SPGController::gamerounds($thirdId, $lasttime);
                if (isset($data['totalPageSize']) && $data['totalPageSize'] > 0)
                {
                    
                    foreach ($data['data'] as $round)
                    {
                        $bet = 0;
                        $win = 0;
                        $gameName = $round['gameId'];
                        if ($catname == 'spg-cq9')
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
                        else if ($catname == 'spg-bng' || $catname == 'spg-playson')
                        {
                            if ($round['type'] == 'WIN')
                            {
                                continue;
                            }

                            $betdata = json_decode($round['details'],true);
                            $bet = $betdata['bet']??0;
                            $win = $betdata['win'];
                            $balance = $betdata['balance_after'];
                            if ($bet==0 && $win==0)
                            {
                                continue;
                            }
                        }
                        else if ($catname == 'spg-hbn')
                        {
                            if ($round['type'] == 'WIN')
                            {
                                continue;
                            }

                            $betdata = json_decode($round['history'],true);
                            $bet = $betdata['Stake'];
                            $win = $betdata['Payout'];
                            $balance = $betdata['BalanceAfter'];
                            $gameName = $betdata['GameKeyName'];
                        }
                        else if ($catname == 'spg-pp')
                        {
                            if ($round['type'] == 'WIN')
                            {
                                continue;
                            }

                            $betdata = json_decode($round['details'],true);
                            $bet = $betdata['bet'];
                            $win = $betdata['win'];
                            $balance = $betdata['balance'];
                            if (!$bet || !$win)
                            {
                                Log::error('SPG PP round : '. json_encode($round));
                                break;
                            }
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
                        $time = $round['trans_time'];

                        $userid = intval(preg_replace('/'. self::SPG_PROVIDER .'(\d+)/', '$1', $round['mem_id'])) ;
                        $shop = \App\Models\ShopUser::where('user_id', $userid)->first();
                        
                        \App\Models\StatGame::create([
                            'user_id' => $userid, 
                            'balance' => $balance, 
                            'bet' => $bet, 
                            'win' => $win, 
                            'game' =>$gameName . '_kten', 
                            'type' => 'slot',
                            'percent' => 0, 
                            'percent_jps' => 0, 
                            'percent_jpg' => 0, 
                            'profit' => 0, 
                            'denomination' => 0, 
                            'date_time' => $time,
                            'shop_id' => $shop?$shop->shop_id:0,
                            'category_id' => isset($category)?$category->id:0,
                            'game_id' =>  $round['gameId'],
                            'roundid' => $round['gameId'] . '_' . $round['roundID'],
                        ]);
                        $count = $count + 1;
                    }
                }

            }
            
            
            return [$count, 0];
        }

        public static function getAgentBalance()
        {

            $secret = config('app.spg_op');
            $token = config('app.spg_key');

            //check kten account
            $params = [
                'agentId' => $secret,
                'token' => $token,
                'time' => time(),
            ];

            try
            {

                $url = config('app.spg_api') . '/api/getAgentAccountBalance';
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('SPGAgentBalance : agentbalance request failed. ' . $response->body());
                    return -1;
                }
                $data = $response->json();
                if (($data==null) || ($data['errorCode'] != 0))
                {
                    Log::error('SPGAgentBalance : agentbalance result failed. ' . ($data==null?'null':$data['msg']));
                    return -1;
                }
                return $data['balance'];
            }
            catch (\Exception $ex)
            {
                Log::error('SPGAgentBalance : agentbalance Exception. Exception=' . $ex->getMessage());
                Log::error('SPGAgentBalance : agentbalance Exception. PARAMS=' . json_encode($params));
                return -1;
            }

        }

       

    }

}
