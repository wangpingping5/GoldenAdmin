<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    class BNGController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */
        public static function calcSecurityHash($body)
        {
            $key = config('app.bng_wallet_sign_key');
            return hash_hmac('sha256', utf8_encode($body), utf8_encode($key));
        }

        public function checkuid($uid)
        {
            $record = \App\Models\BNGTransaction::where('uid',$uid)->get()->first();
            return $record;
        }
        public function generateCode($limit){
            $code = 0;
            for($i = 0; $i < $limit; $i++) { $code .= mt_rand(0, 9); }
            return $code;
        }


        public function microtime_string()
        {
            $microstr = sprintf('%.4f', microtime(TRUE));
            $microstr = str_replace('.', '', $microstr);
            return $microstr;
        }

        public static function gamecodetoname($code)
        {
            $gamelist = BNGController::getgamelist('booongo');
            $gamelist1 = BNGController::getgamelist('playson');
            if ($gamelist1){
                $gamelist = array_merge_recursive($gamelist, $gamelist1);
            }
            $gamename = $code;
            if ($gamelist)
            {
                foreach($gamelist as $game)
                {
                    if ($game['gamecode'] == $code)
                    {
                        $gamename = $game['name'];
                        break;
                    }
                }
            }
            return $gamename;
        }
        /*
        * FROM Booongo, BACK API
        */

        public function betman(\Illuminate\Http\Request $request)
        {
            $data = json_decode($request->getContent(), true);
            $name = $data['name'];
            $uid = $data['uid'];
            $c_at = $data['c_at'];
            $sent_at = $data['sent_at'];
            $session = $data['session'];
            $args = $data['args'];
            $c_at = date(sprintf('Y-m-d\TH:i:s%sP', substr(microtime(), 1, 8)));

            $record = $this->checkuid($uid);
            if ($record)
            {
                $securityhash = BNGController::calcSecurityHash($record->response);
                return response($record->response, 200)->withHeaders([
                    'Content-Type' => 'application/json',
                    'Security-Hash' => $securityhash
                ]);
            }
            \DB::beginTransaction();
            $response = [];
            switch ($name)
            {
                case 'login':
                    $response = $this->login($data);
                    break;
                case 'transaction':
                    $response = $this->transaction($data);
                    break;
                case 'rollback':
                    $response = $this->rollback($data);
                    break;
                case 'getbalance':
                    $response = $this->getbalance($data);
                    break;
                case 'logout':
                    $response = $this->logout($data);
                    break;
                default:
                    $response = ['uid' => $uid, 'error' => ['code' => "FATAL_ERROR"]];
            }

            $transaction = \App\Models\BNGTransaction::create([
                'uid' => $uid, 
                'timestamp' => $this->microtime_string(),
                'data' => $request->getContent(),
                'response' => json_encode($response)
            ]);
            $sent_at = date(sprintf('Y-m-d\TH:i:s%sP', substr(microtime(), 1, 8)));
            $response['c_at'] = $c_at;
            $response['sent_at'] = $sent_at;
            $securityhash = BNGController::calcSecurityHash(json_encode($response));
            \DB::commit();
            return response()->json($response, 200)->header('Security-Hash', $securityhash);
        }

        public function login($data)
        {
            $uid = $data['uid'];
            $token = $data['token'];
            $user = \App\Models\User::lockForUpdate()->where('api_token',$token)->get()->first();
            if (!$user || !$user->hasRole('user')){
                return [
                    'uid' => $uid,
                    'error' => [ 'code' => 'INVALID_TOKEN']];
            }

            $is_test = str_contains($user->username, 'testfor');

            return [
                'uid' => $uid,
                'player' => [
                    'id' => strval($user->id),
                    'brand' => config('app.bng_brand'),
                    'currency' => 'KRW',
                    'mode' => 'REAL',
                    'is_test' => $is_test,
                ],
                'balance' => [
                    'value' => strval($user->balance),
                    'version' => time()
                ],
                'tag' => ''
            ];
        }

        public function transaction($data)
        {
            $uid = $data['uid'];
            $args = $data['args'];
            $gamename = $data['game_name'];
            $game_id = $data['game_id'];
            $provider_name = $data['provider_name'];
            $userId = $args['player']['id'];
            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')  || $user->playing_game != null || $user->remember_token != $user->api_token){
                return [
                    'uid' => $uid,
                    'error' => [ 'code' => 'SESSION_CLOSED']];
            }
            $bet = 0;
            $win = 0;
            if (!isset($args['bonus']) && $args['bet'])
            {
                if ($user->balance < $args['bet'])
                {
                    return [
                        'uid' => $uid,
                        'balance' => [
                            'value' => strval($user->balance),
                            'version' => time()
                        ],
                        'error' => [ 'code' => 'FUNDS_EXCEED']];
                }

                // $user->balance = floatval(sprintf('%.4f', $user->balance - floatval($args['bet'])));
                $bet = floatval($args['bet']);
            }
            if ($args['win'])
            {
                // $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($args['win'])));
                $win = floatval($args['win']);
            }
            $user->balance = $user->balance - $bet + $win;
            $user->save();
            $user = $user->fresh();

            $category = \App\Models\Category::where(['provider' => 'bng', 'shop_id' => 0, 'href' => $provider_name])->first();

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => $bet, 
                'win' => $win, 
                'game' => preg_replace('/[_\s]+/', '', $gamename) . '_bng',
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => $game_id,
                'roundid' => 0,
            ]);

            return [
                'uid' => $uid,
                'balance' => [
                    'value' => strval($user->balance),
                    'version' => time()
                ],
            ];
        }

        public function rollback($data)
        {
            $uid = $data['uid'];
            $gamename = $data['game_name'];
            $args = $data['args'];
            $transaction_uid = $args['transaction_uid'];
            $userId = $args['player']['id'];
            $game_id = $data['game_id'];
            $provider_name = $data['provider_name'];

            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                return [
                    'uid' => $uid,
                    'error' => [ 'code' => 'FATAL_ERROR']];
            }

            $record = $this->checkuid($transaction_uid);
            if (!$record)
            {
                return [
                    'uid' => $uid,
                    'balance' => [
                        'value' => strval($user->balance),
                        'version' => time()
                    ],
                ];
            }
            if ($record->refund > 0)
            {
                return [
                    'uid' => $uid,
                    'balance' => [
                        'value' => strval($user->balance),
                        'version' => time()
                    ],
                ];
            }
            $bet = 0;
            $win = 0;

            if ($args['bet'])
            {
                $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($args['bet'])));
                $bet = floatval($args['bet']);
            }

            if ($args['win'])
            {
                if ($user->balance < $args['win'])
                {
                    return [
                        'uid' => $uid,
                        'balance' => [
                            'value' => strval($user->balance),
                            'version' => time()
                        ],
                        'error' => [ 'code' => 'FUNDS_EXCEED']];
                }
                $user->balance = floatval(sprintf('%.4f', $user->balance - floatval($args['win'])));
                $win = floatval($args['win']);
            }

            $category = \App\Models\Category::where(['provider' => 'bng', 'shop_id' => 0, 'href' => $provider_name])->first();

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => $win, 
                'win' => $bet, 
                'game' => preg_replace('/[_\s]+/', '', $gamename) . '_bng RB',
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => $game_id,
                'roundid' => 0,
            ]);
            $user->save();
            $record->refund = 1;
            $record->save();
            return [
                'uid' => $uid,
                'balance' => [
                    'value' => strval($user->balance),
                    'version' => time()
                ],
            ];
            

        }

        public function getbalance($data)
        {
            $uid = $data['uid'];
            $args = $data['args'];
            $userId = $args['player']['id'];
            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                return [
                    'uid' => $uid,
                    'error' => [ 'code' => 'FATAL_ERROR']];
            }

            return [
                'uid' => $uid,
                'balance' => [
                    'value' => strval($user->balance),
                    'version' => time()
                ],
            ];

        }

        public function logout($data)
        {
            $uid = $data['uid'];
            return [
                'uid' => $uid,
            ];
        }
        /*
        * FROM CONTROLLER, API
        */
        
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

            $data = [
                'api_token' => config('app.bng_api_token'),
            ];
            $reqbody = json_encode($data);
            $response = Http::withHeaders([
                'Security-Hash' => BNGController::calcSecurityHash($reqbody)
                ])->withBody($reqbody, 'text/plain')->post(config('app.bng_game_server') . config('app.bng_project_name') . '/api/v1/provider/list/');
            if (!$response->ok())
            {
                return [];
            }
            $providerId = 1;
            $data = $response->json();
            $gameList = [];

            if (isset($data['items'])){
                foreach ($data['items'] as $item)
                {
                    if ($item['provider_name'] == $href)
                    {
                        $providerId = $item['provider_id'];
                        break;
                    }
                }
            }

            $data1 = [
                'api_token' => config('app.bng_api_token'),
                'provider_id' => $providerId
            ];
            $reqbody = json_encode($data1);
            $response = Http::withHeaders([
                'Security-Hash' => BNGController::calcSecurityHash($reqbody)
                ])->withBody($reqbody, 'text/plain')->post(config('app.bng_game_server') . config('app.bng_project_name') . '/api/v1/game/list/');
            
            if (!$response->ok())
            {
                return [];
            }
            $data1 = $response->json();
            foreach ($data1['items'] as $game)
            {
                if ($game['type'] == "SLOT" && $game['game_id'] != 116) // 116 = roulettewithtracklowpls
                {
                    $gameList[] = [
                        'provider' => 'bng',
                        'gamecode' => $game['game_id'],
                        'name' => preg_replace('/[_\s]+/', '', $game['game_name']),
                        'title' => __('gameprovider.'.$game['i18n']['en']['title']),
                        'icon' => $game['i18n']['en']['banner_path'],
                        //'demo' => BNGController::makegamelink($game['game_id'], "fun")
                    ];
                }
            }
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
            
        }

        public static function makegamelink($gamecode, $mode)
        {
            $detect = new \Detection\MobileDetect();
            $user = auth()->user();
            if ($user == null)
            {
                return null;
            }
            $key = [
                'token' => $user->api_token,
                'game' => $gamecode,
                'ts' => time(),
                'lang' => 'ko',
                'platform' => ($detect->isMobile() || $detect->isTablet())?'mobile':'desktop',
            ];
            if ($mode == "fun")
            {
                $key['wl'] = 'demo';
            }
            else
            {
                $key['wl'] = config('app.bng_wl');
            }
            $str_params = implode('&', array_map(
                function ($v, $k) {
                    return $k.'='.$v;
                }, 
                $key,
                array_keys($key)
            ));
            return $url = config('app.bng_game_server') . config('app.bng_project_name') . '/game.html?'.$str_params;
        }

        public static function getgamelink($gamecode)
        {
            return ['error' => false, 'data' => ['url' => route('frontend.providers.bng.render', $gamecode)]];
            // $url = BNGController::makegamelink($gamecode, "real");
            // if ($url)
            // {
            //     return ['error' => false, 'data' => ['url' => $url]];
            // }
            // return ['error' => true, 'msg' => '로그인하세요'];
            
        }

        public static function bonuscreate($data)
        {
            $requestdata = [
                'api_token' => config('app.bng_api_token'),
                'mode' => 'REAL',
                'campaign' => $data['campaign'],
                'game_id' => strval($data['game_id']),
                'bonus_type' => 'FLEXIBLE_FREEBET',
                'currency' => 'KRW',
                'total_bet' => strval($data['total_bet']),
                'start_date' => null,
                'end_date' => $data['end_date'],
                'bonuses' => [
                    [
                        'player_id' => $data['player_id'],
                        'ext_bonus_id' => '',
                        'brand' => config('app.bng_brand')
                    ]
                ]
            ];

            $reqbody = json_encode($requestdata);
            $response = Http::withHeaders([
                'Security-Hash' => BNGController::calcSecurityHash($reqbody)
                ])->withBody($reqbody, 'text/plain')->post(config('app.bng_game_server') . config('app.bng_project_name') . '/api/v1/bonus/create/');
            if (!$response->ok())
            {
                return null;
            }
            $res = $response->json();
            return $res['items'][0];
        }

        public static function bonuscancel($bonus_id)
        {
            $requestdata = [
                'api_token' => config('app.bng_api_token'),
                'bonus_id' => intval($bonus_id)
            ];

            $reqbody = json_encode($requestdata);
            $response = Http::withHeaders([
                'Security-Hash' => BNGController::calcSecurityHash($reqbody)
                ])->withBody($reqbody, 'text/plain')->post(config('app.bng_game_server') . config('app.bng_project_name') . '/api/v1/bonus/delete/');
            if (!$response->ok())
            {
                return null;
            }
            $res = $response->json();
            return $res;
        }

        public static function bonuslist()
        {
            
            $requestdata = [
                'api_token' => config('app.bng_api_token'),
                'mode' => 'REAL',
                'campaign' => null,
                'game_id' => null,
                'player_id' => null,
                'brand' => config('app.bng_brand'),
                'include_expired' => true,
                'fetch_size' => 100,
                'fetch_state' => null
            ];

            $reslist = [];
            $bcontinue = true;
            while ($bcontinue) {
                $reqbody = json_encode($requestdata);
                $response = Http::withHeaders([
                    'Security-Hash' => BNGController::calcSecurityHash($reqbody)
                    ])->withBody($reqbody, 'text/plain')->post(config('app.bng_game_server') . config('app.bng_project_name') . '/api/v1/bonus/list/');
                if (!$response->ok())
                {
                    break;
                }
                $res = $response->json();
                $reslist = array_merge($reslist, $res['items']);
                if ($res['fetch_state'] === null)
                {
                    $bcontinue = false;
                }
                else
                {
                    $requestdata['fetch_state'] = $res['fetch_state'];
                }
            }
            return $reslist;
        }


        ///BNG request

        public function booongo_process(\Illuminate\Http\Request $request){
            return '[]';
        }
        public function booongo_desktoplog(\Illuminate\Http\Request $request){
            return '[]';
        }
        public function booongo_mobilelog(\Illuminate\Http\Request $request){
            return '[]';
        }
        public function booongo_game_list(\Illuminate\Http\Request $request){
            $data = file(base_path() . '/public/op/major/assets/gamelist.json');
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public function booongo_transaction_list(\Illuminate\Http\Request $request){
            if(!isset($request->player_id) || !isset($request->game_id)){
                return '[]';
            }
            $fetchSize = 100;
            if(isset($request->fetch_size)){
                $fetchSize = $request->fetch_size;
            }
            $fetch_state = 1;
            if(isset($request->fetch_state)){
                $fetch_state = $request->fetch_state;
                $request->page = $fetch_state;
            }
            $bngLoges = \App\Models\BNGGameLog::where([
                'player_id' => $request->player_id, 
                'game_id' => $request->game_id
            ]);
            if(isset($request->round_id)){
                $bngLoges = $bngLoges->where('round_id', $request->round_id);
            }
            $bngLoges = $bngLoges->orderBy('c_at', 'DESC');
            $totalCount = $bngLoges->count(); 
            $paginator = $bngLoges->skip(($fetch_state - 1) * $fetchSize)->take($fetchSize)->get();
            $array = $paginator->toArray();
            $data = [
                'items' => $array
            ];
            // if($array['next_page_url'] != NULL){
            //     $nextPageLink = $array['next_page_url'];
            //     $data['fetch_state'] = explode('page=', $nextPageLink)[1];
            // }
            if(($fetch_state - 1) * $fetchSize + count($array) < $totalCount){
                $data['fetch_state'] = $fetch_state + 1;
            }
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        
        public function booongo_aggregate(\Illuminate\Http\Request $request){
            if(!isset($request->player_id) || !isset($request->game_id)){
                return '[]';
            }
            $bngLoges = \App\Models\BNGGameLog::whereRaw('game_id=? and player_id=? ORDER BY c_at DESC', [
                $request->game_id, 
                $request->player_id
            ])->get();
           
            $bets = 0; 
            $wins = 0;
            $rounds = [];
            foreach( $bngLoges as $log ) 
            {
                if($log->bet != NULL){
                    $bets += $log->bet;
                }
                $wins += $log->win;
                array_push($rounds, $log->round_id);
            }
            $rounds = array_unique($rounds);
            if($bets == 0){
                $percent = '0';
            }else{
                $percent = sprintf('%0.2f', ($wins / $bets) * 100);
            }

            $data = '{"bets": "' . $bets . '", "wins": "' . $wins . '", "profit": "' . ($bets - $wins) . '", "outcome": "' . ($wins - $bets) . '", "payout": "' . $percent . '", "rounds": "'. count($rounds) .'", "transactions": "'. count($bngLoges) .'"}';
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public function booongo_draw_log(\Illuminate\Http\Request $request, $transaction_id)
        {
            if(!isset($transaction_id)){
                return '[]';
            }
            $bngLog = \App\Models\BNGGameLog::where([
                'transaction_id' => $transaction_id
            ])->first();
            if($bngLog == NULL){
                return '[]';
            }
            $gameName = $bngLog->game_name;
            $details = $bngLog->log;
            return view('frontend.Default.games.bng.' . $gameName, compact('details'));
        }
    }

}
