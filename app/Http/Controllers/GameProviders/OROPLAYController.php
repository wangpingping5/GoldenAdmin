<?php
namespace App\Http\Controllers\GameProviders {
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    use App\Models\StatGame;
    use Carbon\Carbon;
    class OROPLAYController extends \App\Http\Controllers\Controller
    {
        /*
         * UTILITY FUNCTION
         */

        const OROPLAY_PROVIDER = 'oroplay';

        const OROPLAY_GAME_IDENTITY = [
            "oroplay-dream" => ["vendor" => "casino-dream", "name" => "DreamGaming", "type" => "table"],
            "oroplay-micro" => ["vendor" => "casino-micro", "name" => "MicroGamingCasino", "type" => "table"],
            "oroplay-sa" => ["vendor" => "casino-sa", "name" => "SaGaming", "type" => "table"],
            "oroplay-pragmatic" => ["vendor" => "casino-pragmatic", "name" => "PragmaticCasino", "type" => "table"],
            "oroplay-playace" => ["vendor" => "casino-playace", "name" => "PlayAce", "type" => "table"],
            "oroplay-pragmatic" => ["vendor" => "slot-pragmatic", "name" => "PragmaticPlay", "type" => "slot"],
            "oroplay-pgsoft" => ["vendor" => "slot-pgsoft", "name" => "PGSoft", "type" => "slot"],
            "oroplay-habanero" => ["vendor" => "slot-habanero", "name" => "Habanero", "type" => "slot"],
            "oroplay-dreamtech" => ["vendor" => "slot-dreamtech", "name" => "DreamTech", "type" => "slot"],
            "oroplay-booongo" => ["vendor" => "slot-booongo", "name" => "Booongo", "type" => "slot"],
            "oroplay-hacksaw" => ["vendor" => "slot-hacksaw", "name" => "Hacksaw", "type" => "slot"],
            "oroplay-popiplay" => ["vendor" => "slot-popiplay", "name" => "Popiplay", "type" => "slot"],
            "oroplay-evoplay" => ["vendor" => "slot-evoplay", "name" => "Evoplay", "type" => "slot"],
            "oroplay-mascot" => ["vendor" => "slot-mascot", "name" => "Mascot", "type" => "slot"],
            "oroplay-popok" => ["vendor" => "slot-popok", "name" => "PopOk", "type" => "slot"],
            "oroplay-cq9" => ["vendor" => "slot-cq9", "name" => "CQ9", "type" => "slot"],
            "oroplay-fachai" => ["vendor" => "slot-fachai", "name" => "FaChai", "type" => "slot"],
            "oroplay-jdb" => ["vendor" => "slot-jdb", "name" => "JDB", "type" => "slot"],
            "oroplay-3oaks" => ["vendor" => "slot-3oaks", "name" => "3Oaks", "type" => "slot"],
            "oroplay-jili" => ["vendor" => "slot-jili", "name" => "Jili", "type" => "slot"],
            "oroplay-rubyplay" => ["vendor" => "slot-rubyplay", "name" => "RubyPlay", "type" => "slot"],
            "oroplay-playson" => ["vendor" => "slot-playson", "name" => "Playson", "type" => "slot"],
            "oroplay-tada" => ["vendor" => "slot-tada", "name" => "TADA", "type" => "slot"],
            "oroplay-homeslot" => ["vendor" => "slot-homeslot", "name" => "JinJiBaoXi", "type" => "slot"],
            "oroplay-nolimitcity" => ["vendor" => "slot-nolimitcity", "name" => "NoLimitCity", "type" => "slot"],
            "oroplay-wg" => ["vendor" => "slot-wg", "name" => "WG", "type" => "slot"],
            "oroplay-atg" => ["vendor" => "slot-atg", "name" => "ATG", "type" => "slot"],
            "oroplay-ka" => ["vendor" => "slot-ka", "name" => "KAGame", "type" => "slot"],
            "oroplay-egt" => ["vendor" => "slot-egt", "name" => "EGT", "type" => "slot"],
            "oroplay-amusnet" => ["vendor" => "slot-amusnet", "name" => "Amusnet", "type" => "slot"],
            "oroplay-aviator" => ["vendor" => "mini-aviator", "name" => "Aviator", "type" => "mini"],
            "oroplay-spribe" => ["vendor" => "mini-spribe", "name" => "Spribe", "type" => "mini"],
            "oroplay-inout" => ["vendor" => "mini-inout", "name" => "InOut", "type" => "mini"],
            "oroplay-jdb" => ["vendor" => "fishing-jdb", "name" => "JDBFishing", "type" => "fishing"],
            "oroplay-sports" => ["vendor" => "sports", "name" => "Sports", "type" => "sports"],
            "oroplay-poker" => ["vendor" => "board-poker", "name" => "Poker", "type" => "board"],
        ];
        public static function getGameObj($uuid, $vendor = '')
        {
            foreach (OROPLAYController::OROPLAY_GAME_IDENTITY as $ref => $value) {
                $gamelist = OROPLAYController::getgamelist($ref);
                if ($gamelist) {
                    foreach ($gamelist as $game) {
                        if($vendor != '')
                        {
                            if ($game['vendorKey'] == $vendor && $game['symbol'] == $uuid) {
                                return $game;
                            }
                        }
                        else if ($game['gamecode'] == $uuid) {
                            return $game;
                        }
                    }
                }
            }
            return null;
        }
        public static function createToken()
        {
            $url = config('app.oroplay_api');
            $agent = config('app.oroplay_agent');
            $pass = config('app.oroplay_secretkey');
            $param = [
                "clientId" => $agent,
                "clientSecret" => $pass
            ];
            try {
                $response = Http::withBody(json_encode($param), 'application/json')->post($url . '/auth/createtoken');
                if ($response->ok()) {
                    $res = $response->json();
                    $oro_token= \App\Models\Settings::where('key', self::OROPLAY_PROVIDER . 'token')->first();
                    if($oro_token != null)
                    {
                        $oro_token->update(['value' => $res['token']]);
                    }
                    else
                    {
                        \App\Models\Settings::create(['key' => self::OROPLAY_PROVIDER .'token', 'value' => $res['token']]);
                    }
                    return $res['token'];
                } else {
                    Log::error('Oroplay Token Request : response is not okay. api=>' . $url . ', param =>' . json_encode($param) . ', body=>' . $response->body());
                }
            } catch (\Exception $ex) {
                Log::error('Oroplay Token Request :  Exception. exception= ' . $ex->getMessage());
                Log::error('Oroplay Token Request :  Exception. PARAMS= ' . json_encode($param));
            }
            return null;
        }
        public static function getToken()
        {
            $oro_token= \App\Models\Settings::where('key', self::OROPLAY_PROVIDER . 'token')->first();
            if($oro_token == null) 
            {
                $token = self::createToken();
                if($token == null)
                {
                    Log::error('Oroplay Request : Token does not exist');
                    return '';
                }
            }
            else 
            {
                $token = $oro_token->value;
            }
            return $token;
        }
        public static function sendRequest($requestmethod, $sub_url, $param)
        {
            Log::info(json_encode($param));
            $url = config('app.oroplay_api');
            $token = self::getToken();
            if($token == '')
            {
                return null;   
            }
            try {
                if($requestmethod == 'post')
                    $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->post($url . $sub_url, $param);
                else
                    $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->get($url . $sub_url);
                if ($response->ok()) {
                    $res = $response->json();
                    return $res;
                } else {
                    Log::error('Oroplay Request : response is not okay. api=>' . $url . $sub_url . ', param =>' . json_encode($param) . ', body=>' . $response->body());
                }
            } catch (\Exception $ex) {
                Log::error('Oroplay Request :  Exception. exception= ' . $ex->getMessage());
                Log::error('Oroplay Request :  Exception. PARAMS= ' . json_encode($param));
            }
            return null;
        }
        public static function getgamelist($href)
        {
            $gameList = \Illuminate\Support\Facades\Redis::get($href . 'list');
            if ($gameList) {
                $games = json_decode($gameList, true);
                if ($games != null && count($games) > 0) {
                    return $games;
                }
            }
            $category = OROPLAYController::OROPLAY_GAME_IDENTITY[$href];

            $param = [
                "vendorCode" => $category['vendor'],
                "language" => "en",
            ];

            $gameList = [];
            $data = OROPLAYController::sendRequest('post', '/games/list', $param);
            $view = 1;
            if ($data && $data['success'] == true) {
                foreach ($data['message'] as $game) {
                    if ($game['vendorCode'] != $category['vendor']) {
                        continue;
                    }
                    if($game['underMaintenance'] == true || ($category['type'] == 'table' && $game['gameCode'] != 'lobby'))
                    {
                        $view = 0;   
                    }
                    array_push($gameList, [
                        'provider' => self::OROPLAY_PROVIDER,
                        'vendorKey' => $category['vendor'],
                        'href' => $href,
                        'gameid' => $game['gameId'],
                        'gamecode' => $game['provider'] . '_' .$game['gameCode'],
                        'symbol' => $game['gameCode'],
                        'name' => $game['gameName'],
                        'title' => $game['gameName'],
                        'type' => $category['type'],
                        'icon' => $game['thumbnail'],
                        'view' => $view
                    ]);
                }
            }

            \Illuminate\Support\Facades\Redis::set($href . 'list', json_encode($gameList));
            return $gameList;

        }
        public static function makegamelink($gamecode, $prefix = self::OROPLAY_PROVIDER, $user = null)
        {
            $game = OROPLAYController::getGameObj($gamecode);
            if (!$game) {
                return null;
            }

            if ($user == null) {
                $user = auth()->user();
            }

            if ($user == null) {
                return null;
            }

            $user_code = $prefix . sprintf("%04d", $user->id);


            $params = [
                "vendorCode" => $game['vendorKey'],
                "gameCode" => $game['symbol'],
                "userCode" => $user_code,
                "language" => $user->language ? $user->language : 'en'
            ];

            $data = OROPLAYController::sendRequest('post', '/game/launch-url', $params);
            $url = null;

            if ($data && $data['success'] == true) {
                $url = $data['message'];
            } else {
                Log::error('OROPLAYMakeLink : geturl, msg=  ' . $data['msg']);
            }

            return $url;
        }
        public static function getgamelink($gamecode)
        {
            if (isset(self::OROPLAY_GAME_IDENTITY[$gamecode])) {
                $gamelist = OROPLAYController::getgamelist($gamecode);
                if (count($gamelist) > 0) {
                    foreach ($gamelist as $g) {
                        if ($g['view'] == 1) {
                            $gamecode = $g['gamecode'];
                            break;
                        }
                    }

                }
            }
            $url = OROPLAYController::makegamelink($gamecode);
            if ($url) {
                return ['error' => false, 'data' => ['url' => $url]];
            }
            return ['error' => true, 'msg' => 'Game Launch Error'];
        }

