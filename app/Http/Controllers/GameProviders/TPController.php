<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class TPController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const TP_PROVIDER = 'tp';
        const TP_PP_HREF = 'tp_pp';
        const TP_GAME_IDENTITY = [
            'tp_bbtec' => 6,
            'tp_pp' => 8,
            'tp_isoft' => 10,
            'tp_star' => 11,
            'tp_pgsoft' => 14,
            'tp_bbin' => 15,
            'tp_rtg' => 19,
            'tp_mg' => 21,
            'tp_cq9' => 23,
            'tp_hbn' => 24,
            'tp_playstar' => 29,
            'tp_gameart' => 30,
            'tp_ttg' => 32,
            'tp_genesis' => 33,
            'tp_tpg' => 34,
            'tp_playson' => 36,
            'tp_bng' => 37,
            'tp_evoplay' => 40,
            'tp_dreamtech' => 41,
            'tp_ag' => 44,
            'tp_theshow' => 47,
            'tp_png' => 51,
        ];
        const TP_IDENTITY_GAME = [
            6  => 'tp_bbtec'      ,
            8  => 'tp_pp'         ,
            10 => 'tp_isoft'      ,
            11 => 'tp_star'       ,
            14 => 'tp_pgsoft'     ,
            15 => 'tp_bbin'       ,
            19 => 'tp_rtg'        ,
            21 => 'tp_mg'         ,
            23 => 'tp_cq9'        ,
            24 => 'tp_hbn'        ,
            29 => 'tp_playstar'   ,
            30 => 'tp_gameart'    ,
            32 => 'tp_ttg'        ,
            33 => 'tp_genesis'    ,
            34 => 'tp_tpg'        ,
            36 => 'tp_playson'    ,
            37 => 'tp_bng'        ,
            40 => 'tp_evoplay'    ,
            41 => 'tp_dreamtech'  ,
            44 => 'tp_ag'         ,
            47 => 'tp_theshow'    ,
            51 => 'tp_png'        ,
        ];
        public static function getGameObjBySymbol($thirdparty, $sym)
        {
            $gamelist = TPController::getgamelist(self::TP_IDENTITY_GAME[$thirdparty]);
            if ($gamelist)
            {
                foreach($gamelist as $game)
                {
                    if ($game['symbol'] == $sym)
                    {
                        return $game;
                        break;
                    }
                }
            }
            return null;
        }
        public static function getGameObj($uuid)
        {
            foreach (self::TP_IDENTITY_GAME as $href){
                $gamelist = TPController::getgamelist($href);
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
        * FROM ThePlus, Utility API
        */

        
        /*
        * FROM CONTROLLER, API
        */

        public function userSignal(\Illuminate\Http\Request $request)
        {
            $user = \App\Models\User::lockForUpdate()->where('id',auth()->id())->first();
            if (!$user)
            {
                return response()->json([
                    'error' => '1',
                    'description' => 'unlogged']);
            }
            if ($user->playing_game != self::TP_PROVIDER)
            {
                return response()->json([
                    'error' => '1',
                    'description' => 'Idle TimeOut']);
            }

            if ($request->name == 'exitGame')
            {
                $user->update([
                    'playing_game' => self::TP_PROVIDER . '_exit',
                    'played_at' => time()
                ]);
            }
            else
            {
                $balance = TPController::getuserbalance($user->id);
                if ($balance >= 0)
                {
                    $user->update([
                        'balance' => $balance,
                        'played_at' => time()
                    ]);
                }
            }
            return response()->json([
                'error' => '0',
                'description' => 'OK']);
        }

        public static function getuserbalance($userID) {
            $url = config('app.tp_api') . '/custom/api/user/GetBalance';
            $key = config('app.tp_api_key');
            $secret = config('app.tp_api_secret');
    
            $params = [
                'key' => $key,
                'secret' => $secret,
                'userID' => self::TP_PROVIDER . $userID,
                'isRenew' => true
            ];
    
            $response = Http::post($url, $params);
            
            $balance = -1;
            if ($response->ok()) {
                $res = $response->json();
    
                if ($res['resultCode'] == 0) {
                    $balance = $res['balance'];
                }
            }
    
            return $balance;
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

            $url = config('app.tp_api') . '/custom/api/game/List';
            $key = config('app.tp_api_key');
            $secret = config('app.tp_api_secret');

            $category = self::TP_GAME_IDENTITY[$href];

            $params = [
                'key' => $key,
                'secret' => $secret,
                'thirdParty' => $category
            ];

            $response = Http::post($url, $params);

            if (!$response->ok())
            {
                return [];
            }
            
            $data = $response->json();
            $gameList = [];
            if ($data['resultCode'] == 0)
            {
                foreach ($data['data'] as $game)
                {
                    if (in_array($href,['tp_bng','tp_playson']) && str_contains($game['uuid'],'_mob'))
                    {
                        continue;
                    }
                    array_push($gameList, [
                        'provider' => self::TP_PROVIDER,
                        'href' => $href,
                        'symbol' => isset($game['symbol'])?$game['symbol']:$game['uuid'],
                        'gamecode' => $game['uuid'],
                        'enname' => $game['name'],
                        'name' => preg_replace('/\s+/', '', $game['name']),
                        'title' => $game['nameKO'],
                        'icon' => $game['img'],
                        'type' => strtolower($game['type']),
                        'view' => 1
                    ]);
                }
                //add Unknown Game item
                array_push($gameList, [
                    'provider' => self::TP_PROVIDER,
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

        public static function getgamelink_tp($gamecode, $user) 
        {
            //Create Game Session
            $url = config('app.tp_api') . '/custom/api/game/CreateSession';
            $key = config('app.tp_api_key');
            $secret = config('app.tp_api_secret');
            $params = [
                'key' => $key,
                'secret' => $secret,
                'userID' => self::TP_PROVIDER . $user->id,
            ];

            $response = Http::post($url, $params);
            if (!$response->ok())
            {
                Log::error('TPGetLink : Game Session request failed. ' . $response->body());
                return ['error' => true, 'data' => 'Game Session request failed'];
            }
            $data = $response->json();
            if ($data==null || ($data['resultCode']!=0 )) //Deposit failed
            {
                Log::error('TPGetLink : Game Session result failed. ' . ($data==null?'null':$data['resultMessage']));
                return ['error' => true, 'data' => 'Game Session result failed'];
            }

            $session = $data['session'];

            //Create Game link
            $url = config('app.tp_api') . '/custom/api/game/Link';
            $params = [
                'key' => $key,
                'secret' => $secret,
                'session' => $session,
                'gameID' => $gamecode
            ];

            $response = Http::post($url, $params);
            if (!$response->ok())
            {
                Log::error('TPGetLink : Game link request failed. ' . $response->body());
                return ['error' => true, 'data' => 'Game link request failed'];
            }
            $data = $response->json();
            if ($data==null || ($data['resultCode']!=0 )) //Deposit failed
            {
                Log::error('TPGetLink : Game link result failed. ' . ($data==null?'null':$data['resultMessage']));
                return ['error' => true, 'data' => 'Game link result failed'];
            }

            $url = $data['gameUrl'];  
            return ['error' => false, 'data' => ['url' => $url]];
        }

        public static function withdrawAll($userID)
        {
            $url = config('app.tp_api') . '/custom/api/user/WithdrawAll';
            $key = config('app.tp_api_key');
            $secret = config('app.tp_api_secret');
            $params = [
                'key' => $key,
                'secret' => $secret,
                'userID' => self::TP_PROVIDER . $userID
            ];
            $response = Http::post($url, $params);
            if (!$response->ok())
            {
                Log::error('withdrawAll : WithdrawAll request failed. ' . $response->body());
                return ['error'=>true, 'amount'=>0];
            }
            $data = $response->json();
            if ($data==null || ($data['resultCode']!=0 )) //WithdrawAll failed
            {
                Log::error('withdrawAll : WithdrawAll result failed. ' . self::TP_PROVIDER . $userID . ':' .($data==null?'null':$data['resultMessage']));
                return ['error'=>true, 'amount'=>0];
            }
            return ['error'=>false, 'amount'=>$data['amount']];
        }

        public static function createPlayer($userid)
        {
            //create theplus account
            $url = config('app.tp_api') . '/custom/api/user/Create';
            $key = config('app.tp_api_key');
            $secret = config('app.tp_api_secret');
            $params = [
                'key' => $key,
                'secret' => $secret,
                'userID' => self::TP_PROVIDER . $userid
            ];

            $response = Http::post($url, $params);
            if (!$response->ok())
            {
                Log::error('TPMakeLink : Create account request failed. ' . $response->body());
                return -1;
            }
            $data = $response->json();
            if ($data==null || ($data['resultCode']!=0 && $data['resultCode']!=89)) //create success or already exist
            {
                Log::error('TPMakeLink : Create account result failed. ' . ($data==null?'null':$data['resultMessage']));
                return -1;
            }
            return $data['resultCode'];
        }

        public static function makelink($gamecode, $userid)
        {
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('TPMakeLink : Does not find user ' . $userid);
                return null;
            }

            $key = config('app.tp_api_key');
            $secret = config('app.tp_api_secret');

            //create theplus account
            $createdId = TPController::createPlayer($user->id);
            if ($createdId < 0)
            {
                return null;
            }

            if ($createdId==89)
            {
                //withdraw all balance
                $data = TPController::withdrawAll($user->id);
                if ($data['error'])
                {
                    return null;
                }
            }
            //Add balance

            if ($user->balance > 0)
            {
                $url = config('app.tp_api') . '/custom/api/user/Deposit';
                $params = [
                    'key' => $key,
                    'secret' => $secret,
                    'userID' => self::TP_PROVIDER . $user->id,
                    'amount' => (int)$user->balance
                ];

                $response = Http::post($url, $params);
                if (!$response->ok())
                {
                    Log::error('TPMakeLink : Deposit request failed. ' . $response->body());
                    return null;
                }
                $data = $response->json();
                if ($data==null || ($data['resultCode']!=0 )) //Deposit failed
                {
                    Log::error('TPMakeLink : Deposit result failed. ' . ($data==null?'null':$data['resultMessage']) . ' for user id ' . $user->id . ', balance=' . $user->balance);
                    return null;
                }
            }
             
            return '/providers/tp/'.$gamecode;

        }

        public static function getgamelink($gamecode)
        {
            $user = auth()->user();
            if ($user->playing_game != null) //already playing game.
            {
                return ['error' => true, 'data' => '이미 실행중인 게임을 종료해주세요. 이미 종료했음에도 불구하고 이 메시지가 계속 나타난다면 매장에 문의해주세요.'];
            }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [self::TP_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($timepoint)
        {
            $url = config('app.tp_api') . '/system/api/GetBetWinLogByIndex';
            $key = config('app.tp_api_key');
            $secret = config('app.tp_api_secret');
            $pageSize = 2000;
            $params = [
                'key' => $key,
                'secret' => $secret,
                'pageSize' => $pageSize,
                'objectID' => $timepoint
            ];
            $response = null;

            try {
                $response = Http::post($url, $params);
            } catch (\Exception $e) {
                Log::error('TPGameRounds : GameRounds request failed. ' . $e->getMessage());
                return null;
            }

            if (!$response->ok())
            {
                Log::error('TPGameRounds : GameRounds request failed. ' . $response->body());
                return null;
            }

            $data = $response->json();
            return $data;
        }

        public static function processGameRound()
        {
            $tpoint = \App\Models\Settings::where('key', 'TPtimepoint')->first();
            if ($tpoint)
            {
                $timepoint = $tpoint->value;
            }
            else
            {
                $timepoint = 0;
            }

            //get category id
            
            $data = TPController::gamerounds($timepoint);
            $count = 0;
            if ($data && $data['resultCode'] == 0 && $data['totalDataSize'] > 0)
            {
                $timepoint = $data['lastObjectID'];
                if ($tpoint)
                {
                    $tpoint->update(['value' => $timepoint]);
                    $tpoint->save();
                }
                else
                {
                    \App\Models\Settings::create(['key' => 'TPtimepoint', 'value' => $timepoint]);
                }
                foreach ($data['data'] as $round)
                {
                    if ($round['Status'] == 'BET')
                    {
                        $bet = $round['Amount'];
                        $win = 0;
                    }
                    if ($round['Status'] == 'WIN') {
                        $bet = 0;
                        $win = $round['Amount'];
                        if ($win  == 0) //skip record
                        {
                            continue;
                        }
                    }

                    $category = \App\Models\Category::where(['provider' => self::TP_PROVIDER,'href' => self::TP_IDENTITY_GAME[$round['ThirdParty']]])->first();

                    $balance = $round['Balance'];
                    $time = $round['Date'];
                    $userid = preg_replace('/'. self::TP_PROVIDER .'(\d+)/', '$1', $round['PlayerID']) ;
                    $shop = \App\Models\ShopUser::where('user_id', $userid)->first();
                    $gameName = $round['GameID'];
                    $gameObj =  TPController::getGameObjBySymbol($round['ThirdParty'], $round['GameID']);
                    if ($gameObj == null)
                    {
                        $gameObj = TPController::getGameObjBySymbol($round['ThirdParty'], 'Unknown');
                    }
                    else
                    {
                        $gameName = $gameObj['name'];
                    }
                    \App\Models\StatGame::create([
                        'user_id' => $userid, 
                        'balance' => $balance, 
                        'bet' => $bet, 
                        'win' => $win, 
                        'game' =>$gameName . '_tp', 
                        'type' => 'slot',
                        'percent' => 0, 
                        'percent_jps' => 0, 
                        'percent_jpg' => 0, 
                        'profit' => 0, 
                        'denomination' => 0, 
                        'date_time' => $time,
                        'shop_id' => $shop?$shop->shop_id:0,
                        'category_id' => isset($category)?$category->original_id:0,
                        'game_id' => $gameObj['gamecode'],
                        'roundid' => $round['GameID'] . '_' . $round['LinkTransID'],
                    ]);
                    $count = $count + 1;
                }
            }
            return [$count, $timepoint];
        }

        public static function syncpromo()
        {
            $anyuser = \App\Models\User::where('role_id', 1)->whereNull('playing_game')->first();           
            if (!$anyuser)
            {
                return ['error' => true, 'msg' => 'not found any available user.'];
            }
            try{
                $gamelist = TPController::getgamelist(self::TP_PP_HREF);
                $len = count($gamelist);
                if ($len > 10) {$len = 10;}
                if ($len == 0)
                {
                    return ['error' => true, 'msg' => 'not found any available game.'];
                }
                $rand = mt_rand(0,$len);
                
                $gamecode = $gamelist[$rand]['gamecode'];
                $code = TPController::createPlayer($anyuser->id);
                if ($code < 0) //create player failed
                {
                    //오류
                    return ['error' => true, 'msg' => 'crete player error '];
                }
                $url = TPController::getgamelink_tp($gamecode, $anyuser);
                if ($url['error'] == true)
                {
                    return ['error' => true, 'msg' => 'game link error '];
                }
            
                //emulate client
                $response = Http::withOptions(['allow_redirects' => false,'proxy' => config('app.ppproxy')])->get($url['data']['url']);
                if ($response->status() == 302)
                {
                    $location = $response->header('location');
                    $keys = explode('&', $location);
                    $mgckey = null;
                    foreach ($keys as $key){
                        if (str_contains( $key, 'mgckey='))
                        {
                            $mgckey = $key;
                            break;
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
                    $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/promo/active/?symbol='.$gamecode.'&' . $mgckey );
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
                    $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/promo/tournament/details/?symbol='.$gamecode.'&' . $mgckey );
                    if ($response->ok())
                    {
                        $promo->tournamentdetails = $response->body();
                    }
                    $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/promo/race/details/?symbol='.$gamecode.'&' . $mgckey );
                    if ($response->ok())
                    {
                        $promo->racedetails = $response->body();
                    }
                    $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/promo/tournament/v3/leaderboard/?symbol='.$gamecode.'&' . $mgckey );
                    if ($response->ok())
                    {
                        $promo->tournamentleaderboard = $response->body();
                    }
                    $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/promo/race/prizes/?symbol='.$gamecode.'&' . $mgckey );
                    if ($response->ok())
                    {
                        $promo->raceprizes = $response->body();
                    }

                    $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->post(config('app.ppgameserver') . '/gs2c/promo/race/winners/?symbol='.$gamecode.'&' . $mgckey , ['latestIdentity' => $raceIds]);
                    if ($response->ok())
                    {
                        $promo->racewinners = $response->body();
                    }

                    $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/minilobby/games?' . $mgckey );
                    if ($response->ok())
                    {
                        $json_data = $response->json();
                        //disable not own games
                        $ownCats = \App\Models\Category::where(['href'=> 'pragmatic', 'shop_id'=>0,'site_id'=>0])->first();
                        $gIds = $ownCats->games->pluck('game_id')->toArray();
                        $ownGames = \App\Models\Game::whereIn('id', $gIds)->get();

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

        public static function getAgentBalance()
        {

            $url = config('app.tp_api') . '/custom/api/agent/GetCurrentEgg';
            $key = config('app.tp_api_key');
            $secret = config('app.tp_api_secret');
            $params = [
                'key' => $key,
                'secret' => $secret,
            ];
            $response = null;

            try {
                $response = Http::post($url, $params);
                $data = $response->json();
                return $data['currentEgg'];
            } catch (\Exception $e) {
                Log::error('TPAgentMoney : request failed. ' . $e->getMessage());
                return -1;
            }

        }

    }

}
