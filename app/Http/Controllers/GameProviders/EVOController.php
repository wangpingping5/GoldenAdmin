<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    class EVOController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        public function checktransaction($id)
        {
            $record = \App\Models\EVOTransaction::Where('transactionId',$id)->first();
            return $record;
        }

        public static function microtime_string()
        {
            $microstr = sprintf('%.4f', microtime(TRUE));
            $microstr = str_replace('.', '', $microstr);
            return $microstr;
        }

        public static function getGameObj($code)
        {
            
            $gamelist_rt = EVOController::getgamelist('rt');
            $gamelist_ne = EVOController::getgamelist('ne');
            $gamelist_live = EVOController::getgamelist('evo');

            $gamelist = $gamelist_rt;
            $gamelist = array_merge_recursive($gamelist, $gamelist_ne);
            $gamelist = array_merge_recursive($gamelist, $gamelist_live);

            if ($gamelist)
            {
                foreach($gamelist as $game)
                {
                    if ($game['gamecode'] == $code)
                    {
                        return $game;
                        break;
                    }
                    if (isset($game['gamecode1']) && ($game['gamecode1'] == $code))
                    {
                        return $game;
                        break;
                    }
                }
            }
            return null;
        }

        /*
        * FROM Evolution, BACK API
        */

        public function endpoint($service, \Illuminate\Http\Request $request)
        {
            $authToken = $request->authToken;
            $slottoken = config('app.evo_authtoken_slot');
            $livetoken = config('app.evo_authtoken_live');
            if ($authToken==null || ($authToken != $slottoken && $authToken != $livetoken))
            {
                //auth is not valid
                return response()->json([
                    'status' => 'INVALID_TOKEN_ID',
                    'sid' => null,
                    'uuid' => EVOController::microtime_string(),
                ]);
            }
            $data = json_decode($request->getContent(), true);
            switch ($service)
            {
                case 'check':
                    $response = $this->check($data);
                    break;
                case 'balance':
                    $response = $this->balance($data);
                    break;
                case 'debit':
                    $response = $this->debit($data);
                    break;
                case 'credit':
                    $response = $this->credit($data);
                    break;
                case 'cancel':
                    $response = $this->cancel($data);
                    break;
                default :
                    $response = [
                        'status' => 'UNKNOWN_ERROR',
                        'sid' => null,
                        'uuid' => EVOController::microtime_string(),
                    ];
                    break;
            }
            $resjson = json_encode($response);
            $pattern = '/("balance":)"([.\d]+)"/x';
            $replacement = '${1}${2}';
            return response(preg_replace($pattern, $replacement, $resjson), 200)->header('Content-Type', 'application/json');
            
        }

        public function check($data)
        {
            $token = $data['sid'];
            $userid = $data['userId'];
            $response = [
                'status' => 'OK',
                'sid' => null,
                'uuid' => EVOController::microtime_string(),
            ];

            $user = \App\Models\User::where('id',$userid)->first();
            if (!$user || !$user->hasRole('user') || $user->api_token != $token){
                $response['status'] = 'INVALID_SID';
                return $response;
            }
            $response['sid'] = $user->api_token;
            return $response;
        }


        public function debit($data)
        {
            $token = $data['sid'];
            $userid = $data['userId'];
            $tableid = $data['game']['details']['table']['id'];
            $amount = $data['transaction']['amount'];
            $trid = $data['transaction']['id'];
            $roundid =  $data['game']['id'];

            $response = [
                'status' => 'OK',
                'balance' => null,
                'bonus' => 0,
                'uuid' => EVOController::microtime_string(),
            ];

            $user = \App\Models\User::find($userid);
            if (!$user || !$user->hasRole('user') || $user->api_token != $token){
                $response['status'] = 'INVALID_SID';
                return $response;
            }
            $record = $this->checktransaction($trid);
            if ($record)
            {
                $response['status'] = 'BET_ALREADY_EXIST';
                $response['balance'] = number_format ($user->balance,2,'.','');
                return $response;
            }

            if ($amount > 0){
                if ($user->balance < $amount){
                    $response['status'] = 'INSUFFICIENT_FUNDS';
                    $response['balance'] = number_format ($user->balance,2,'.','');
                    return $response;
                }
                $user->balance = floatval(sprintf('%.4f', $user->balance - floatval($amount)));
                $user->save();

                $game = $this->getGameObj($tableid);
                if (!$game)
                {
                    $game = $this->getGameObj('unknowntable');
                    //save original tableid to roundid
                    $roundid = $roundid . '-' . $tableid;
                    $tableid = 'unknowntable';
                    
                }
                
                $category = \App\Models\Category::where(['provider' => 'evo', 'shop_id' => 0, 'href' => $game['href']])->first();

                \App\Models\StatGame::create([
                    'user_id' => $user->id, 
                    'balance' => floatval($user->balance), 
                    'bet' => $amount, 
                    'win' => 0, 
                    'game' => $game['name'] . '_' . $game['href'], 
                    'type' => ($game['type']=='slot')?'slot':'table',
                    'percent' => 0, 
                    'percent_jps' => 0, 
                    'percent_jpg' => 0, 
                    'profit' => 0, 
                    'denomination' => 0, 
                    'shop_id' => $user->shop_id,
                    'category_id' => isset($category)?$category->id:0,
                    'game_id' => $tableid,
                    'roundid' => $roundid,
                ]);
            }

            $response['balance'] = number_format ($user->balance,2,'.','');

            \App\Models\EVOTransaction::create([
                'transactionId' => $trid, 
                'timestamp' => EVOController::microtime_string(),
                'data' => json_encode($data),
                'response' => json_encode($response)
            ]);
            return $response;
        }

        public function credit($data)
        {
            $token = $data['sid'];
            $userid = $data['userId'];
            $tableid = $data['game']['details']['table']['id'];
            $amount = $data['transaction']['amount'];
            $trid = $data['transaction']['id'];
            $roundid =  $data['game']['id'];

            $response = [
                'status' => 'OK',
                'balance' => null,
                'bonus' => 0,
                'uuid' => EVOController::microtime_string(),
            ];

            $user = \App\Models\User::find($userid);
            if (!$user || !$user->hasRole('user') || $user->api_token != $token || $user->playing_game == 'pp'){
                $response['status'] = 'INVALID_SID';
                return $response;
            }

            $record = $this->checktransaction($trid);
            if ($record)
            {
                $response['status'] = 'BET_ALREADY_EXIST';
                $response['balance'] = number_format ($user->balance,2,'.','');
                return $response;
            }

            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
            $user->save();

            $game = $this->getGameObj($tableid);
            if (!$game)
            {
                $game = $this->getGameObj('unknowntable');
                //save original tableid to roundid
                $roundid = $roundid . '-' . $tableid;
                $tableid = 'unknowntable';
                
            }
            $category = \App\Models\Category::where(['provider' => 'evo', 'shop_id' => 0, 'href' => $game['href']])->first();
            $gamename = '';
            if ($game){
                $gamename = $game['name'] . '_' . $game['href'];
            }

            //check if result is tie
            $betamount = 0;
            $betrounds = \App\Models\StatGame::where(['user_id' => $user->id, 'roundid' => $roundid, 'game_id' => $tableid])->get();
            if (count($betrounds) > 0)
            {
                $betamount = $betrounds->sum('bet');
            }
            if ($betamount == $amount) // this is tie
            {
                $gamename = $gamename . '_tie';
            }
            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => $amount, 
                'game' =>  $gamename, 
                'type' => ($game['type']=='slot')?'slot':'table',
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => $tableid,
                'roundid' => $roundid,
            ]);

            $response['balance'] = number_format ($user->balance,2,'.','');

            \App\Models\EVOTransaction::create([
                'transactionId' => $trid, 
                'timestamp' => EVOController::microtime_string(),
                'data' => json_encode($data),
                'response' => json_encode($response)
            ]);
            return $response;
        }

        public function cancel($data)
        {
            $token = $data['sid'];
            $userid = $data['userId'];
            $tableid = $data['game']['details']['table']['id'];
            $amount = $data['transaction']['amount'];
            $trid = $data['transaction']['id'];
            $roundid =  $data['game']['id'];

            $response = [
                'status' => 'OK',
                'balance' => null,
                'bonus' => 0,
                'uuid' => EVOController::microtime_string(),
            ];

            $user = \App\Models\User::find($userid);
            if (!$user || !$user->hasRole('user')){
                $response['status'] = 'INVALID_SID';
                return $response;
            }

            $record = $this->checktransaction($trid);
            if (!$record)
            {
                $response['status'] = 'BET_DOES_NOT_EXIST';
                return $response;
            }
            $tdata = json_decode($record->data, true);
            $amount = $tdata['transaction']['amount'];

            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
            $user->save();

            $game = $this->getGameObj($tableid);
            if (!$game)
            {
                $game = $this->getGameObj('unknowntable');
                //save original tableid to roundid
                $roundid = $roundid . '-' . $tableid;
                $tableid = 'unknowntable';
                
            }
            
            $category = \App\Models\Category::where(['provider' => 'evo', 'shop_id' => 0, 'href' => $game['href']])->first();

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => $amount, 
                'game' => $game['name'] . '_' . $game['href'] . '_refund', 
                'type' => ($game['type']=='slot')?'slot':'table',
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => $tableid,
                'roundid' => $roundid,
            ]);

            $response['balance'] = number_format ($user->balance,2,'.','');
            
            return $response;
        }

        public function balance($data)
        {
            $userId = $data['userId'];
            $token = $data['sid'];
            $response = [
                'status' => 'OK',
                'balance' => null,
                'bonus' => 0,
                'uuid' => EVOController::microtime_string(),
            ];


            $user = \App\Models\User::find($userId);
            if (!$user || !$user->hasRole('user') || $user->api_token != $token){
                $response['status'] = 'INVALID_TOKEN_ID';
                return $response;
            }

            $response['balance'] = number_format ($user->balance,2,'.','');
            return $response;

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
            $gameList = [];
            $newgames = \App\Models\NewGame::where('provider', 'evo')->get()->pluck('gameid')->toArray();
            $query = 'SELECT * FROM w_provider_games WHERE provider="evo' . $href .'"';
            $evo_games = \DB::select($query);
            foreach ($evo_games as $game)
            {
                if ($href == 'evo')
                {
                    $icon_name = trim($game->name);
                }
                else
                {
                    $icon_name = str_replace(' ', '_', $game->gameid);
                    $icon_name = strtolower(preg_replace('/\s+/', '', $icon_name));
                }
                $iconfullpath = '/frontend/Default/ico/evo/'. $href . '/' . $icon_name . '.jpg';
                if (!file_exists(public_path($iconfullpath))) {
                    $iconfullpath = '/frontend/Default/ico/evo/'. $href . '/' . $icon_name . '.png';
                }
                if (in_array($game->gamecode , $newgames))
                {
                    array_unshift($gameList, [
                        'provider' => 'evo',
                        'gameid' => $game->gameid,
                        'gamecode' => $game->gamecode,
                        'gamecode1' => $game->gamecode1,
                        'enname' => $game->name,
                        'name' => preg_replace('/\s+/', '', $game->name),
                        'title' => $game->title,
                        'type' => $game->type,
                        'href' => $href,
                        'view' => $game->view,
                        'icon' => $iconfullpath,
                    ]);
                }
                else
                {
                    array_push($gameList, [
                        'provider' => 'evo',
                        'gameid' => $game->gameid,
                        'gamecode' => $game->gamecode,
                        'gamecode1' => $game->gamecode1,
                        'enname' => $game->name,
                        'name' => preg_replace('/\s+/', '', $game->name),
                        'title' => $game->title,
                        'type' => $game->type,
                        'href' => $href,
                        'view' => $game->view,
                        'icon' => $iconfullpath,
                        ]);
                }
            }
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
        }

        public static function makegamelink($gamecode)
        {
            $detect = new \Detection\MobileDetect();
            $user = auth()->user();
            if ($user == null)
            {
                return ['error' => true, 'data' => 'login failed'];
            }
            $api_server = config('app.evo_apihost_slot');
            $api_key = config('app.evo_casinokey_slot');
            $api_token = config('app.evo_authtoken_slot');

            $game = EVOController::getGameObj($gamecode);
            if ($game['type'] != 'slot')
            {
                $api_server = config('app.evo_apihost_live');
                $api_key = config('app.evo_casinokey_live');
                $api_token = config('app.evo_authtoken_live');
            }

            $code = ($detect->isMobile() || $detect->isTablet())?$game['gamecode1'] : $game['gamecode'];
            if ($code == null)
            {
                $code =  $game['gamecode'];
            }

            $ip = \Request::ip();
            if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ip = '192.168.1.1';
            }

            $data = [
                'uuid' => EVOController::microtime_string(),
                'player' => [
                    'id' => $user->id,
                    'update' => true,
                    'firstName' => 'major',
                    'lastName' => 'player_' . $user->id,
                    'nickName' => $user->username,
                    'country' => 'KR',
                    'language' => 'ko',
                    'currency' => 'KRW',
                    'session' => [
                        'id' => $user->api_token,
                        'ip' => $ip,
                    ],
                    'group' => [
                        'id' => config('app.evo_groupid_live'),
                        'action' => 'assign',
                    ]
                ],
                'config' => [
                    'game' => [
                        'category' => $game['type'],
                        'interface' => 'view1',
                        'table' => [
                            'id' => $code,
                        ]
                    ]
                ]
            ];
            if ($code == 'lobby') //evolution live casino lobby
            {  
                unset($data['config']['game']['table']);
            }
            $url = null;
            try {
                $response = Http::timeout(10)->post($api_server . '/ua/v1/' .  $api_key . '/' . $api_token, $data);
                if (!$response->ok())
                {
                    return ['error' => true, 'data' => $response->body()];
                }
                $data = $response->json();
                if (isset($data['entry'])){
                    $url = $data['entry'];
                }
            }
            catch (\Exception $ex)
            {
                return ['error' => true, 'data' => 'request failed'];
            }

            return ['error' => false, 'data' => ['url' => $url]];
        }

        public static function getgamelink($gamecode)
        {
            $data = EVOController::makegamelink($gamecode);
            if ($data['error'] == true)
            {
                $data['msg'] = '로그인하세요';
            }
            return $data;
        }

    }

}