        // for admin
        public function setVendorRTP(\Illuminate\Http\Request $request)
        {
            $param = [
                'vendorCode' => $request->vendor,
                'rtp' => $request->rtp
            ];
            $data = OROPLAYController::sendRequest('post', '/game/users/reset-rtp', $params);

            if ($data && $data['success'] == true) {
                Log::error('OROPLAY RTP for Vendor: updated RTP =  ' . $data['message']);
            } else {
                Log::error('OROPLAY RTP for Vendor, msg=  ' . $data['message'] . ', errorCode=' . $data['errorCode']);
            }
        }
        public function setUserVendorRTP(\Illuminate\Http\Request $request)
        {
            $user = \App\Models\User::where(['id' => $request->userid, 'role_id' => 1])->first();
            if($user == null)
            {
                return;
            }
            $param = [
                'vendorCode' => $request->vendor,
                'userCode' => self::OROPLAY_PROVIDER . sprintf("%04d", $user->id),
                'rtp' => $request->rtp
            ];
            $data = OROPLAYController::sendRequest('post', '/game/user/set-rtp', $params);

            if ($data && $data['success'] == true) {
                Log::error('OROPLAY RTP for Vendor: updated RTP =  ' . $data['message']);
            } else {
                Log::error('OROPLAY RTP for Vendor, msg=  ' . $data['message'] . ', errorCode=' . $data['errorCode']);
            }
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
                "id" => $betrounds[1],
                "language" => $user->language ?? 'en',
            ];
            $data = OROPLAYController::sendRequest('post', '/betting/history/detail', $params);
            if($data['success'] == true)
            {
                return $data['message'];
            }
            else
            {
                Log::error('OROPLAY GetGameDedail: data =  ' . json_encode($data));
                return '';   
            }
        }
        public static function getAgentBalance()
        {
            $data = OROPLAYController::sendRequest('get', '/agent/balance', '');
            if($data['success'] == true)
            {
                return $data['message'];
            }
            else
            {
                return -1;
            }   
        }

