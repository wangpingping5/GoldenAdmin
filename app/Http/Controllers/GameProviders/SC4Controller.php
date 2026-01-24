<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    // SlotCity
    class SC4Controller extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const SC4_PROVIDER = 'sc4';
        const SC4_LANG = 2;
        const SC4_GAME_IDENTITY = [
            //==== SLOT ====
            'sc4-evo' => ['providername' =>'Evolution', 'providerid' => 6],
            'sc4-playson' => ['providername' =>'Playson', 'providerid' => 5],
            'sc4-pgsoft' => ['providername' =>'Pocket Games Soft', 'providerid' => 3],
        ];

        public static function getGameObj($uuid)
        {
            foreach (SC4Controller::SC4_GAME_IDENTITY as $ref => $value)
            {
                $gamelist = SC4Controller::getgamelist($ref);
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

        
        public static function getUserBalance($href, $user, $prefix=self::SC4_PROVIDER) {
            $url = config('app.sc4_api') . '/v4/user/info';
            $token = config('app.sc4_key');

            $param = [
                'user_code' => SC4Controller::getUserCode($href, $user, $prefix)
            ];
            $balance = -1;

            try {                   
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->post($url , $param);
                if ($response->getStatusCode() == 200) {
                    $res = $response->json();
        
                    if (isset($res['data']) && isset($res['data']['balance'])) {
                        $balance = $res['data']['balance'];
                    }
                    else
                    {
                        Log::error('SC4getuserbalance : return failed. ' . $res['message']);
                    }
                }
                else
                {
                    Log::error('SC4getuserbalance : response is not okay. ' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('SC4getuserbalance : getUserBalance Excpetion. exception= ' . $ex->getMessage());
                Log::error('SC4gamerounds : getUserBalance Excpetion. PARAMS= ' . json_encode($params));
            }
            
            return intval($balance);
        }

        public static function getUserCode($href, $user, $prefix=self::SC4_PROVIDER) {
            $url = config('app.sc4_api') . '/v4/user/create';
            $token = config('app.sc4_key');
            if($token == ''){
                return null;
            }
            $alreadyUser= \App\Models\SC4User::where([
                'user_id' => $user->id
            ])->first();
            $usercode = 0;
            if(isset($alreadyUser)){
                $usercode = $alreadyUser->sc4user_id;
            }else{
                $param = [
                    'name' => $prefix . sprintf("%04d",$user->id)
                ];
                try {                   
                    $response = Http::withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                        ])->post($url , $param);
                    if ($response->getStatusCode() == 200) {
                        $res = $response->json();
            
                        if (isset($res['code']) && $res['code'] == 0 && isset($res['data']) && isset($res['data']['user_code'])) {
                            $usercode = $res['data']['user_code'];
                            \App\Models\SC4User::create([
                                'user_id' => $user->id, 
                                'sc4user_id' => $usercode
                            ]);
                        }
                        else
                        {
                            Log::error('SC4createuser : return failed. ' . json_encode($res));
                        }
                    }
                    else
                    {
                        Log::error('SC4createuser : response is not okay. ' . $response->body());
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('SC4createuser : CreateUser Excpetion. exception= ' . $ex->getMessage());
                    Log::error('SC4createuser : CreateUser Excpetion. PARAMS= ' . json_encode($params));
                }
            }
            return $usercode;
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
            $category = SC4Controller::SC4_GAME_IDENTITY[$href];

            $url = config('app.sc4_api') . '/v4/game/games';
            $token = config('app.sc4_key');

            $param = [
                'provider_id' => $category['providerid'],
                'lang' => SC4Controller::SC4_LANG,
            ];

    
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
                ])->post($url , $param);
            if ($response->getStatusCode() != 200)
            {
                return [];
            }
            $data = $response->json();
            if(!isset($data['data']) || (isset($data['code']) && $data['code']>0)){
                return [];
            }else{
                $data = $data['data'];
            }
            $gameList = [];

            foreach ($data as $game)
            {
                $view = 1;

                $korname = $game['locale_name'];
                array_push($gameList, [
                    'provider' => self::SC4_PROVIDER,
                    'href' => $href,
                    'gamecode' => 'sc4_' . $game['game_code'],
                    'symbol' => $game['game_code'],
                    'enname' => $game['game_name'],
                    'name' => preg_replace('/\s+/', '', $game['game_name']),
                    'title' => $korname,
                    'icon' => $game['game_image'],
                    'type' => ($game['category']=='Slots')?'slot':'table',
                    'view' => $view
                ]);
            }

            //add Unknown Game item
            array_push($gameList, [
                'provider' => self::SC4_PROVIDER,
                'href' => $href,
                'symbol' => 'Unknown',
                'gamecode' => $href,
                'enname' => 'UnknownGame',
                'name' => 'UnknownGame',
                'title' => 'UnknownGame',
                'icon' => '',
                'type' => 'Unknown',
                'view' => 0
            ]);
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
        }

        public static function makegamelink($gamecode, $user,  $prefix=self::SC4_PROVIDER) 
        {

            $token = config('app.sc4_key');
            
            $game = SC4Controller::getGameObj($gamecode);
            if (!$game)
            {
                return null;
            }

            $usercode = SC4Controller::getUserCode($game['href'], $user, $prefix);
            //Create Game link
            $category = SC4Controller::SC4_GAME_IDENTITY[$game['href']];
            if($game['href'] != 'sc4-evo'){
                $gamecode = explode('_', $game['gamecode'])[1];
            }else{
                $gamecode = 'baccarat';
            }
            
            $param = [
                'user_code' => $usercode,
                'provider_id' => $category['providerid'],
                'game_symbol' => $gamecode,
                'lang' => SC4Controller::SC4_LANG
            ];

            $url = config('app.sc4_api') . '/v4/game/game-url';
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
                ])->post($url , $param);
            if ($response->getStatusCode() != 200)
            {
                Log::error('SC4GetLink : Game url request failed. status=' . $response->status());
                Log::error('SC4GetLink : Game url request failed. param=' . json_encode($param));

                return null;
            }
            $data = $response->json();
            if ($data==null || (isset($data['code']) && $data['code'] > 0))
            {
                Log::error('SC4GetLink : Game url result failed. ' . ($data==null?'null':json_encode($data)));
                return null;
            }
            $url = $data['data']['game_url'];

            return $url;
        }
        
        public static function withdrawAll($href, $user, $prefix=self::SC4_PROVIDER)
        {
            $balance = SC4Controller::getUserBalance($href, $user, $prefix);
            if ($balance < 0)
            {
                return ['error'=>true, 'amount'=>$balance, 'msg'=>'getuserbalance return -1'];
            }
            if ($balance > 0)
            {
                $token = config('app.sc4_key');

                $usercode = SC4Controller::getUserCode($href, $user, $prefix);
                $param = [
                    'user_code' => $usercode
                ];
                try {
                    $url = config('app.sc4_api') . '/v4/wallet/withdraw-all';
                    $response = Http::withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                        ])->post($url, $param);
                    if ($response->getStatusCode() != 200)
                    {
                        Log::error('SC4Withdraw : subtractMemberPoint request failed. ' . $response->body());

                        return ['error'=>true, 'amount'=>0, 'msg'=>'response not ok'];
                    }
                    $data = $response->json();
                    if ($data==null || (isset($data['code']) && $data['code'] > 0))
                    {
                        Log::error('SC4Withdraw : subtractMemberPoint result failed. PARAMS=' . $str_param);
                        Log::error('SC4Withdraw : subtractMemberPoint result failed. ' . ($data==null?'null':$data['message']));
                        return ['error'=>true, 'amount'=>0, 'msg'=>'data not ok'];
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('SC4Withdraw : subtractMemberPoint Exception. Exception=' . $ex->getMessage());
                    Log::error('SC4Withdraw : subtractMemberPoint Exception. PARAMS=' .$str_param);
                    return ['error'=>true, 'amount'=>0, 'msg'=>'exception'];
                }
            }
            return ['error'=>false, 'amount'=>$balance];
        }

        public static function makelink($gamecode, $userid)
        {
            $token = config('app.sc4_key');

            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('SC4MakeLink : Does not find user ' . $userid);
                return null;
            }

            $game = SC4Controller::getGameObj($gamecode);
            if ($game == null)
            {
                Log::error('SC4MakeLink : Game not find  ' . $gamecode);
                return null;
            }


            $alreadyUser = 1;
            $usercode = SC4Controller::getUserCode($game['href'], $user);

            $balance = SC4Controller::getuserbalance($game['href'], $user);
            if ($balance == -1)
            {
                return null;
            }

            if ($balance != $user->balance)
            {
                //withdraw all balance
                $data = SC4Controller::withdrawAll($game['href'], $user);
                if ($data['error'])
                {
                    return null;
                }
                //Add balance

            
                //addMemberPoint
                $param = [
                    'user_code' => $usercode,
                    'amount' => floatval($user->balance),
                ];
                try {
                    $url = config('app.sc4_api') . '/v4/wallet/deposit';
                    $response = Http::withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                        ])->post($url, $param);
                    if ($response->getStatusCode() != 200)
                    {
                        Log::error('SC4MakeLink : addMemberPoint result failed. ' . $response->body());
                        return null;
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('SC4MakeLink : addMemberPoint Exception. exception=' . $ex->getMessage());
                    Log::error('SC4MakeLink : addMemberPoint PARAM. PARAM=' . $str_param);
                    return null;
                }
            }
            return '/followgame/'.SC4Controller::SC4_PROVIDER.'/'.$gamecode;
            
        }

        public static function getgamelink($gamecode)
        {
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [SC4Controller::SC4_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($lastid, $limit=2000)
        {
            $token = config('app.sc4_key');
            if($token == ''){
                return null;
            }
            $params = [
                'last_id' => $lastid,
                'limit' => $limit,
            ];
            try
            {
                $url = config('app.sc4_api') . '/v4/game/transaction-id';
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->post($url , $params);
                if (!$response->ok())
                {
                    Log::error('SC4gamerounds : getBetWinHistoryAll request failed. PARAMS= ' . json_encode($params));
                    Log::error('SC4gamerounds : getBetWinHistoryAll request failed. ' . $response->body());

                    return null;
                }
                $data = $response->json();
                if ($data==null)
                {
                    Log::error('SC4gamerounds : getBetWinHistoryAll result failed. PARAMS=' . json_encode($params));
                    Log::error('SC4gamerounds : getBetWinHistoryAll result failed. ');
                    return null;
                }

                return $data;
            }
            catch (\Exception $ex)
            {
                Log::error('SC4gamerounds : getBetWinHistoryAll Excpetion. exception= ' . $ex->getMessage());
                Log::error('SC4gamerounds : getBetWinHistoryAll Excpetion. PARAMS= ' . json_encode($params));
            }
            return null;
        }

        public static function processGameRound($frompoint=-1, $checkduplicate=false)
        {
            $timepoint = 0;
            if ($frompoint == -1)
            {
                $tpoint = \App\Models\Settings::where('key', self::SC4_PROVIDER . 'timepoint')->first();
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
            $curPage = 0;
            $data = null;
            $newtimepoint = $timepoint;
            $totalCount = 0;
            $data = SC4Controller::gamerounds($timepoint);
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
                        \App\Models\Settings::create(['key' => self::SC4_PROVIDER .'timepoint', 'value' => $newtimepoint]);
                    }
                }

                return [0,0];
            }

            if (isset($data['data']))
            {
                foreach ($data['data'] as $round)
                {
                    $gameObj = self::getGameObj('sc4_' . $round['game_code']);
                    
                    if (!$gameObj)
                    {
                        Log::error('SC4 Game could not found : '. $round['game_code']);
                        continue;
                    }
                    $bet = 0;
                    $win = 0;
                    if($round['trans_type'] == 1){
                        $bet = $round['trans_amount'];
                    }else if($round['trans_type'] == 2){
                        $win = $round['trans_amount'];
                        if ($win == 0)
                        {
                            continue;
                        }
                    }else{
                        continue;
                    }
                    $balance = $round['balance'];
                    $time = $round['regdate'];
                    // $time = date('Y-m-d H:i:s',strtotime($round['CreatedAt'] . ' +9 hours'));

                    $sc4user = \App\Models\SC4User::where('sc4user_id', $round['user_code'])->first();
                    if(!isset($sc4user)){
                        continue;
                    }
                    $userid = $sc4user->user_id;
                    $shop = \App\Models\ShopUser::where('user_id', $userid)->first();
                    $category = \App\Models\Category::where('href', $gameObj['href'])->first();

                    $checkGameStat = \App\Models\StatGame::where([
                        'user_id' => $userid, 
                        'bet' => $bet, 
                        'win' => $win, 
                        'date_time' => $time,
                        'roundid' => $round['game_code'] . '#' . $round['round_id'] . '#' . $round['provider_id'],
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
                        'game' =>$gameObj['name'] . '_sc4', 
                        'type' => ($round['category']=='Slots')?'slot':'table',
                        'percent' => 0, 
                        'percent_jps' => 0, 
                        'percent_jpg' => 0, 
                        'profit' => 0, 
                        'denomination' => 0, 
                        'date_time' => $time,
                        'shop_id' => $shop?$shop->shop_id:-1,
                        'category_id' => $category?$category->original_id:0,
                        'game_id' =>  $gameObj['gamecode'],
                        'roundid' => $round['game_code'] . '#' . $round['round_id'] . '#' . $round['provider_id'],
                    ]);
                    $count = $count + 1;
                    if ($newtimepoint < $round['trans_id'])
                    {
                        $newtimepoint = $round['trans_id'] + 1;
                    }
                }
            }
            sleep(60);

            $timepoint = $newtimepoint;

            if ($frompoint == -1)
            {

                if ($tpoint)
                {
                    $tpoint->update(['value' => $timepoint]);
                }
                else
                {
                    \App\Models\Settings::create(['key' => self::SC4_PROVIDER .'timepoint', 'value' => $timepoint]);
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
            $user = \App\Models\User::where('id', $stat->user_id)->first();
            if(!isset($user)){
                return null;
            }
            $params = [
                "user_code" => SC4Controller::getUserCode('', $user),
                "round_id" => $betrounds[1],
                "provider_id" => $betrounds[2],
                "game_code" => $betrounds[0]
            ];
            try
            {
                $token = config('app.sc4_key');
                $url = config('app.sc4_api') . '/v4/game/round-details';
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->post($url , $params);
                if (!$response->ok())
                {
                    Log::error('SC4gamerounds : getRoundDetails request failed. PARAMS= ' . json_encode($params));
                    Log::error('SC4gamerounds : getRoundDetails request failed. ' . $response->body());

                    return null;
                }
                $data = $response->json();
                if ($data==null || !isset($data['code']) || $data['code'] > 0)
                {
                    Log::error('SC4gamerounds : getRoundDetails result failed. PARAMS=' . json_encode($params));
                    Log::error('SC4gamerounds : getRoundDetails result failed. ');
                    return null;
                }

                return $data['data']['url'];
            }
            catch (\Exception $ex)
            {
                Log::error('SC4gamerounds : getRoundDetails Excpetion. exception= ' . $ex->getMessage());
                Log::error('SC4gamerounds : getRoundDetails Excpetion. PARAMS= ' . json_encode($params));
            }
        }

        public static function getAgentBalance()
        {
            $token = config('app.sc4_key');
            try
            {
                $url = config('app.sc4_api') . '/v4/agent/info';
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->post($url);
                if ($response->getStatusCode() != 200)
                {
                    Log::error('SC4AgentBalance : agentbalance request failed. ' . $response->body());
                    return -1;
                }
                $data = $response->json();
                if (($data==null) || (isset($data['code']) && $data['code'] > 0))
                {
                    Log::error('SC4AgentBalance : agentbalance result failed. ' . ($data==null?'null':$data['message']));
                    return -1;
                }
                return $data['data']['balance'];
            }
            catch (\Exception $ex)
            {
                Log::error('SC4AgentBalance : agentbalance Exception. Exception=' . $ex->getMessage());
                Log::error('SC4AgentBalance : agentbalance Exception. PARAMS=');
                return -1;
            }
        }
    }
}
