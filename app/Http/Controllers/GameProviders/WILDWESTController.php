<?php
namespace App\Http\Controllers\GameProviders {
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    use \App\Http\Controllers\Web\Frontend\CallbackController;
    use App\Models\StatGame;
    use Carbon\Carbon;
    class WILDWESTController extends \App\Http\Controllers\Controller
    {
        /*
         * UTILITY FUNCTION
         */

        const WILDWEST_PROVIDER = 'wildwest';

        const WILDWEST_GAME_IDENTITY = [
            "wildwest-playtechlive" => ["vendor" => "playtechlive", "type" => "table"],
            "wildwest-pragmaticplaylive" => ["vendor" => "pragmaticplaylive", "type" => "table"],
            "wildwest-spribe" => ["vendor" => "spribe", "type" => "table"],

            "wildwest-hacksaw" => ["vendor" => "hacksaw", "type" => "slot"],
            "wildwest-retrogaming" => ["vendor" => "retrogaming", "type" => "slot"],
            "wildwest-onlyplay" => ["vendor" => "onlyplay", "type" => "slot"],
            "wildwest-wizard" => ["vendor" => "wizard", "type" => "slot"],
            "wildwest-gamebeat" => ["vendor" => "gamebeat", "type" => "slot"],
            "wildwest-apparat" => ["vendor" => "apparat", "type" => "slot"],
            "wildwest-caleta" => ["vendor" => "caleta", "type" => "slot"],
            "wildwest-yggdrasil" => ["vendor" => "yggdrasil", "type" => "slot"],
            "wildwest-booming" => ["vendor" => "booming", "type" => "slot"],
            "wildwest-pragmaticslots" => ["vendor" => "pragmaticslots", "type" => "slot"],
            "wildwest-playngo" => ["vendor" => "playngo", "type" => "slot"],
            "wildwest-nolimit" => ["vendor" => "nolimit", "type" => "slot"],
            "wildwest-netent" => ["vendor" => "netent", "type" => "slot"],
            "wildwest-bigtimegaming" => ["vendor" => "bigtimegaming", "type" => "slot"],
            "wildwest-3oaks" => ["vendor" => "3oaks", "type" => "slot"],
            "wildwest-pgsoft" => ["vendor" => "pgsoft", "type" => "slot"],
            "wildwest-platipus" => ["vendor" => "platipus", "type" => "slot"],
            "wildwest-readyplay" => ["vendor" => "readyplay", "type" => "slot"],
            "wildwest-endorphina" => ["vendor" => "endorphina", "type" => "slot"],
            "wildwest-spinomenal" => ["vendor" => "spinomenal", "type" => "slot"],
            "wildwest-4theplayer" => ["vendor" => "4theplayer", "type" => "slot"],
            "wildwest-backseatgaming" => ["vendor" => "backseatgaming", "type" => "slot"],
            "wildwest-bullsharkgames" => ["vendor" => "bullsharkgames", "type" => "slot"],
            "wildwest-mascot" => ["vendor" => "mascot", "type" => "slot"],
            "wildwest-evoplay" => ["vendor" => "evoplay", "type" => "slot"],
            "wildwest-jelly" => ["vendor" => "jelly", "type" => "slot"],
            "wildwest-petersons" => ["vendor" => "petersons", "type" => "slot"],
            "wildwest-pragmaticplay" => ["vendor" => "pragmaticplay", "type" => "slot"],
            "wildwest-bulletproof" => ["vendor" => "bulletproof", "type" => "slot"],
            "wildwest-bgaming" => ["vendor" => "bgaming", "type" => "slot"],
            "wildwest-aceroll" => ["vendor" => "aceroll", "type" => "slot"],
        ];
        public static function getGameObj($uuid)
        {
            foreach (WILDWESTController::WILDWEST_GAME_IDENTITY as $ref => $value) {
                $gamelist = WILDWESTController::getgamelist($ref);
                if ($gamelist) {
                    foreach ($gamelist as $game) {
                        if ($game['gamecode'] == $uuid) {
                            return $game;
                        }
                    }
                }
            }
            return null;
        }
        public static function sendRequest($param)
        {
            Log::info(json_encode($param));

            $url = config('app.wildwest_api');
            $agent = config('app.wildwest_agent');
            $pass = config('app.wildwest_secretkey');
            $param['api_login'] = $agent;
            $param['api_password'] = $pass;
            try {
                $response = Http::withBody(json_encode($param), 'application/json')->post($url);
                if ($response->ok()) {
                    $res = $response->json();
                    return $res;
                } else {
                    Log::error('WildWest Request : response is not okay. api=>' . $url . ', param =>' . json_encode($param) . ', body=>' . $response->body());
                }
            } catch (\Exception $ex) {
                Log::error('WildWest Request :  Exception. exception= ' . $ex->getMessage());
                Log::error('WildWest Request :  Exception. PARAMS= ' . json_encode($param));
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
            $category = WILDWESTController::WILDWEST_GAME_IDENTITY[$href];

            $param = [
                "method" => "getGameList",
                "show_additional" => true,
                "show_systems" => 0,
                "list_type" => 1,
                "currency" => auth()->user() && auth()->user()->shop->currency ? auth()->user()->shop->currency : "USD"
            ];

            $gameList = [];
            $data = WILDWESTController::sendRequest($param);
            $view = 1;
            if ($data && $data['error'] == 0) {
                foreach ($data['response'] as $game) {
                    if ($game['category'] != $category['vendor']) {
                        continue;
                    }
                    $type = 'slot';
                    if ($game['type'] == 'live') {
                        $type = 'table';
                    }
                    array_push($gameList, [
                        'provider' => self::WILDWEST_PROVIDER,
                        'vendorKey' => $category['vendor'],
                        'href' => $href,
                        'gameid' => $game['id'],
                        'gamecode' => $game['id_hash'],
                        'symbol' => $game['id_hash_parent'] ?? $game['id_hash'],
                        'name' => preg_replace('/\s+/', '', $game['name']),
                        'title' => $game['name'],
                        'type' => $type,
                        'currency' => $game['currency'],
                        'icon' => $game['image'],
                        'view' => $view
                    ]);
                }
            }

            \Illuminate\Support\Facades\Redis::set($href . 'list', json_encode($gameList));
            return $gameList;

        }

        public static function makegamelink($gamecode, $prefix = self::WILDWEST_PROVIDER, $user = null)
        {
            $game = WILDWESTController::getGameObj($gamecode);
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

            
            $param = [
                "method" => "playerExists",
                "user_username" => $user_code,
                "currency" => $user->shop->currency ?? "USD"
            ];
            $data = WILDWESTController::sendRequest($param);
            if ($data == null || $data['error'] != 0) {
                
                $params = [
                    "method" => "createPlayer",
                    "user_username" => $user_code,
                    "user_password" => $user_code,
                    "currency" => $user->shop->currency ?? "USD"
                ];

                $data = WILDWESTController::sendRequest($params);
                if ($data == null || $data['error'] != 0) {
                    Log::error('WILDWESTMakeLink (makegamelink User Id) Input Data' . $user->id);
                    Log::error('WILDWESTMakeLink (makegamelink User Code) Input Data' . $user_code);
                    Log::error('WILDWESTMakeLink (makegamelink) : Player Create, msg=  ' . $data['message']);
                    return null;
                }
            }

            $params = [
                "method" => "getGame",
                "lang" => $user->language ? $user->language : 'en',
                "user_username" => $user_code,
                "user_password" => $user_code,
                "gameid" => $game['gamecode'],
                "homeurl" => config('app.url'),
                "cashierurl" => config('app.url'),
                "play_for_fun" => 0,
                "currency" => $user->shop->currency ?? "USD"
            ];

            $data = WILDWESTController::sendRequest($params);
            $url = null;

            if ($data && $data['error'] == 0) {
                $url = $data['response'];
            } else {
                Log::error('WILDWESTMakeLink : geturl, msg=  ' . $data['msg']);
            }

            return $url;
        }


        public static function getgamelink($gamecode)
        {
            if (isset(self::WILDWEST_GAME_IDENTITY[$gamecode])) {
                $gamelist = WILDWESTController::getgamelist($gamecode);
                if (count($gamelist) > 0) {
                    foreach ($gamelist as $g) {
                        if ($g['view'] == 1) {
                            $gamecode = $g['gamecode'];
                            break;
                        }
                    }

                }
            }
            $url = WILDWESTController::makegamelink($gamecode);
            if ($url) {
                return ['error' => false, 'data' => ['url' => $url]];
            }
            return ['error' => true, 'msg' => 'Game Launch Error'];
        }


        // callback for seamless
        public function callback(\Illuminate\Http\Request $request)
        {
            set_time_limit(0);
            $data = $request->all();
            Log::info('WILDWEST CallBack Data = ' . json_encode($data));
            if (!isset($data['key']) || $data['key'] != md5($data['timestamp'] . config('app.wildwest_saltkey'))) {
                Log::error('WILDWEST CallBack : Invalid ApiKey. PARAMS= ' . json_encode($data));
                return response()->json([
                    "error" => 2,              
                    "balance" => 0,
                ]);
            }
            if (!isset($data['username'])) {
                Log::error('WILDWEST CallBack : No params. PARAMS= ' . json_encode($data));
                return response()->json([
                    "error" => 2,               
                    "balance" => 0,
                ]);
            }

            $username = $data['username'];
            $userid = preg_replace('/' . self::WILDWEST_PROVIDER . '(\d+)/', '$1', $username);

            $user = \App\Models\User::where(['id' => $userid, 'role_id' => 1])->first();

            if (!$user) {
                Log::error('WILDWEST CallBack : Not found user. PARAMS= ' . json_encode($data));
                return response()->json([
                    "error" => 2,               
                    "balance" => 0,
                ]);
            }
            if ($user->api_token == 'playerterminate') {
                Log::error('WILDWEST CallBack : terminated by admin. PARAMS= ' . json_encode($data));
                return response()->json([
                    "error" => 2,               
                    "balance" => 0,
                    "message" => 'terminated by admin',
                ]);
            }
            $action = $data['action'];
            if ($action == 'balance') {
                if ($user->shop->callback) {
                    $username = explode("#P#", $user->username)[1];
                    $response = CallbackController::userBalance($user->shop->callback, $username);

                    if ($response['status'] == 1) {
                        $user->balance = $response['balance'];
                        $user->save();

                        return response()->json([
                            "error" => 0,
                            "balance" => intval($response['balance'] * 100)
                        ]);
                    }
                    Log::error('WILDWEST CallBack : seamless balance ' . $response['msg']);
                    return response()->json([
                        "error" => 2,
                        "message" => $response['msg'],
                        "balance" => 0,
                    ]);
                }
            } 
            elseif ($action == 'credit' || $action == 'debit') {
                $bet = 0;
                $win = 0;
                $type = 'spin';
                $bettype = 'bet';
                $transactionKey = $data['call_id'];
                if ($data['action'] == 'debit') {
                    $bet = $data['amount'] / 100;
                } else {
                    $bettype = 'win';
                    $win = $data['amount'] / 100;
                }

                if ($data['type'] == 'bonus_fs') {
                    $type = 'bonus';
                }

                // $time = Carbon::parse((string)$data['timestamp'])->format('Y-m-d H:i:s');
                $gameObj = WILDWESTController::getGameObj($data['game_id']);
                $shop = \App\Models\ShopUser::where('user_id', $user->id)->first();
                $category = \App\Models\Category::where('href', $gameObj['href'])->first();

                $checkGameStat = \App\Models\StatGame::where([
                    'user_id' => $user->id,
                    'bettype' => $bettype,
                    // 'date_time' => $time,
                    'roundid' => $data['round_id'] . '#' . $data['game_id'] . '#' . $transactionKey,
                ])->first();
                if ($checkGameStat) {
                    Log::error('WILDWEST CallBack : duplicate data ' . json_encode($data));
                    return response()->json([
                        "error" => 0,             
                        "balance" => intval($user->balance * 100),
                    ]);
                }
                \DB::beginTransaction();
                $user = \App\Models\User::lockforUpdate()->where(['id' => $user->id, 'role_id' => 1])->first();
                $beforeBalance = $user->balance;
                if ($bettype == 'bet') {
                    if ($type == 'spin') {
                        if ($user->balance < intval($bet)) {
                            \DB::commit();
                            Log::error('WILDWEST CallBack Balance : insufficient balance. PARAMS= ' . json_encode($data));
                            return response()->json([
                                "error" => 2,              
                                "balance" => intval($user->balance * 100),
                                "message" => 'insufficient balance',
                            ]);
                        } else {
                            $user->balance = $user->balance - $bet;
                        }
                    }
                } else {
                    $user->balance = $user->balance + $win;
                }
                $user->save();
                $gamename = $gameObj['name'] . '_wildwest';

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
                    // 'date_time' => $time,
                    'shop_id' => $shop ? $shop->shop_id : -1,
                    'category_id' => $category ? $category->original_id : 0,
                    'game_id' => $gameObj['gamecode'],
                    // 'status' =>  $gameObj['isFinished']==true ? 1 : 0,
                    'roundid' => $data['round_id'] . '#' . $data['game_id'] . '#' . $transactionKey,
                    'transactionid' => $transactionKey,
                ]);
                Log::debug('WILDWEST CallBack  : cretae Stat Game');
                \DB::commit();
            }

            Log::debug('WILDWEST CallBack  : Player Balance = ' . $user->balance);
            return response()->json([
                "error" => 0,               
                "balance" => intval($user->balance * 100),
            ]);
        }
    }
}