        // callback for seamless
        public function balance(\Illuminate\Http\Request $request)
        {
            $auth = $request->header('Authorization');
            if (!$auth) {
                return response()->json([
                    "success" => false,               
                    "message" => 0,
                    "errorCode" => 401
                ]);
            }
            $data = $request->all();
            Log::info('OROPLAY CallBack Balance Data = ' . json_encode($data));
            if (!isset($data['userCode'])) {
                Log::error('OROPLAY CallBack : No params. PARAMS= ' . json_encode($data));
                return response()->json([
                    "success" => false,               
                    "message" => 0,
                    "errorCode" => 400
                ]);
            }

            $username = $data['username'];
            $userid = preg_replace('/' . self::OROPLAY_PROVIDER . '(\d+)/', '$1', $username);

            $user = \App\Models\User::where(['id' => $userid, 'role_id' => 1])->first();

            if (!$user) {
                Log::error('OROPLAY CallBack : Not found user. PARAMS= ' . json_encode($data));
                return response()->json([
                    "success" => false,               
                    "message" => 0,
                    "errorCode" => 2
                ]);
            }
            if ($user->api_token == 'playerterminate') {
                Log::error('OROPLAY CallBack : terminated by admin. PARAMS= ' . json_encode($data));
                return response()->json([
                    "success" => false,               
                    "message" => 0,
                    "errorCode" => 404
                ]);
            }
            return response()->json([
                    "success" => true,               
                    "message" => $user->balance,
                    "errorCode" => 0
                ]);
        }

