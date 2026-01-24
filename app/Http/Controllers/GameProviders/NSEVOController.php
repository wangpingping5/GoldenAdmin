<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    class NSEVOController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const PROVIDER = 'nsevo';
        const tableName = [
            0=>"코리안스피드바카라A",
            1=>"코리안스피드바카라B",
            8=>"풍성한골든바카라",
            9=>"라이트닝바카라",
            11=>"엠퍼러스피드바카라A",
            12=>"엠퍼러스피드바카라B",
            13=>"엠퍼러스피드바카라C",
            14=>"스피드바카라A",
            15=>"스피드바카라B",
            16=>"스피드바카라C",
            17=>"스피드바카라D",
            18=>"스피드바카라E",
            19=>"스피드바카라F",
            20=>"스피드바카라G",
            21=>"스피드바카라H",
            22=>"스피드바카라I",
            23=>"스피드바카라J",
            24=>"스피드바카라K",
            25=>"스피드바카라L",
            26=>"스피드바카라M",
            27=>"스피드바카라N",
            28=>"스피드바카라O",
            29=>"스피드바카라P",
            30=>"스피드바카라Q",
            31=>"스피드바카라R",
            32=>"스피드바카라S",
            50=>"스피드바카라T",
            51=>"스피드바카라U",
            52=>"스피드바카라V",
            54=>"스피드바카라W", 
            55=>"스피드바카라X", 
            33=>"노커미션스피드바카라A",
            34=>"노커미션스피드바카라B",
            35=>"노커미션스피드바카라C",
            36=>"노커미션바카라",
            37=>"바카라A",
            38=>"바카라B",
            39=>"바카라C",
            40=>"바카라스퀴즈",
            41=>"바카라컨트롤스퀴즈"
        ];



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
                'gamecode' => 'nslobby',
                'enname' => 'lobby',
                'name' => 'lobby',
                'title' => '에볼로비',
                'type' => 'table',
                'href' => self::PROVIDER,
                'view' => 1,
                'icon' => '/frontend/Default/ico/gac/gvo/117.jpg',
            ]);
                
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
        }

        public static function getUserBalance($user)
        {
            //get user balance
            $param = [
                'apiKey' => config('app.nsevo_key'),
                'user_id' => self::PROVIDER . '#' . $user->id,
            ];
            $url = config('app.nsevo_api') . '/api/user/balance_new';

            $response = Http::timeout(10)->post($url, $param);

            if (!$response->ok())
            {
                return -1;
            }
            $data = $response->json();
            if ($data['error'] != 0) {
                return -1;
            }
            return $data['data']['amount'];
        }

        public static function withdrawAll($user)
        {
            $balance = NSEVOController::getUserBalance($user);

            if ($balance <= 0)
            {
                return ['error'=>false, 'amount'=>0];
            }
            //sub balance
            $param = [
                'apiKey' => config('app.nsevo_key'),
                'user_id' => self::PROVIDER . '#' . $user->id,
                'amount' => $balance
            ];
            $url = config('app.nsevo_api') . '/api/user/transaction_new/withdraw';
            $response = Http::timeout(10)->post($url, $param);
            if (!$response->ok())
            {
                return ['error' => true, 'amount'=>-1,'msg' => $response->body()];
            }
            $data = $response->json();
            if ($data['error'] != 0) {
                return ['error' => true, 'amount'=>-1, 'msg' => $response->body()];
            }

            return ['error'=>false, 'amount'=>abs($data['data']['amount'])];
        }

        public static function makelink($gamecode, $userid)
        {
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('NSEVO : Does not find user ' . $userid);
                return null;
            }
            //create user
            $url = null;
            try {
                $param = [
                    'apiKey' => config('app.nsevo_key'),
                    'userId' => self::PROVIDER . '#' . $user->id,
                    'password' => '111111',
                    'type' => 1
                ];
                $response = Http::timeout(10)->post(config('app.nsevo_api') . '/api/user/login_new', $param);
                if (!$response->ok())
                {
                    return ['error' => true, 'data' => $response->body()];
                }
                $data = $response->json();
                if ($data['error'] != 0) {
                    return ['error' => true, 'data' => $response->body()];
                }
            }
            catch (\Exception $ex)
            {
                return null;
            }

            //user balance
            try {
                $data = NSEVOController::withdrawAll($user);
                if ($data['error'])
                {
                    return null;
                }
                //deposit
                if ($user->balance > 0)
                {
                    $param = [
                        'apiKey' => config('app.nsevo_key'),
                        'user_id' => self::PROVIDER . '#' . $user->id,
                        'amount' => $user->balance
                    ];
                    $response = Http::timeout(10)->post(config('app.nsevo_api') . '/api/user/transaction_new/deposit', $param);
                    if (!$response->ok())
                    {
                        return null;
                    }
                    $data = $response->json();
                    if ($data['error'] != 0) {
                        return null;
                    }
                }
            }
            catch (\Exception $ex)
            {
                return null;
            }

            return '/followgame/nsevo/'.$gamecode;
        }
        public static function makegamelink($gamecode)
        {
            //run
             $url = null;
            try {
                $param = [
                    'agentKey' => config('app.nsevo_key'),
                    'user_id' => self::PROVIDER . '#' . auth()->user()->id,
                    'password' => '111111',
                ];
                $response = Http::timeout(10)->post(config('app.nsevo_api') . '/api/game/launch_new', $param);
                if (!$response->ok())
                {
                    return null;
                }
                $data = $response->json();
                if ($data['error'] != 0) {
                    return null;
                }
                $url = $data['data']['url'];
            }
            catch (\Exception $ex)
            {
                return null;
            }
            return $url;
        }

        public static function getgamelink($gamecode)
        {
            $user = auth()->user();
            if ($user->playing_game != null) //already playing game.
            {
                return ['error' => true, 'data' => '이미 실행중인 게임을 종료해주세요. 이미 종료했음에도 불구하고 이 메시지가 계속 나타난다면 매장에 문의해주세요.'];
            }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [self::PROVIDER, $gamecode])]];
        }

        public static function processGameRound()
        {
            $start_time = gmdate('Y-m-d H:i:s'); 
            $last_time = gmdate('Y-m-d H:i:s',strtotime('-2 day'));
            $limit = 1000;
            $category = \App\Models\Category::where('provider', self::PROVIDER)->first();
            if ($category == null)
            {
                return [0, -1];
            }
            $last = \App\Models\StatGame::where('category_id', $category->original_id)->orderby('date_time', 'desc')->first();
            if ($last)
            {
                $last_time = gmdate('Y-m-d H:i:s',strtotime($last->date_time . ' +1 seconds'));
            }
            try {
                $param = [
                    'apiKey' => config('app.nsevo_key'),
                    'start_date' => $last_time,
                    'end_date' => $start_time,
                    'limit' => $limit,
                ];
                $response = Http::timeout(10)->post(config('app.nsevo_api') .  '/api/user/history_new/bet', $param);
                if (!$response->ok())
                {
                    return [0, -1];
                }
                $data = $response->json();
                if ($data['error'] != 0) {
                    return [0, -1];
                }
                foreach ($data['data'] as $round)
                {
                    $bet = $round['bet_player'] + $round['bet_banker'] + $round['bet_tie'] + $round['bet_player_pair'] + $round['bet_banker_pair'] + $round['bet_player_bonus'] + $round['bet_banker_bonus'] + $round['bet_either_pair'] + $round['bet_perfect_pair'];

                    $win = $round['win_player'] + $round['win_banker'] + $round['win_tie'] + $round['win_player_pair'] + $round['win_banker_pair'] + $round['win_player_bonus'] + $round['win_banker_bonus'] + $round['win_either_pair'] + $round['win_perfect_pair'];

                    $bet_time = date('Y-m-d H:i:s', strtotime($round['bet_time'] . " +9 hour"));
                    $user_balance = $round['after_bet_balance'];

                    $userid = preg_replace('/'. self::PROVIDER .'#(\d+)/', '$1', $round['user_id']) ;
                    $shop = \App\Models\ShopUser::where('user_id', $userid)->first();
                    \App\Models\StatGame::create([
                        'user_id' => $userid, 
                        'balance' => $user_balance, 
                        'bet' => $bet, 
                        'win' => $win, 
                        'game' =>self::tableName[$round['table_index']], 
                        'type' => 'table',
                        'percent' => 0, 
                        'percent_jps' => 0, 
                        'percent_jpg' => 0, 
                        'profit' => 0, 
                        'denomination' => 0, 
                        'date_time' => $bet_time,
                        'shop_id' => $shop?$shop->shop_id:0,
                        'category_id' => $category->original_id,
                        'game_id' => $round['table_index'],
                        'roundid' => $round['game_id'],
                    ]);
                }
                return [count($data['data']), $start_time];
            }
            catch (\Exception $ex)
            {
                return [0, -1];
            }
        }

    }

}
