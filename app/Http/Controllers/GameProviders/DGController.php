<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class DGController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */
        const DG_PROVIDER = 'DG';
        const UNKNOWN_TABLE = 99999;
        public function checkreference($reference)
        {
            $record = \App\Models\DGTransaction::Where('reference',$reference)->get()->first();
            return $record;
        }

        public static function sign($random)
        {
            return md5(config('app.dg_agent') . config('app.dg_token') . $random);
        }


        public static function generateCode($limit){
            $code = 0;
            for($i = 0; $i < $limit; $i++) { $code .= mt_rand(0, 9); }
            return $code;
        }

        public static function getGameObj($code)
        {
            $categories = \App\Models\Category::where(['provider' => 'dg', 'shop_id' => 0, 'site_id' => 0])->get();
            $gamelist = [];
            foreach ($categories as $category)
            {
                $gamelist = DGController::getgamelist($category->href);
                if (count($gamelist) > 0 )
                {
                    foreach($gamelist as $game)
                    {
                        if ($game['gamecode'] == $code)
                        {
                            return $game;
                        }
                    }
                }
            }
            return null;
        }


        public function microtime_string()
        {
            $microstr = sprintf('%.4f', microtime(TRUE));
            $microstr = str_replace('.', '', $microstr);
            return $microstr;
        }

        /*
        * FROM DG, BACK API
        */

        public function getBalance($agentName, \Illuminate\Http\Request $request)
        {
            $configAgent = config('app.dg_agent');
            $configToken = $this->sign('');
            if ($configAgent != $agentName)
            {
                return response()->json([
                    'codeId' => 118,
                    'token' => $configToken
                ], 200);
            }
            $data = json_decode($request->getContent());

            if ($configToken != $data->token)
            {
                return response()->json([
                    'codeId' => 2,
                    'token' => $configToken
                ], 200);
            }

            $username = $data->member->username;
            $userid = preg_replace('/'. self::DG_PROVIDER .'(\d+)/', '$1', $username) ;

            $user = \App\Models\User::where('id',$userid)->first();
            if (!$user || !$user->hasRole('user')){
                return response()->json([
                    'codeId' => 102,
                    'token' => $configToken
                ], 200);
            }

            return response()->json([
                'codeId' => 0,
                'token' => $configToken,
                'member' => [
                    'username' => $username,
                    'balance' => floatval($user->balance)
                ]
            ], 200);
        }

        public function transfer($agentName, \Illuminate\Http\Request $request)
        {
            $configAgent = config('app.dg_agent');
            $configToken = $this->sign('');
            if ($configAgent != $agentName)
            {
                return response()->json([
                    'codeId' => 118,
                    'token' => $configToken
                ], 200);
            }
            $data = json_decode($request->getContent());

            if ($configToken != $data->token)
            {
                return response()->json([
                    'codeId' => 2,
                    'token' => $configToken
                ], 200);
            }

            $username = $data->member->username;
            $serial = $data->data;
            $amount = $data->member->amount;

            $userid = preg_replace('/'. self::DG_PROVIDER .'(\d+)/', '$1', $username);

            $user = \App\Models\User::lockforUpdate()->where('id',$userid)->first();
            if (!$user || !$user->hasRole('user') || $user->remember_token != $user->api_token || $user->playing_game != null){
                return response()->json([
                    'codeId' => 102,
                    'token' => $configToken
                ], 200);
            }


            $record = $this->checkreference($serial);
            if ($record)
            {
                return response()->json([
                    'codeId' => 0,
                    'token' => $configToken,
                    'member' => [
                        'username' => $username,
                        'amount' => $amount,
                        'balance' => floatval($user->balance)
                    ]
                ], 200);
            }

            $transaction = \App\Models\DGTransaction::create([
                'reference' => $serial, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($data),
                'refund' => 0
            ]);
            
            $oldBalance = $user->balance;
            $newBalance = $oldBalance + $amount;
            $user->update(['balance' => $newBalance]);

            return response()->json([
                'codeId' => 0,
                'token' => $configToken,
                'member' => [
                    'username' => $username,
                    'amount' => $amount,
                    'balance' => floatval($oldBalance)
                ]
            ], 200);
        }

        public function checkTransfer($agentName, \Illuminate\Http\Request $request)
        {
            $configAgent = config('app.dg_agent');
            $configToken = $this->sign('');
            if ($configAgent != $agentName)
            {
                return response()->json([
                    'codeId' => 118,
                    'token' => $configToken
                ], 200);
            }
            $data = json_decode($request->getContent());

            if ($configToken != $data->token)
            {
                return response()->json([
                    'codeId' => 2,
                    'token' => $configToken
                ], 200);
            }

            $serial = $data->data;

            $record = $this->checkreference($serial);
            if (!$record)
            {
                return response()->json([
                    'codeId' => 98,
                    'token' => $configToken,
                ], 200);
            }

            return response()->json([
                'codeId' => 0,
                'token' => $configToken,
            ], 200);
        }

        public function inform($agentName, \Illuminate\Http\Request $request)
        {
            $configAgent = config('app.dg_agent');
            $configToken = $this->sign('');
            if ($configAgent != $agentName)
            {
                return response()->json([
                    'codeId' => 118,
                    'token' => $configToken
                ], 200);
            }
            $data = json_decode($request->getContent());

            if ($configToken != $data->token)
            {
                return response()->json([
                    'codeId' => 2,
                    'token' => $configToken
                ], 200);
            }

            $serial = $data->data;
            $username = $data->member->username;
            $amount = $data->member->amount;
            $oldBalance = 0;
            $record = $this->checkreference($serial);
            if ($amount > 0) {
                if ($record)
                {
                    return response()->json([
                        'codeId' => 0,
                        'token' => $configToken,
                    ], 200);
                }
                $userid = preg_replace('/'. self::DG_PROVIDER .'(\d+)/', '$1', $username);
                $user = \App\Models\User::lockforUpdate()->where('id',$userid)->first();
                if (!$user || !$user->hasRole('user')){
                    return response()->json([
                        'codeId' => 98,
                        'token' => $configToken
                    ], 200);
                }

                $transaction = \App\Models\DGTransaction::create([
                    'reference' => $serial, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($data),
                    'refund' => 0
                ]);
                
                $oldBalance = $user->balance;
                $newBalance = $oldBalance + $amount;
                $user->update(['balance' => $newBalance]);
            }
            else
            {
                if (!$record)
                {
                    return response()->json([
                        'codeId' => 0,
                        'token' => $configToken,
                    ], 200);
                }
                $record->delete();
                $userid = preg_replace('/'. self::DG_PROVIDER .'(\d+)/', '$1', $username);
                $user = \App\Models\User::lockforUpdate()->where('id',$userid)->first();
                if (!$user || !$user->hasRole('user')){
                    return response()->json([
                        'codeId' => 98,
                        'token' => $configToken
                    ], 200);
                }
               
                $oldBalance = $user->balance;
                $newBalance = $oldBalance - $amount;
                $user->update(['balance' => $newBalance]);
            }
            return response()->json([
                'codeId' => 0,
                'token' => $configToken,
                'member' => [
                    'username' => $username,
                    'amount' => $amount,
                    'balance' => floatval($oldBalance)
                ]
            ], 200);
        }
        public function order($agentName, \Illuminate\Http\Request $request)
        {
            $configAgent = config('app.dg_agent');
            $configToken = $this->sign('');
            if ($configAgent != $agentName)
            {
                return response()->json([
                    'codeId' => 118,
                    'token' => $configToken
                ], 200);
            }
            $data = json_decode($request->getContent());

            if ($configToken != $data->token)
            {
                return response()->json([
                    'codeId' => 2,
                    'token' => $configToken
                ], 200);
            }
            $ticketId = $data->ticketId;
            return response()->json([
                'codeId' => 0,
                'token' => $configToken,
                'tiketId' => $ticketId,
                'list' => []
            ], 200);
        }

        public function unsettle($agentName, \Illuminate\Http\Request $request)
        {
            $configAgent = config('app.dg_agent');
            $configToken = $this->sign('');
            if ($configAgent != $agentName)
            {
                return response()->json([
                    'codeId' => 118,
                    'token' => $configToken
                ], 200);
            }
            $data = json_decode($request->getContent());

            if ($configToken != $data->token)
            {
                return response()->json([
                    'codeId' => 2,
                    'token' => $configToken
                ], 200);
            }
            return response()->json([
                'codeId' => 0,
                'token' => $configToken,
                'list' => []
            ], 200);
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
            $query = 'SELECT * FROM w_provider_games WHERE provider="dg' . $href .'"';
            $gac_games = \DB::select($query);
            foreach ($gac_games as $game)
            {
                $icon_name = str_replace(' ', '_', $game->gameid);
                $icon_name = strtolower(preg_replace('/\s+/', '', $icon_name));
                array_push($gameList, [
                    'provider' => 'dg',
                    'gameid' => $game->gameid,
                    'gamecode' => $game->gamecode,
                    'enname' => $game->name,
                    'name' => preg_replace('/\s+/', '', $game->name),
                    'title' => $game->title,
                    'type' => $game->type,
                    'href' => $href,
                    'view' => $game->view,
                    'icon' => '/frontend/Default/ico/dg/'. $icon_name . '.jpg',
                    ]);
            }
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
            
        }

        public static function registerMember($userId)
        {
            $url = config('app.dg_api') . '/user/signup/' . config('app.dg_agent');
            $rand = DGController::generateCode(6);
            $key = DGController::sign($rand);
            $betLimit = 'R';//bet limit 1000-5000000, refer to KRW Currency.pdf
            $params = [
                'token' => $key,
                'random' => $rand,
                'data' => $betLimit, 
                'member' => [
                    'username' => self::DG_PROVIDER . $userId,
                    'password' => DGController::sign($rand),
                    'currencyName' => 'KRW',
                    'winLimit' => 0
                ]
            ];
            $response = Http::post($url, $params);
            if (!$response->ok())
            {
                Log::error('DG : register request failed. ' . $response->body());
                return false;
            }
            $data = $response->json();
            if ($data['codeId'] == 0)
            {
                return true;
            }
            if ($data['codeId'] == 116) //exist player?
            {
                // change bet limit.
                $params = [
                    'token' => $key,
                    'random' => $rand,
                    'data' => $betLimit, 
                    'member' => [
                        'username' => self::DG_PROVIDER . $userId,
                    ]
                ];
                $url = config('app.dg_api') . '/game/updateLimit/' . config('app.dg_agent');
                $response = Http::post($url, $params);
                return true;
            }
            Log::error('DG : register response failed. ' . $data['codeId']);
            return false;
        }

        public static function login($userId)
        {
            $url = config('app.dg_api') . '/user/login/' . config('app.dg_agent');
            $rand = DGController::generateCode(6);
            $key = DGController::sign($rand);
            $params = [
                'token' => $key,
                'random' => $rand,
                'lang' => 'kr',
                'member' => [
                    'username' => self::DG_PROVIDER . $userId,
                    'password' => DGController::sign($rand),
                ]
            ];
            $response = Http::post($url, $params);
            if (!$response->ok())
            {
                Log::error('DG : login request failed. ' . $response->body());
                return null;
            }
            $data = $response->json();
            if ($data['codeId'] != 0 )
            {
                Log::error('DG : login response failed. ' . $data['codeId']);
            }
            return $data;
        }

        public static function makegamelink($gamecode)
        {
            $detect = new \Detection\MobileDetect();
            $user = auth()->user();
            if ($user == null)
            {
                return null;
            }
            $res = DGController::registerMember($user->id);
            if (!$res)
            {
                return null;
            }
            $data = DGController::login($user->id);
            if (!$data || $data['codeId'] != 0)
            {
                return null;
            }
            $url = $data['list'][0] . $data['token'] . '&language=kr';
            if ($detect->isMobile() || $detect->isTablet())
            {
                $url = $data['list'][1] . $data['token'] . '&language=kr';
            }
            return $url;                 
        }

        public static function getgamelink($gamecode)
        {
            $url = DGController::makegamelink($gamecode);
            if ($url)
            {
                return ['error' => false, 'data' => ['url' => $url]];
            }
            return ['error' => true, 'msg' => '로그인하세요'];            
        }

        public static function processGameRound()
        {
            $url = config('app.dg_api') . '/game/getReport/' . config('app.dg_agent');
            $rand = DGController::generateCode(6);
            $key = DGController::sign($rand);
            $params = [
                'token' => $key,
                'random' => $rand,
            ];

            $response = Http::post($url, $params);
            if (!$response->ok())
            {
                // Log::error('DG : getReport request failed. ' . $response->body());
                return;
            }
            $data = $response->json();
            if ($data==null && $data['codeId'] != 0 )
            {
                // Log::error('DG : getReport response failed. ' . $data['codeId']);
                return;
            }

            if (!isset($data['list']))
            {
                return;
            }

            $category = \App\Models\Category::where(['provider' => 'dg', 'shop_id' => 0, 'href' => 'dg'])->first();
            
            foreach ($data['list'] as $round)
            {
                //calc after balance
                $balance = $round['balanceBefore'] - $round['betPoints'] + $round['winOrLoss'];
                $time = strtotime($round['calTime'] .' +1 hours');
                $dateInLocal = date("Y-m-d H:i:s", $time);
                $userid = preg_replace('/'. self::DG_PROVIDER .'(\d+)/', '$1', $round['userName']) ;
                $userid = preg_replace('/'. strtolower(self::DG_PROVIDER) .'(\d+)/', '$1', $userid) ;
                $shop = \App\Models\ShopUser::where('user_id', $userid)->first();
                $gameObj =  DGController::getGameObj($round['tableId']);
                if (!$gameObj)
                {
                    $gameObj = DGController::getGameObj(self::UNKNOWN_TABLE);
                }
                \App\Models\StatGame::create([
                    'user_id' => $userid, 
                    'balance' => $balance, 
                    'bet' => $round['betPoints'], 
                    'win' => $round['winOrLoss'], 
                    'game' =>$gameObj['name'] . '_dg', 
                    'type' => 'table',
                    'percent' => 0, 
                    'percent_jps' => 0, 
                    'percent_jpg' => 0, 
                    'profit' => 0, 
                    'denomination' => 0, 
                    'date_time' => $dateInLocal,
                    'shop_id' => $shop->shop_id,
                    'category_id' => isset($category)?$category->id:0,
                    'game_id' => $gameObj['gamecode'],
                    'roundid' => $round['tableId'] . '_' . $round['id'],
                ]);
            }

            //mark as read

            $url = config('app.dg_api') . '/game/markReport/' . config('app.dg_agent');
            $ids = array_column($data['list'], 'id');
            $params = [
                'token' => $key,
                'random' => $rand,
                'list' => $ids
            ];

            $response = Http::post($url, $params);
            if (!$response->ok())
            {
                Log::error('DG : markReport request failed. ' . $response->body());
            }
            $data = $response->json();
            if ($data==null || $data['codeId'] != 0 )
            {
                Log::error('DG : markReport response failed. ' . $data['codeId']);
            }
            return;

        }

    }

}