        public function transaction(\Illuminate\Http\Request $request)
        {
            set_time_limit(0);
            $data = $request->all();
            Log::info('OROPLAY CallBack Transaction Data = ' . json_encode($data));
            $auth = $request->header('Authorization');
            if (!$auth) {
                Log::error('OROPLAY CallBack Transaction : Unauthorization');
                return response()->json([
                    "success" => false,               
                    "message" => 0,
                    "errorCode" => 401
                ]);
            }
            if (!isset($data['userCode'])) {
                Log::error('OROPLAY CallBack Transaction : No params. PARAMS= ' . json_encode($data));
                return response()->json([
                    "success" => false,               
                    "message" => 0,
                    "errorCode" => 400
                ]);
            }

            $username = $data['username'];
            $userid = preg_replace('/' . self::OROPLAY_PROVIDER . '(\d+)/', '$1', $username);

            $user = \App\Models\User::where(['id' => $userid, 'role_id' => 1])->first();

            if (!$user) {
                Log::error('OROPLAY CallBack Transaction : Not found user. PARAMS= ' . json_encode($data));
                return response()->json([
                    "success" => false,               
                    "message" => 0,
                    "errorCode" => 2
                ]);
            }
            if ($user->api_token == 'playerterminate') {
                Log::error('OROPLAY CallBack Transaction : terminated by admin. PARAMS= ' . json_encode($data));
                return response()->json([
                    "success" => false,               
                    "message" => 0,
                    "errorCode" => 404
                ]);
            }
            
            $bet = $data['amount'] < 0 ? -$data['amount'] : 0;
            $win = $data['amount'] > 0 ? $data['amount'] : 0;
            $type = 'spin';
            $bettype = 'bet';
            $transactionKey = $data['transactionCode'];
            if(isset($data['transactionType']))
            {
                if($data['transactionType'] == 'debit')
                {
                    $bettype = 'bet';
                }
                else if($data['transactionType'] == 'credit')
                {
                       $bettype = 'win';
                }
                else 
                {
                    $bettype = 'betwin';
                }    
            }
            else
            {
                if($bet == 0)   
                {
                    $bettype = 'win';   
                }
                else if($bet > 0 && $win > 0)
                {
                    $bettype = 'betwin';
                }
            }

            $gameObj = OROPLAYController::getGameObj($data['gameCode'], $data['vendorCode']);
            $shop = \App\Models\ShopUser::where('user_id', $user->id)->first();
            $category = \App\Models\Category::where('href', $gameObj['href'])->first();

            $checkGameStat = \App\Models\StatGame::where([
                'user_id' => $user->id,
                'bettype' => $bettype,
                // 'date_time' => $time,
                'roundid' => $data['roundId'] . '#' . $data['historyId'] . '#' . $transactionKey,
            ])->first();
            if ($checkGameStat) {
                Log::error('OROPLAY CallBack Transaction : duplicate data ' . json_encode($data));
                return response()->json([
                    "success" => true,               
                    "message" => $user->balance,
                    "errorCode" => 0
                ]);
            }
            \DB::beginTransaction();
            $user = \App\Models\User::lockforUpdate()->where(['id' => $user->id, 'role_id' => 1])->first();
            $beforeBalance = $user->balance;
            if ($bettype == 'bet') {
                if ($user->balance < intval($bet)) {
                    \DB::commit();
                    Log::error('OROPLAY CallBack Transaction : insufficient balance. PARAMS= ' . json_encode($data));
                    return response()->json([
                        "success" => false,              
                        "message" => 0,
                        "errorCode" => 4,
                    ]);
                } else {
                    $user->balance = $user->balance - $bet;
                }
            } else {
                $user->balance = $user->balance + $win;
            }
            $user->save();
            $gamename = $gameObj['name'] . '_oroplay';

            \App\Models\StatGame::create([
                'user_id' => $user->id,
                'balance' => $user->balance,
                'bet' => $bet,
                'win' => $win,
                'bettype' => $bettype,
                'game' => $gamename,
                'type' => $gameObj['type'],
                'percent' => 0,
                'percent_jps' => 0,
                'percent_jpg' => 0,
                'profit' => 0,
                'denomination' => 0,
                'shop_id' => $shop ? $shop->shop_id : -1,
                'category_id' => $category ? $category->original_id : 0,
                'game_id' => $gameObj['symbol'],
                'round_finished' =>  $data['isFinished'] == true ? 1 : 0,
                'roundid' => $data['round_id'] . '#' . $data['historyId'] . '#' . $transactionKey,
                'transactionid' => $transactionKey,
            ]);
            Log::debug('OROPLAY CallBack Transaction : cretae Stat Game');
            \DB::commit();

            Log::debug('OROPLAY CallBack  Transaction : Player Balance = ' . $user->balance);
            return response()->json([
                    "success" => true,               
                    "message" => $user->balance,
                    "errorCode" => 0
                ]);
        }
    }
}
