<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class KUZAController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const PROVIDER = 'kuza';
        const tableName = [
            'S01'=>"ArenaBaccaratS01",
            'S02'=>"ArenaBaccaratS01",
            'S03'=>"ArenaBaccaratS01",
            'S04'=>"ArenaBaccaratS01",
            'S05'=>"ArenaBaccaratS01",
            'S06'=>"ArenaBaccaratS01",
            'H01'=>"ArenaBaccaratH01",
            'H02'=>"ArenaBaccaratH02",
            'H03'=>"ArenaBaccaratH03",
            'H04'=>"ArenaBaccaratH04",
            'H05'=>"ArenaBaccaratH05",
            'H06'=>"ArenaBaccaratH06",
            'H07'=>"ArenaBaccaratH07",
            'H08'=>"ArenaBaccaratH08",
            'H09'=>"ArenaBaccaratH09",
            'H10'=>"ArenaBaccaratH10",
        ];


        public static function getGameObj($uuid)
        {
            $gamelist = KUZAController::getgamelist($uuid);
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
            return null;
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
            foreach (self::tableName as $key=>$table)
            {
                array_push($gameList, [
                    'provider' => self::PROVIDER,
                    'gamecode' => $key,
                    'name' => $table,
                    'title' => $table,
                    'type' => 'table',
                    'href' => $href,
                    'view' => 0,
                    ]);
            }
            array_unshift($gameList, [
                'provider' => self::PROVIDER,
                'gameid' => 'lobby',
                'gamecode' => 'kuzalobby',
                'enname' => 'lobby',
                'name' => 'lobby',
                'title' => '아레나로비',
                'type' => 'table',
                'href' => self::PROVIDER,
                'view' => 1,
                'icon' => '/frontend/Default/ico/gac/gvo/117.jpg',
            ]);
                
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
        }

        public static function getUserBalance($href, $user)
        {
            //get user balance
            $header = [
                'Authorization' => 'Bearer ' . config('app.kuza_key'),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            $param = [
                'username' => self::PROVIDER . $user->id,
            ];
            $url = config('app.kuza_api') . '/api/v1/user/balance';

            $response = Http::timeout(10)->asForm()->withHeaders($header)->post($url, $param);

            if (!$response->ok())
            {
                return -1;
            }
            $data = $response->json();
            if ($data['result'] != 'SUCCESS') {
                return -1;
            }
            return $data['data']['balance'];
        }

        public static function withdrawAll($href, $user)
        {
            $balance = KUZAController::getUserBalance($href, $user);

            if ($balance <= 0)
            {
                return ['error'=>false, 'amount'=>0];
            }
            //sub balance

            $header = [
                'Authorization' => 'Bearer ' . config('app.kuza_key'),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            $param = [
                'username' => self::PROVIDER . $user->id,
                'amount' => $balance
            ];

            $url = config('app.kuza_api') . '/api/v1/user/withdraw';
            $response = Http::timeout(10)->asForm()->withHeaders($header)->post($url, $param);
            if (!$response->ok())
            {
                return ['error' => true, 'amount'=>-1,'msg' => $response->body()];
            }
            $data = $response->json();
            if ($data['result'] != 'SUCCESS') {
                return ['error' => true, 'amount'=>-1, 'msg' => $response->body()];
            }

            return ['error'=>false, 'amount'=>abs($data['data']['amount'])];
        }

        public static function makelink($gamecode, $userid)
        {
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('KUZA : Does not find user ' . $userid);
                return null;
            }
            //create user
            $header = [
                'Authorization' => 'Bearer ' . config('app.kuza_key'),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            $param = [
                'username' => self::PROVIDER . $user->id,
                'password' => '11@@aabb',
                'ipAddress' => '192.168.1.1'
            ];

            $url = config('app.kuza_api') . '/api/v1/login';
            
            try {
                $response = Http::timeout(10)->asForm()->withHeaders($header)->post($url, $param);
                if (!$response->ok())
                {
                    Log::error('KUZA makelink create user : response is not 200. ');
                    return null;
                }
                $data = $response->json();
                if ($data['result'] != 'SUCCESS') {
                    Log::error('KUZA makelink create user : response is not success. ' . $data['message']);
                    return null;
                }
                //bet limit
                $url = config('app.kuza_api') . '/api/v1/user/limit/update';
                $param = [
                    'username' => self::PROVIDER . $user->id,
                    'pMax' => 1000000,
                    'pMin' => 10000,
                    'bMax' => 1000000,
                    'bMin' => 10000,
                    'tMax' => 100000,
                    'tMin' => 10000,
                    'ppMax' => 100000,
                    'ppMin' => 10000,
                    'bpMax' => 100000,
                    'bpMin' => 10000,
                ];
                $response = Http::timeout(10)->asForm()->withHeaders($header)->post($url, $param);
                if (!$response->ok())
                {
                    Log::error('KUZA makelink betlimit  : response is not 200. ');
                    return null;
                }
                $data = $response->json();
                if ($data['result'] != 'SUCCESS') {
                    Log::error('KUZA makelink bet limit : response is not success. ' . $data['message']);
                    return null;
                }
            }
            catch (\Exception $ex)
            {
                Log::error('KUZA makelink create user : exception. ' . $ex->getMessage());
                return null;
            }

            //user balance
            try {
                $data = KUZAController::withdrawAll($gamecode, $user);
                if ($data['error'])
                {
                    Log::error('KUZA makelink withdraw user : error. ');
                    return null;
                }
                //deposit
                if ($user->balance > 0)
                {
                    $param = [
                        'username' => self::PROVIDER . $user->id,
                        'amount' => $user->balance
                    ];
                    $url = config('app.kuza_api') . '/api/v1/user/deposit';
                    $response = Http::timeout(10)->asForm()->withHeaders($header)->post($url, $param);
                    if (!$response->ok())
                    {
                        Log::error('KUZA makelink deposit : response is not okay. ');
                        return null;
                    }
                    $data = $response->json();
                    if ($data['result'] != 'SUCCESS') {
                        Log::error('KUZA makelink deposit : not success. ' . $data['message']);
                        return null;
                    }
                }
            }
            catch (\Exception $ex)
            {
                Log::error('KUZA makelink user balance: exception. ' . $ex->getMessage());
                return null;
            }

            return '/followgame/kuza/'.$gamecode;
        }
        public static function makegamelink($gamecode, $user)
        {
            //run
             $url = null;
            try {
                $header = [
                    'Authorization' => 'Bearer ' . config('app.kuza_key'),
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ];
                $param = [
                    'username' => self::PROVIDER . $user->id,
                    'password' => '11@@aabb',
                    'ipAddress' => '192.168.1.1'
                ];
                $url = config('app.kuza_api') . '/api/v1/login';
                $response = Http::timeout(10)->asForm()->withHeaders($header)->post($url, $param);
                if (!$response->ok())
                {
                    return null;
                }
                $data = $response->json();
                if ($data['result'] != 'SUCCESS') {
                    Log::error('KUZA makegamelink: not success. ' . $data['message']);
                    return null;
                }
                $url = $data['data']['redirectUrl'];
            }
            catch (\Exception $ex)
            {
                Log::error('KUZA makegamelink  : exception. ' . $ex->getMessage());
                return null;
            }
            return $url;
        }

        public static function getgamelink($gamecode)
        {
            $user = auth()->user();
            // if ($user->playing_game != null) //already playing game.
            // {
            return ['error' => true, 'data' => '점검중입니다.'];
            // }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [self::PROVIDER, $gamecode])]];
        }

        public static function processGameRound()
        {
            $start_date = gmdate('Y-m-d'); 
            $start_time = gmdate('H:i:s'); 
            $last_date = gmdate('Y-m-d',strtotime('-2 day'));
            $last_time = gmdate('H:i:s',strtotime('-2 day'));
            $limit = 500;

            $category = \App\Models\Category::where('provider', self::PROVIDER)->first();
            if ($category == null)
            {
                return [0, -1];
            }

            $last = \App\Models\StatGame::where('category_id', $category->original_id)->orderby('date_time', 'desc')->first();
            if ($last)
            {
                $last_date = gmdate('Y-m-d',strtotime($last->date_time . ' +1 seconds'));
                $last_time = gmdate('H:i:s',strtotime($last->date_time . ' +1 seconds'));
            }
            try {
                $header = [
                    'Authorization' => 'Bearer ' . config('app.kuza_key'),
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ];
                $param = [
                    'startDate' => $last_date,
                    'startTime' => $last_time,
                    'endDate' => $start_date,
                    'endTime' => $start_time,
                    'page' => 1,
                    'perPage' => $limit
                ];
                $url = config('app.kuza_api') . '/api/v1/partner/betting/list';
                $response = Http::timeout(10)->asForm()->withHeaders($header)->post($url, $param);

                if (!$response->ok())
                {
                    return [0, -1];
                }
                $data = $response->json();
                if ($data['result'] != 'SUCCESS') {
                    return [0, -1];
                }
                foreach ($data['data']['bets'] as $round)
                {
                    $bet = $round['amount'];
                    
                    $win = 0;
                    if ($round['result']=='win')
                    {
                        $win = $bet + $round['award'];
                    }

                    $bet_time = date('Y-m-d H:i:s', strtotime($round['resultDate']));
                    $user_balance = -1;

                    $userid = preg_replace('/'. self::PROVIDER .'(\d+)/', '$1', $round['username']) ;
                    $shop = \App\Models\ShopUser::where('user_id', $userid)->first();
                    $gameid = substr($round['gameId'], 0, 3);
                    \App\Models\StatGame::create([
                        'user_id' => $userid, 
                        'balance' => $user_balance, 
                        'bet' => $bet, 
                        'win' => $win, 
                        'game' =>self::tableName[$gameid], 
                        'type' => 'table',
                        'percent' => 0, 
                        'percent_jps' => 0, 
                        'percent_jpg' => 0, 
                        'profit' => 0, 
                        'denomination' => 0, 
                        'date_time' => $bet_time,
                        'shop_id' => $shop?$shop->shop_id:0,
                        'category_id' => $category->original_id,
                        'game_id' => $gameid,
                        'roundid' => $round['transactionId'],
                    ]);
                }
                return [count($data['data']), $start_time];
            }
            catch (\Exception $ex)
            {
                return [0, -1];
            }
        }

        public static function getAgentBalance()
        {

            $header = [
                'Authorization' => 'Bearer ' . config('app.kuza_key'),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            $url = config('app.kuza_api') . '/api/v1/partner/info';

            try {
                $response = Http::timeout(10)->asForm()->withHeaders($header)->post($url);
                $data = $response->json();
                return $data['data']['balance'];
            } catch (\Exception $e) {
                Log::error('KUZA Agent money : request failed. ' . $e->getMessage());
                return -1;
            }

        }

    }

}
