<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class HONORController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const HONOR_PROVIDER = 'honor';
        const HONOR_PP_HREF = 'honor-pp';
        const HONOR_PPVERIFY_PROVIDER = 'vrf';
        const HONOR_GAME_IDENTITY = [
            //==== SLOT ====
            'honor-evol' => ['vendor' =>'evolution'],
            'honor-evoplay' => ['vendor' =>'EVOPLAY'],
            'honor-pp' => ['vendor' =>'PragmaticPlay'],
            'honor-bng' => ['vendor' =>'Booongo'],
            'honor-hbn' => ['vendor' =>'Habanero'],
            'honor-bota' => ['vendor' =>'bota'],
            'honor-redt' => ['vendor' =>'redtiger'],
            'honor-dg' => ['vendor' =>'DreamGame'],
            'honor-wml' => ['vendor' =>'WM Live'],
            'honor-waz' => ['vendor' =>'Wazdan'],
            'honor-relg' => ['vendor' =>'Relax Gaming'],
            'honor-ppl' => ['vendor' =>'PragmaticPlay Live'],
            'honor-tpg' => ['vendor' =>'Triple Profit Gaming'],
            'honor-gameart' => ['vendor' =>'GameArt'],
            'honor-cq9' => ['vendor' =>'CQ9'],
            'honor-bpg' => ['vendor' =>'Blueprint Gaming'],
            'honor-playstar' => ['vendor' =>'PlayStar'],
            'honor-btg' => ['vendor' =>'BigTimeGaming'],
            'honor-playson' => ['vendor' =>'PlaySon'],
            'honor-thunder' => ['vendor' =>'Thunderkick'],
            'honor-ncity' => ['vendor' =>'Nolimit City'],
            'honor-mobilots' => ['vendor' =>'Mobilots'],
            'honor-playpearls' => ['vendor' =>'PlayPearls'],
            'honor-ds' => ['vendor' =>'Dragoon Soft'],
            'honor-betgt' => ['vendor' =>'Betgames.tv'],
            'honor-skywl' => ['vendor' =>'Skywind Live'],
            'honor-1x2g' => ['vendor' =>'1X2 Gaming'],
            'honor-elkst' => ['vendor' =>'Elk Studios'],
            'honor-asiag' => ['vendor' =>'Asia Gaming'],
            'honor-pgsoft' => ['vendor' =>'PG Soft'],
            'honor-dowin' => ['vendor' =>'Dowin'],
            'honor-mgp' => ['vendor' =>'MicroGaming Plus'],
            'honor-mgps' => ['vendor' =>'MicroGaming Plus Slo'],
            'honor-iconix' => ['vendor' =>'iconix'],
            'honor-intouchg' => ['vendor' =>'intouch-games'],
            'honor-bfgames' => ['vendor' =>'bfgames'],
            'honor-popok' => ['vendor' =>'popok'],
            'honor-vir2al' => ['vendor' =>'vir2al'],
            'honor-dreamtech' => ['vendor' =>'dreamtech'],
            'honor-onetouch' => ['vendor' =>'onetouch'],
            'honor-onetouchl' => ['vendor' =>'onetouch-live'],
            'honor-endorphina' => ['vendor' =>'endorphina'],
            'honor-caletag' => ['vendor' =>'caletagaming'],
            'honor-absolute' => ['vendor' =>'absolute'],
            'honor-7mojos' => ['vendor' =>'7-mojos'],
            'honor-7mojosslots' => ['vendor' =>'7-mojos-slots'],
            'honor-platipus' => ['vendor' =>'platipus'],
            'honor-mancala' => ['vendor' =>'mancala'],
            'honor-retrogames' => ['vendor' =>'retrogames'],
            'honor-spinomenal' => ['vendor' =>'spinomenal'],
            'honor-fils' => ['vendor' =>'fils'],
            'honor-liw' => ['vendor' =>'liw'],
            'honor-inrace' => ['vendor' =>'inrace'],
            'honor-smartsoft' => ['vendor' =>'smartsoft'],
            'honor-plating' => ['vendor' =>'platingaming'],
            'honor-7777' => ['vendor' =>'7777'],
            'honor-eagaming' => ['vendor' =>'eagaming'],
            'honor-galaxsys' => ['vendor' =>'galaxsys'],
            'honor-vivo' => ['vendor' =>'vivo'],
            'honor-globalbet' => ['vendor' =>'globalbet'],
            'honor-ezugi' => ['vendor' =>'ezugi'],
            'honor-bitville' => ['vendor' =>'bitville'],
            'honor-gamedaddy' => ['vendor' =>'gamedaddy'],
            'honor-playngo' => ['vendor' =>'playngo'],
        ];

        public static function getGameObj($uuid)
        {
            foreach (HONORController::HONOR_GAME_IDENTITY as $ref => $value)
            {
                $gamelist = HONORController::getgamelist($ref);
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

        
        public static function getUserBalance($href, $user, $prefix=self::HONOR_PROVIDER) {
            $url = config('app.honor_api') . '/user';
            $token = config('app.honor_key');

            $param = [
                'username' => $prefix . sprintf("%04d",$user->id)
            ];
            $balance = -1;

            try {                   
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->get($url , $param);
                if ($response->getStatusCode() == 200) {
                    $res = $response->json();
        
                    if (!isset($res['errors'])) {
                        $balance = $res['balance'];
                    }
                    else
                    {
                        Log::error('HONORgetuserbalance : return failed. ' . $res['message']);
                    }
                }
                else
                {
                    Log::error('HONORgetuserbalance : response is not okay. ' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('HONORgetuserbalance : getUserBalance Excpetion. exception= ' . $ex->getMessage());
                Log::error('HONORgamerounds : getUserBalance Excpetion. PARAMS= ' . json_encode($params));
            }
            
            return intval($balance);
        }

        public static function getUserToken($href, $user, $prefix=self::HONOR_PROVIDER) {
            $url = config('app.honor_api') . '/user/refresh-token';
            $token = config('app.honor_key');

            $param = [
                'username' => $prefix . sprintf("%04d",$user->id)
            ];
    
            $usertoken = '';

            try {                   
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->patch($url , $param);
                if ($response->getStatusCode() == 200) {
                    $res = $response->json();
        
                    if (isset($res['token']) && $res['token'] != '') {
                        $usertoken = $res['token'];
                    }
                    else
                    {
                        Log::error('HONORgetusertoken : return failed. ' . json_encode($res));
                    }
                }
                else
                {
                    Log::error('HONORgetusertoken : response is not okay. ' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('HONORgetusertoken : getUserToken Excpetion. exception= ' . $ex->getMessage());
                Log::error('HONORgetusertoken : getUserToken Excpetion. PARAMS= ' . json_encode($params));
            }
            
            return $usertoken;
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
            $category = HONORController::HONOR_GAME_IDENTITY[$href];

            $url = config('app.honor_api') . '/game-list';
            $token = config('app.honor_key');

            $param = [
                'vendor' => $category['vendor']
            ];

    
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
                ])->get($url , $param);
            if ($response->getStatusCode() != 200)
            {
                return [];
            }
            $data = $response->json();
            $gameList = [];

            foreach ($data as $game)
            {
                $view = 1;

                $korname = '';
                if(isset($game['langs']) && isset($game['langs']['ko'])){
                    $korname = $game['langs']['ko'];
                }else{
                    $korname = $game['title'];
                }

                array_push($gameList, [
                    'provider' => self::HONOR_PROVIDER,
                    'href' => $href,
                    'gamecode' => 'honor_' . $game['id'] . '_' . $game['vendor'],
                    'symbol' => $game['id'],
                    'enname' => $game['title'],
                    'name' => preg_replace('/\s+/', '', $game['title']),
                    'title' => $korname,
                    'icon' => $game['thumbnail'],
                    'type' => ($game['type']=='slot')?'slot':'table',
                    'view' => $view
                ]);
            }

            //add Unknown Game item
            array_push($gameList, [
                'provider' => self::HONOR_PROVIDER,
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

        public static function makegamelink($gamecode, $user,  $prefix=self::HONOR_PROVIDER) 
        {

            $token = config('app.honor_key');
            
            $game = HONORController::getGameObj($gamecode);
            if (!$game)
            {
                return null;
            }

            $usertoken = HONORController::getUserToken($game['href'], $user, $prefix);
            //Create Game link
            $category = HONORController::HONOR_GAME_IDENTITY[$game['href']];
            $real_code = explode('_', $game['gamecode'])[1];
            $param = [
                'game_id' => $real_code,
                'token' => $usertoken,
                'vendor' => $category['vendor']
            ];

            $url = config('app.honor_api') . '/get-game-url';
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
                ])->get($url , $param);
            if ($response->getStatusCode() != 200)
            {
                Log::error('HONORGetLink : Game url request failed. status=' . $response->status());
                Log::error('HONORGetLink : Game url request failed. param=' . json_encode($param));

                return null;
            }
            $data = $response->json();
            if ($data==null || isset($data['errors']))
            {
                Log::error('HONORGetLink : Game url result failed. ' . ($data==null?'null':json_encode($data)));
                return null;
            }
            $url = $data['link'];

            return $url;
        }
        
        public static function withdrawAll($href, $user, $prefix=self::HONOR_PROVIDER)
        {
            $balance = HONORController::getUserBalance($href, $user, $prefix);
            if ($balance < 0)
            {
                return ['error'=>true, 'amount'=>$balance, 'msg'=>'getuserbalance return -1'];
            }
            if ($balance > 0)
            {
                $token = config('app.honor_key');

                $str_param = '?username=' . $prefix . sprintf("%04d",$user->id);

                try {
                    $url = config('app.honor_api') . '/user/sub-balance-all';
                    $response = Http::withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                        ])->post($url . $str_param);
                    if ($response->getStatusCode() != 200)
                    {
                        Log::error('HONORWithdraw : subtractMemberPoint request failed. ' . $response->body());

                        return ['error'=>true, 'amount'=>0, 'msg'=>'response not ok'];
                    }
                    $data = $response->json();
                    if ($data==null || isset($data['message']))
                    {
                        Log::error('HONORWithdraw : subtractMemberPoint result failed. PARAMS=' . $str_param);
                        Log::error('HONORWithdraw : subtractMemberPoint result failed. ' . ($data==null?'null':$data['message']));
                        return ['error'=>true, 'amount'=>0, 'msg'=>'data not ok'];
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('HONORWithdraw : subtractMemberPoint Exception. Exception=' . $ex->getMessage());
                    Log::error('HONORWithdraw : subtractMemberPoint Exception. PARAMS=' .$str_param);
                    return ['error'=>true, 'amount'=>0, 'msg'=>'exception'];
                }
            }
            return ['error'=>false, 'amount'=>$balance];
        }

        public static function makelink($gamecode, $userid)
        {
            $token = config('app.honor_key');

            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('HONORMakeLink : Does not find user ' . $userid);
                return null;
            }

            $game = HONORController::getGameObj($gamecode);
            if ($game == null)
            {
                Log::error('HONORMakeLink : Game not find  ' . $gamecode);
                return null;
            }


            $alreadyUser = 1;
            $username = self::HONOR_PROVIDER . sprintf("%04d",$user->id);

            try
            {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->get(config('app.honor_api') . '/user?username='.$username);
                if ($response->getStatusCode() != 200)
                {
                    // Log::error('HONORmakelink : checkUser request failed. ' . $response->body());
                    $alreadyUser = 0;
                }
            }
            catch (\Exception $ex)
            {
                Log::error('HONORcheckuser : checkUser Exception. Exception=' . $ex->getMessage());
                Log::error('HONORcheckuser : checkUser Exception. PARAMS=' . $username);
                return null;
            }
            if ($alreadyUser == 0){
                //create honor account
                try
                {

                    $url = config('app.honor_api') . '/user/create';
                    $str_param = '?username='.$username.'&nickname='.$username;
                    $response = Http::withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                        ])->post($url . $str_param);
                    if ($response->getStatusCode() != 200)
                    {
                        Log::error('HONORmakelink : createAccount request failed. ' . $response->body());
                        return null;
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('HONORcheckuser : createAccount Exception. Exception=' . $ex->getMessage());
                    Log::error('HONORcheckuser : createAccount Exception. PARAMS=' . $str_param);
                    return null;
                }
            }

            $balance = HONORController::getuserbalance(null, $user);
            if ($balance == -1)
            {
                return null;
            }

            if ($balance != $user->balance)
            {
                //withdraw all balance
                $data = HONORController::withdrawAll(null, $user);
                if ($data['error'])
                {
                    return null;
                }
                //Add balance

            
                //addMemberPoint
                $str_param = '?amount=' . floatval($user->balance) . '&username=' . $username;
                try {
                    $url = config('app.honor_api') . '/user/add-balance';
                    $response = Http::withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                        ])->post($url . $str_param);
                    if ($response->getStatusCode() != 200)
                    {
                        Log::error('HONORmakelink : addMemberPoint result failed. ' . $response->body());
                        return null;
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('HONORmakelink : addMemberPoint Exception. exception=' . $ex->getMessage());
                    Log::error('HONORmakelink : addMemberPoint PARAM. PARAM=' . $str_param);
                    return null;
                }
            }
            return '/followgame/'.HONORController::HONOR_PROVIDER.'/'.$gamecode;
            
        }

        public static function getgamelink($gamecode)
        {
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [HONORController::HONOR_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($pageIdx, $startDate, $endDate)
        {
            $token = config('app.honor_key');
            $params = [
                'start' => $startDate,
                'end' => $endDate,
                'perPage' => 1000,
                'page' => $pageIdx,
                'withDetails' => 1
            ];
            try
            {
                $url = config('app.honor_api') . '/transactions';
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->get($url , $params);
                if (!$response->ok())
                {
                    Log::error('HONORgamerounds : getBetWinHistoryAll request failed. PARAMS= ' . json_encode($params));
                    Log::error('HONORgamerounds : getBetWinHistoryAll request failed. ' . $response->body());

                    return null;
                }
                $data = $response->json();
                if ($data==null)
                {
                    Log::error('HONORgamerounds : getBetWinHistoryAll result failed. PARAMS=' . json_encode($params));
                    Log::error('HONORgamerounds : getBetWinHistoryAll result failed. ');
                    return null;
                }

                return $data;
            }
            catch (\Exception $ex)
            {
                Log::error('HONORgamerounds : getBetWinHistoryAll Excpetion. exception= ' . $ex->getMessage());
                Log::error('HONORgamerounds : getBetWinHistoryAll Excpetion. PARAMS= ' . json_encode($params));
            }
            return null;
        }

        public static function processGameRound($from='', $to='')
        {
            $count = 0;


            $category_ids = \App\Models\Category::where([
                'provider'=> HONORController::HONOR_PROVIDER,
                'shop_id' => 0,
                'site_id' => 0,
                ])->pluck('original_id')->toArray();
            if (count($category_ids) == 0)
            {
                return [$count, 0];
            }
            $roundfrom = '';
            $roundto = '';

            if ($from == '')
            {
                $roundfrom = date('Y-m-d H:i:s',strtotime('-2 hours'));
                $lastround = \App\Models\StatGame::whereIn('category_id', $category_ids)->orderby('date_time', 'desc')->first();
                if ($lastround)
                {
                    $d = strtotime($lastround->date_time);
                    if ($d > strtotime("-2 hours"))
                    {
                        $roundfrom = date('Y-m-d H:i:s',strtotime($lastround->date_time. ' +1 seconds'));
                    }
                }
            }
            else
            {
                $roundfrom = $from;
            }

            if ($to == '')
            {
                $roundto = date('Y-m-d H:i:s');
            }
            else
            {
                $roundto = $to;
            }

            $start_timeStamp = strtotime($roundfrom);
            $end_timeStamp = strtotime($roundto);
            if ($end_timeStamp < $start_timeStamp)
            {
                Log::error('HONOR processGameRound : '. $roundto . '>' . $roundfrom);
                return [0, 0];
            }

            do
            {
                $curend_timeStamp = $start_timeStamp + 3599; // 1hours
                if ($curend_timeStamp > $end_timeStamp)
                {
                    $curend_timeStamp = $end_timeStamp;
                }
                $curPage = 1;
                $data = null;
                do
                {
                    // Log::info('HONOR processGameRound : '. date('Y-m-d H:i:s', $start_timeStamp) . '~' . date('Y-m-d H:i:s', $curend_timeStamp));

                    $data = HONORController::gamerounds($curPage, date('Y-m-d H:i:s', $start_timeStamp - 9 * 60 * 60), date('Y-m-d H:i:s', $curend_timeStamp  - 9 * 60 * 60));
                    if ($data == null)
                    {
                        Log::error('HONOR gamerounds failed : '. date('Y-m-d H:i:s', $start_timeStamp) . '~' . date('Y-m-d H:i:s', $curend_timeStamp));
                        return [0,0];
                    }
                    
                    if (isset($data['data']) && count($data['data']) > 0)
                    {
                        foreach ($data['data'] as $round)
                        {
                            if ($round['type'] != 'bet' && $round['type'] != 'win' )
                            {
                                continue;
                            }

                            $bet = 0;
                            $win = 0;
                            $balance = -1;
                            if ($round['type'] == 'bet')
                            {
                                $bet = abs($round['amount']);
                            }
                            else if ($round['type'] == 'win')
                            {
                                $win = abs($round['amount']);
                                if ($win == 0)
                                {
                                    continue;
                                }
                            }

                            $balance = $round['before'] + $round['amount'];
                            $gameId = 'honor_' . $round['details']['game']['id'] . '_' . $round['details']['game']['vendor'];
                            $game = HONORController::getGameObj($gameId);
                            if (!$game)
                            {
                                Log::error('HONOR could not find game  : '. $gameId);
                                continue;
                            }
                            
                            
                            $time = date('Y-m-d H:i:s',strtotime($round['processed_at']));
                            $type = ($round['details']['game']['type'] == 'slot')?'slot':'table';
                            $category = \App\Models\Category::where('provider', self::HONOR_PROVIDER)->where('href', $game['href'])->where(['shop_id'=>0, 'site_id'=>0])->first();

                            $userid = intval(preg_replace('/'. self::HONOR_PROVIDER .'(\d+)/', '$1', $round['user']['username'])) ;
                            $shop = \App\Models\ShopUser::where('user_id', $userid)->first();

                            $checkGameStat = \App\Models\StatGame::where([
                                'user_id' => $userid, 
                                'bet' => $bet, 
                                'win' => $win, 
                                'date_time' => $time,
                                'roundid' => $round['id'],
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
                                'game' =>$game['name'] . '_honor', 
                                'type' => $type,
                                'percent' => 0, 
                                'percent_jps' => 0, 
                                'percent_jpg' => 0, 
                                'profit' => 0, 
                                'denomination' => 0, 
                                'date_time' => $time,
                                'shop_id' => $shop?$shop->shop_id:0,
                                'category_id' => isset($category)?$category->id:0,
                                'game_id' => $game['gamecode'],
                                'roundid' => $round['id'],
                            ]);
                            $count = $count + 1;
                        }
                    }
                    else
                    {
                        break;
                    }
                    $curPage = $curPage + 1;
                    sleep(1);
                } while ($data!=null);
                sleep(60);
                $start_timeStamp = $curend_timeStamp;
            }while ($start_timeStamp<$end_timeStamp);
            return [$count, 0];
        }


        public static function getAgentBalance()
        {
            $token = config('app.honor_key');
            try
            {
                $url = config('app.honor_api') . '/my-info';
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->get($url);
                if ($response->getStatusCode() != 200)
                {
                    Log::error('HONORAgentBalance : agentbalance request failed. ' . $response->body());
                    return -1;
                }
                $data = $response->json();
                if (($data==null) || isset($data['message']))
                {
                    Log::error('HONORAgentBalance : agentbalance result failed. ' . ($data==null?'null':$data['message']));
                    return -1;
                }
                return $data['balance'];
            }
            catch (\Exception $ex)
            {
                Log::error('HONORAgentBalance : agentbalance Exception. Exception=' . $ex->getMessage());
                Log::error('HONORAgentBalance : agentbalance Exception. PARAMS=');
                return -1;
            }

        }

        public static function syncpromo()
        {
            $user = \App\Models\User::where('role_id', 1)->whereNull('playing_game')->first();           
            if (!$user)
            {
                return ['error' => true, 'msg' => 'not found any available user.'];
            }
            $gamelist = HONORController::getgamelist(self::HONOR_PP_HREF);
            $len = count($gamelist);
            if ($len > 10) {$len = 10;}
            if ($len == 0)
            {
                return ['error' => true, 'msg' => 'not found any available game.'];
            }
            $rand = mt_rand(0,$len);
                
            $gamecode = $gamelist[$rand]['gamecode'];

            $token = config('app.honor_key');
    
            $username = self::HONOR_PP_HREF . sprintf("%04d",$user->id);
            $alreadyUser = 1;
            try
            {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->get(config('app.honor_api') . '/user?username='.$username);
                if ($response->getStatusCode() != 200)
                {
                    Log::error('HONORmakelink : checkUser request failed. ' . $response->body());
                    $alreadyUser = 0;
                }
            }
            catch (\Exception $ex)
            {
                Log::error('HONORcheckuser : checkUser Exception. Exception=' . $ex->getMessage());
                Log::error('HONORcheckuser : checkUser Exception. PARAMS=' . json_encode($params));
                return ['error' => true, 'msg' => 'checkUser failed.'];
            }
            if ($alreadyUser == 0){
                //create kten account
                try
                {

                    $url = config('app.honor_api') . '/user/create';
                    $str_param = '?username='.$username.'&nickname='.$username;
                    $response = Http::withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                        ])->post($url . $str_param);
                    if ($response->getStatusCode() != 200)
                    {
                        Log::error('HONORmakelink : createAccount request failed. ' . $response->body());
                        return ['error' => true, 'msg' => 'createAccount failed.'];
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('HONORcheckuser : createAccount Exception. Exception=' . $ex->getMessage());
                    Log::error('HONORcheckuser : createAccount Exception. PARAMS=' . json_encode($params));
                    return ['error' => true, 'msg' => 'createAccount failed.'];
                }
            }

            $url = HONORController::makegamelink($gamecode, $user, self::HONOR_PP_HREF);
            if ($url == null)
            {
                return ['error' => true, 'msg' => 'game link error '];
            }
            //emulate client
            $response = Http::withOptions(['allow_redirects' => false,'proxy' => config('app.ppproxy')])->get($url);
            if($response->status() == 302){
                $url = $response->header('location');
                $response = Http::withOptions(['allow_redirects' => false,'proxy' => config('app.ppproxy')])->get($url);
            }
            $parse = parse_url($url);
            $ppgameserver = $parse['scheme'] . '://' . $parse['host'];
        
            if ($response->status() == 302)
            {
                $location = $response->header('location');
                $keys = explode('&', $location);
                $mgckey = null;
                foreach ($keys as $key){
                    if (str_contains( $key, 'mgckey='))
                    {
                        $mgckey = $key;
                    }
                    if (str_contains($key, 'symbol='))
                    {
                        $gamecode = $key;
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
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/promo/active/?'.$gamecode.'&' . $mgckey );
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
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/promo/tournament/details/?'.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->tournamentdetails = $response->body();
                }
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/promo/race/details/?'.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->racedetails = $response->body();
                }
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/promo/tournament/v3/leaderboard/?'.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->tournamentleaderboard = $response->body();
                }
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/promo/race/prizes/?'.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->raceprizes = $response->body();
                }
                
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->post($ppgameserver . '/gs2c/promo/race/v2/winners/?'.$gamecode.'&' . $mgckey, ['latestIdentity' => $raceIds]) ;
                if ($response->ok())
                {
                    $promo->racewinners = $response->body();
                }

                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($ppgameserver . '/gs2c/minilobby/games?' . $mgckey );
                if ($response->ok())
                {
                    $json_data = $response->json();
                    //disable not own games
                    $ownCats = \App\Models\Category::where(['href'=> 'pragmatic', 'shop_id'=>0,'site_id'=>0])->first();
                    $gIds = $ownCats->games->pluck('game_id')->toArray();
                    $ownGames = \App\Models\Game::whereIn('id', $gIds)->where('view',1)->get();

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
        public function ppverify($gamecode, \Illuminate\Http\Request $request){
            set_time_limit(0);
            $failed_url = '/gs2c/session/play-verify/'. $gamecode .'?lang=ko&mgckey=AUTHTOKEN@1788e7bf943b9d54907c1b8c7211c4307b9be071e1501f0b09a6b069b6e58~stylename@' . self::HONOR_PPVERIFY_PROVIDER.'-'.self::HONOR_PPVERIFY_PROVIDER . '~SESSION@d016e80c-2a3d-4e6a-b2a9-43b61b0c98dc~SN@5e05f7a7';
            $user = \Auth()->user();
            $token = config('app.honor_key');
            if($user == null){
                $this->ppverifyLog($gamecode, '', 'There is no user');
                return redirect($failed_url); 
            }
            //check user account
            $username = self::HONOR_PPVERIFY_PROVIDER . sprintf("%04d",$user->id);
            $alreadyUser = 1;
            try
            {
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token
                    ])->get(config('app.honor_api') . '/user?username='.$username);
                if ($response->getStatusCode() != 200)
                {
                    $this->ppverifyLog($gamecode, $user->id, 'HONORmakelink : checkUser request failed. ' . $response->body());
                    $alreadyUser = 0;
                }
            }
            catch (\Exception $ex)
            {
                $this->ppverifyLog($gamecode, $user->id, 'HONORcheckuser : checkUser Exception. Exception=' . $ex->getMessage() . '. PARAMS=' . json_encode($params));
                return redirect($failed_url);
            }
            if ($alreadyUser == 0){
                try
                {

                    $url = config('app.honor_api') . '/user/create';
                    $str_param = '?username='.$username.'&nickname='.$username;
                    $response = Http::withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $token
                        ])->post($url . $str_param);
                    if ($response->getStatusCode() != 200)
                    {
                        $this->ppverifyLog($gamecode, $user->id, 'HONORmakelink : createAccount request failed. ' . $response->body());
                        return redirect($failed_url);
                    }
                }
                catch (\Exception $ex)
                {
                    $this->ppverifyLog($gamecode, $user->id, 'HONORcheckuser : checkUser Exception. Exception=' . $ex->getMessage() . '. PARAMS=' . json_encode($params));
                    return redirect($failed_url);
                }
            }
            
            
            // 유저마지막 베팅로그 얻기
            $verify_log = \App\Models\PPGameVerifyLog::where('user_id', $user->id)->orderby('date_time', 'desc')->first();
            $last_bet_time = null;
            if($verify_log != null){
                $last_bet_time = $verify_log->date_time;
            }
            $pm_category = \App\Models\Category::where('href', 'pragmatic')->first();
            $cat_id = $pm_category->original_id;
            $stat_game = \App\Models\StatGame::where(['user_id' => $user->id, 'category_id' => $cat_id]);
            if($last_bet_time != null){
                $stat_game = $stat_game->where('date_time', '>', $last_bet_time);
            }
            $stat_game = $stat_game->orderby('date_time', 'desc')->first();
            if($stat_game != null){
                $game = \App\Models\Game::where('original_id', $stat_game->game_id)->where('shop_id', $user->shop_id)->first();
                if($game == null){
                    $this->ppverifyLog($gamecode, $user->id, 'there is no gamecode => ' . $gamecode);
                    return redirect($failed_url);
                }else{
                    $gamecode = $game->label;
                }
            }

            ///////////////////////<--- User Balance Transfer --->/////////////////////////
            $balance = HONORController::getuserbalance($gamecode, $user, self::HONOR_PPVERIFY_PROVIDER);
            if ($balance == -1)
            {
                $this->ppverifyLog($gamecode, $user->id, 'UserBalance => ' . $balance);
                return redirect($failed_url);
            }

            if ($balance != $user->balance && $stat_game != null)
            {
                //withdraw all balance
                // $data = HONORController::withdrawAll($gamecode, $user, self::HONOR_PPVERIFY_PROVIDER);
                // if ($data['error'])
                // {
                //     $this->ppverifyLog($gamecode, $user->id, 'withdrawAll error');
                //     return redirect($failed_url);
                // }
                //Add balance
                $adduserbalance = $stat_game->bet;   // 유저머니를 베트머니만큼 충전한다.
                if ($adduserbalance > 1)
                {
                    //addMemberPoint
                    $str_param = '?amount=' . floatval($adduserbalance) . '&username=' . self::HONOR_PPVERIFY_PROVIDER . sprintf("%04d",$user->id);
                    try {
                        $url = config('app.honor_api') . '/user/add-balance';
                        $response = Http::withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token
                            ])->post($url . $str_param);
                        if ($response->getStatusCode() != 200)
                        {
                            $this->ppverifyLog($gamecode, $user->id, 'HONORmakelink : add-balance request failed. ' . $response->body());
                            return redirect($failed_url);
                        }
                    }
                    catch (\Exception $ex)
                    {
                        $this->ppverifyLog($gamecode, $user->id, 'add-balance exception=' . $ex->getMessage() . ', PARAM=' . json_encode($params));
                        return redirect($failed_url);
                    }
                }
            }
            ///////////////////////<--- End --->/////////////////////////

            $url = HONORController::makegamelink($gamecode, $user, self::HONOR_PP_HREF, self::HONOR_PPVERIFY_PROVIDER);
            if ($url == null)
            {
                $this->ppverifyLog($gamecode, $user->id, 'make game link error');
                return redirect($failed_url);
            }

            //emulate client
            $response = Http::withOptions(['allow_redirects' => false,'proxy' => config('app.ppproxy')])->get($url);
            if($response->status() == 302){
                $url = $response->header('location');
                $response = Http::withOptions(['allow_redirects' => false,'proxy' => config('app.ppproxy')])->get($url);
            }
            $parse = parse_url($url);
            $ppgameserver = $parse['scheme'] . '://' . $parse['host'];
        
            if ($response->status() == 302)
            {
                $location = $response->header('location');
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($location);
                $datapath = $ppgameserver . '/gs2c/common/games-html5/games/vs/'. $gamecode . '/';
                $gameservice = $ppgameserver. '/gs2c/ge/v3/gameService';
                if ($response->ok())
                {
                    $content = $response->body();
                    preg_match("/\"datapath\":\"([^\"]*)/", $content, $match);
                    if(!empty($match) && isset($match[1]) && !empty($match[1])){
                        $datapath = $match[1];
                    }
                    preg_match("/\"gameService\":\"([^\"]*)/", $content, $match);
                    if(!empty($match) && isset($match[1]) && !empty($match[1])){
                        $gameservice = $match[1];
                    }
                }
                $keys = explode('&', $location);
                $mgckey = null;
                foreach ($keys as $key){
                    if (str_contains( $key, 'mgckey='))
                    {
                        $mgckey = $key;
                    }
                }
                if (!$mgckey){
                    $this->ppverifyLog($gamecode, $user->id, 'could not find mgckey value');
                    return redirect($failed_url);
                }

                $arr_b_ind_games = ['vs243lionsgold', 'vs10amm', 'vs10egypt', 'vs25asgard', 'vs9aztecgemsdx', 'vs10tut', 'vs243caishien', 'vs243ckemp', 'vs25davinci', 'vs15diamond', 'vs7fire88', 'vs20leprexmas', 'vs20leprechaun', 'vs25mustang', 'vs20santa', 'vs20pistols', 'vs25holiday', 'vs10bbextreme'];
                $arr_b_no_ind_games = ['vs7776secrets', 'vs10txbigbass', 'vs20terrorv', 'vs20drgbless', 'vs5drhs', 'vs20ekingrr', 'vswaysxjuicy', 'vs10goldfish','vs10floatdrg', 'vswaysfltdrg', 'vs20hercpeg', 'vs20honey', 'vs20hburnhs', 'vs4096magician', 'vs9chen', 'vs243mwarrior', 'vs20muertos', 'vs20mammoth', 'vs25peking', 'vswayshammthor', 'vswayslofhero', 'vswaysfrywld', 'vswaysluckyfish', 'vs10egrich', 'vs25rlbank', 'vs40streetracer', 'vs5spjoker', 'vs20superx', 'vs1024temuj', 'vs20doghouse', 'vs20tweethouse', 'vs20amuleteg', 'vs40madwheel', 'vs5trdragons', 'vs10vampwolf', 'vs20vegasmagic', 'vswaysyumyum', 'vs10jnmntzma','vs10kingofdth', 'vswaysrhino', 'vs20xmascarol', 'vswaysaztecking','vswaysrockblst', 'vs20maskgame'];
                $arr_b_gamble_games = ['vs20underground', 'vs40pirgold', 'vs40voodoo', 'vswayswwriches'];

                $cver = 99951;
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get($datapath .'desktop/bootstrap.js');
                if ($response->ok())
                {
                    $content = $response->body();
                    preg_match("/UHT_REVISION={common:'(\d+)',desktop:'\d+',mobile/", $content, $match);
                    if(!empty($match) && isset($match[1]) && !empty($match[1])){
                        $cver = $match[1];
                    }
                }
                $data = [
                    'action' => 'doInit',
                    'symbol' => $gamecode,
                    'cver' => $cver,
                    'index' => 1,
                    'counter' => 1,
                    'repeat' => 0,
                    'mgckey' => explode('=', $mgckey)[1]
                ];
                $v_type = 'v3';
                $response = Http::withOptions(['proxy' => config('app.ppproxy')])->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->asForm()->post($gameservice, $data);
                if (!$response->ok())
                {
                    $this->ppverifyLog($gamecode, $user->id, 'doInit request error');
                    return redirect($failed_url);
                }

                if($stat_game != null){
                    $result = PPController::toJson($response->body());
                    $line = (int)$result['l'] ?? 20;
                    $bet = (float)$result['c'] ?? 100;
                    $arr_bets = isset( $result['sc']) ? explode(',', $result['sc']) : [];
                    $bl = $result['bl'] ?? -1;
                    $spinType = $result['na'] ?? 's';
                    $rs_p = $result['rs_p'] ?? -1;
                    $fsmax = $result['fsmax'] ?? -1;
                    $index = $result['index'] ?? 1;
                    $counter = $result['counter'] ?? 2;
                    $bw = -1;
                    $end = -1;
                    $bgt = -1;
                    $ind = 0;
                    $isRespin = false;
                    $pur_ind = -1;
                    $ppgamelog = \App\Models\PPGameLog::where(['user_id' => $user->id, 'game_id'=>$game->id, 'roundid' => $stat_game->roundid])->orderby('id', 'asc')->first();
                    if(!isset($ppgamelog)){
                        return redirect($ppgameserver . '/gs2c/session/verify?lang=ko&'.$mgckey);
                    }
                    $ppgamelog = json_decode($ppgamelog->str);
                    $pur = -1;
                    if(isset($ppgamelog->request) && isset($ppgamelog->request->pur) && $ppgamelog->request->pur >= 0){
                        $pur = $ppgamelog->request->pur;
                    }
                    if(isset($ppgamelog->request) && isset($ppgamelog->request->bl)){
                        $bl = $ppgamelog->request->bl;
                    }
                    if(isset($ppgamelog->request) && isset($ppgamelog->request->c)){
                        $bet = $ppgamelog->request->c;
                    }
                    if(isset($ppgamelog->request) && isset($ppgamelog->request->l)){
                        $line = $ppgamelog->request->l;
                    }
                    if(isset($ppgamelog->request) && isset($ppgamelog->request->ind)){
                        $pur_ind = $ppgamelog->request->ind;
                    }
                    while(true){
                        try
                        {
                            $data = [
                                'action' => 'doSpin',
                                'symbol' => $gamecode,
                                'index' => $index + 1,
                                'counter' => $counter + 1,
                                'repeat' => 0,
                                'mgckey' => explode('=', $mgckey)[1]
                            ];
                            if($spinType != 'b'){
                                $isRespin = false;
                            }
                            if($spinType == 's'){
                                $data['c'] = $bet;
                                $data['l'] = $line;
                                if($pur_ind >= 0){
                                    $data['ind'] = $pur_ind;
                                    if($gamecode != 'vs15godsofwar'){
                                        $pur_ind = -1;
                                    }
                                }
                            }else if($spinType == 'c'){
                                $data['action'] = 'doCollect';
                            }else if($spinType == 'cb'){
                                $data['action'] = 'doCollectBonus';
                            }else if($spinType == 'fso'){
                                $data['action'] = 'doFSOption';
                                $data['ind'] = 0;
                            }else if($spinType == 'm'){
                                $data['action'] = 'doMysteryScatter';
                                if($gamecode == 'vs10bookfallen'){
                                    $data['ind'] = 0;
                                }
                            }else if($spinType == 'go'){
                                $data['action'] = 'doGambleOption';
                                $data['g_a'] = 'stand';
                                $data['g_o_ind'] = -1;
                            }else if($spinType == 'b'){
                                $data['action'] = 'doBonus';
                                if($isRespin == false){
                                    $isRespin = true;
                                    $ind = 0;
                                }else{
                                    $ind++;
                                }
                                if($gamecode == 'vs7pigs'){
                                    $arr_i_pos = isset($result['i_pos']) ? explode(',', $result['i_pos']) : [1,1,1];
                                    $data['ind'] = $arr_i_pos[$ind];
                                }else if($gamecode == 'vs243queenie'){
                                    if($bw != 1){
                                        $data['ind'] = isset($result['wi']) ? ($result['wi'] + 1) : 1;
                                    }
                                }else if(in_array($gamecode, $arr_b_gamble_games)){
                                    $data['ind'] = 1;
                                }else if(in_array($gamecode, $arr_b_no_ind_games)){
                                    $data['ind'] = 0;
                                }else if(in_array($gamecode, $arr_b_ind_games)){
                                    if($bgt == 9 || $bgt == 23){
                                        $data['ind'] = 0;
                                    }else if($bgt == 11 || $bgt == 31){
                                        
                                    }else if($bgt == 21){
                                        if($end == 0){
                                            $data['ind'] = 0;
                                        }
                                    }else{
                                        $data['ind'] = $ind;
                                    }
                                }
                            }
                            if($bl >= 0){
                                $data['bl'] = $bl;
                            }
                            if($pur >= 0){
                                $data['pur'] = $pur;
                            }
                            $response = Http::withOptions(['proxy' => config('app.ppproxy')])->withHeaders([
                                'Accept' => '*/*',
                                'Content-Type' => 'application/x-www-form-urlencoded'
                                ])->asForm()->post($gameservice, $data);
                            if (!$response->ok())
                            {
                                $this->ppverifyLog($gamecode, $user->id, 'doSpin Error, Body => ' . $response->body());
                                if(in_array($bet, $arr_bets) == false){
                                    $this->ppverifyLog($gamecode, $user->id, 'doSpin BetMoney =' . $bet . ', BetList=' . json_encode($arr_bets));
                                }
                                break;
                            }
                            $result = PPController::toJson($response->body());
                            if(count($result) == 0){
                                $this->ppverifyLog($gamecode, $user->id, 'doSpin Error Data=' . json_encode($data));
                                break;
                            }
                            $spinType = $result['na'] ?? 's';
                            $rs_p = $result['rs_p'] ?? -1;
                            $fsmax = $result['fsmax'] ?? -1;
                            $index = $result['index'] ?? 1;
                            $counter = $result['counter'] ?? 2;
                            $bw = $result['bw'] ?? -1;
                            $end = $result['end'] ?? -1;
                            $bgt = $result['bgt'] ?? -1;
                            $pur = -1;
                            if($spinType == 's' && $rs_p == -1 && $fsmax == -1){
                                \App\Models\PPGameVerifyLog::create([
                                    'game_id' => $game->original_id, 
                                    'user_id' => $user->id, 
                                    'bet' => $stat_game->bet
                                ]);
                                sleep(5);
                                break;
                            }
                            
                        }
                        catch (\Exception $ex)
                        {
                            $this->ppverifyLog($gamecode, $user->id, 'doSpin Error Exception=' . $ex->getMessage() . ', Data=' . json_encode($data));
                            break;
                        }
                    }
                }
                return redirect($ppgameserver . '/gs2c/session/verify?lang=ko&'.$mgckey);
            }else{
                Log::error('server response is not 302.');
                return redirect($failed_url);
            }
        }
        public function ppverifyLog($gamecode, $user_id, $msg)
        {
            $strlog = '';
            $strlog .= "\n";
            $strlog .= date("Y-m-d H:i:s") . ' ';
            $strlog .= $user_id . '->' . $gamecode . ' : ' . $msg;
            $strlog .= "\n";
            $strlog .= ' ############################################### ';
            $strlog .= "\n";
            $strinternallog = '';
            if( file_exists(storage_path('logs/') . 'PPVerify.log') ) 
            {
                $strinternallog = file_get_contents(storage_path('logs/') . 'PPVerify.log');
            }
            file_put_contents(storage_path('logs/') . 'PPVerify.log', $strinternallog . $strlog);
        }

    }

}
