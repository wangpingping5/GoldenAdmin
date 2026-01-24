<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class NEXUSController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const NEXUS_PROVIDER = 'nexus';
        const NEXUS_GAMEKEY = 'B';
        const NEXUS_GAME_IDENTITY = [
            //==== CASINO ====
            'nexus-evo' => ['thirdname' =>'evolution_casino','type' => 'casino', 'symbol'=>'evo', 'skin'=>'B'],              //에볼루션
            'nexus-ppl' => ['thirdname' =>'pragmatic_casino','type' => 'casino', 'symbol'=>'ppl', 'skin'=>'E'],         //프라그마틱 카지노
            'nexus-dg' => ['thirdname' =>'dreamgaming_casino','type' => 'casino', 'symbol'=>'dg', 'skin'=>'B'],         //드림게이밍
            'nexus-asia' => ['thirdname' =>'ag_casino','type' => 'casino', 'symbol'=>'asia', 'skin'=>'B'],              //아시안게이밍 카지노
            'nexus-mgl' => ['thirdname' =>'mggrand_casino','type' => 'casino', 'symbol'=>'mgl', 'skin'=>'A'],           //마이크로게이밍 그랜드 카지노
            'nexus-og' => ['thirdname' =>'orientalgame_casino','type' => 'casino', 'symbol'=>'og', 'skin'=>'B'],        //오리엔탈 게이밍
            'nexus-bota' => ['thirdname' =>'bota_casino','type' => 'casino', 'symbol'=>'bota', 'skin'=>'B'],            //보타 카지노
            //new
            'nexus-bgl' => ['thirdname' =>'bg_casino','type' => 'casino', 'symbol'=>'bgl', 'skin'=>'B'],                //빅게이밍
            'nexus-motil' => ['thirdname' =>'motivation_casino','type' => 'casino', 'symbol'=>'motil', 'skin'=>'A'],    //모티베이션 카지노
            'nexus-ezugil' => ['thirdname' =>'ezugi_casino','type' => 'casino', 'symbol'=>'ezugil', 'skin'=>'A'],       //이주기
            'nexus-taishanl' => ['thirdname' =>'taishan_casino','type' => 'casino', 'symbol'=>'taishanl', 'skin'=>'A'], //taishan 카지노
            'nexus-skyl' => ['thirdname' =>'skywind_casino','type' => 'casino', 'symbol'=>'skyl', 'skin'=>'A'],         //스카이윈드 카지노
            'nexus-betl' => ['thirdname' =>'betgames_casino','type' => 'casino', 'symbol'=>'betl', 'skin'=>'A'],        //벳게임즈 TV
            'nexus-sexyl' => ['thirdname' =>'sexy_casino','type' => 'casino', 'symbol'=>'sexyl', 'skin'=>'B'],          //섹시 바카라
            'nexus-dbl' => ['thirdname' =>'dblive_casino','type' => 'casino', 'symbol'=>'dbl', 'skin'=>'B'],            //DB Live 카지노

            //==== SLOT ====
            'nexus-pp' => ['thirdname' =>'pragmatic_slot','type' => 'slot', 'symbol'=>'pp', 'skin'=>'SLOT'],            //프라그마틱 슬롯
            'nexus-mg' => ['thirdname' =>'mggrand_slot','type' => 'slot', 'symbol'=>'mg', 'skin'=>'SLOT'],              //마이크로게이밍
            'nexus-bng' => ['thirdname' =>'booongo_slot','type' => 'slot', 'symbol'=>'bng', 'skin'=>'SLOT'],            //부운고 
            'nexus-playson' => ['thirdname' =>'playson_slot','type' => 'slot', 'symbol'=>'playson', 'skin'=>'SLOT'],    //플레이손
            'nexus-playngo' => ['thirdname' =>'playngo_slot','type' => 'slot', 'symbol'=>'playngo', 'skin'=>'SLOT'],    //플레이앤고
            'nexus-hbn' => ['thirdname' =>'habanero_slot','type' => 'slot', 'symbol'=>'hbn', 'skin'=>'SLOT'],           //하바네로
            'nexus-rtg' => ['thirdname' =>'evolution_redtiger','type' => 'slot', 'symbol'=>'rtg', 'skin'=>'SLOT'],      //레드타이거
            'nexus-pgsoft' => ['thirdname' =>'pgsoft_slot','type' => 'slot', 'symbol'=>'pgsoft', 'skin'=>'SLOT'],       //PG소프트
            //new
            'nexus-ag' => ['thirdname' =>'ag_slot','type' => 'slot', 'symbol'=>'ag', 'skin'=>'SLOT'],                   //아시안게이밍 슬롯
            'nexus-bp' => ['thirdname' =>'blueprint_slot','type' => 'slot', 'symbol'=>'bp', 'skin'=>'SLOT'],            //블루프린트 슬롯
            'nexus-cq9' => ['thirdname' =>'cq9_slot','type' => 'slot', 'symbol'=>'cq9', 'skin'=>'SLOT'],                //CQ9 슬롯
            'nexus-sh' => ['thirdname' =>'spearhead_slot','type' => 'slot', 'symbol'=>'sh', 'skin'=>'SLOT'],            //슬롯 매트릭스
            'nexus-gmw' => ['thirdname' =>'gmw_slot','type' => 'slot', 'symbol'=>'gmw', 'skin'=>'SLOT'],                //GMW
            'nexus-netent' => ['thirdname' =>'netent_slot','type' => 'slot', 'symbol'=>'netent', 'skin'=>'SLOT'],       //넷엔트
            'nexus-btg' => ['thirdname' =>'btg_slot','type' => 'slot', 'symbol'=>'btg', 'skin'=>'SLOT'],                //BTG
            'nexus-aspect' => ['thirdname' =>'aspect_slot','type' => 'slot', 'symbol'=>'aspect', 'skin'=>'SLOT'],       //Aspect
            'nexus-betsoft' => ['thirdname' =>'betsoft_slot','type' => 'slot', 'symbol'=>'betsoft', 'skin'=>'SLOT'],    //Betsoft 슬롯
            'nexus-skywind' => ['thirdname' =>'skywind_slot','type' => 'slot', 'symbol'=>'skywind', 'skin'=>'SLOT'],    //스카이윈드 슬롯
            'nexus-ftg' => ['thirdname' =>'ftg_slot','type' => 'slot', 'symbol'=>'ftg', 'skin'=>'SLOT'],                //FTG 슬롯
            'nexus-netgaming' => ['thirdname' =>'netgaming','type' => 'slot', 'symbol'=>'netgaming', 'skin'=>'SLOT'],   //넷게이밍
            'nexus-ps' => ['thirdname' =>'ps_slot','type' => 'slot', 'symbol'=>'ps', 'skin'=>'SLOT'],                   //플레이스타 슬롯
            'nexus-naga' => ['thirdname' =>'naga_slot','type' => 'slot', 'symbol'=>'naga', 'skin'=>'SLOT'],             //NAGA
            'nexus-nspin' => ['thirdname' =>'nspin_slot','type' => 'slot', 'symbol'=>'nspin', 'skin'=>'SLOT'],          //Nextspin 슬롯
            'nexus-evop' => ['thirdname' =>'evo_slot','type' => 'slot', 'symbol'=>'evop', 'skin'=>'SLOT'],              //에보플레이 슬롯
            'nexus-aux' => ['thirdname' =>'aux_slot','type' => 'slot', 'symbol'=>'aux', 'skin'=>'SLOT'],                //아바타UX 슬롯
            'nexus-relax' => ['thirdname' =>'relax_slot','type' => 'slot', 'symbol'=>'relax', 'skin'=>'SLOT'],          //릴렉스
            'nexus-reelp' => ['thirdname' =>'reelplay_slot','type' => 'slot', 'symbol'=>'reelp', 'skin'=>'SLOT'],       //릴플레이 슬롯
            'nexus-fant' => ['thirdname' =>'fantasma_slot','type' => 'slot', 'symbol'=>'fant', 'skin'=>'SLOT'],         //판타즈마 슬롯
            'nexus-boome' => ['thirdname' =>'boomerang_slot','type' => 'slot', 'symbol'=>'boome', 'skin'=>'SLOT'],      //부메랑 슬롯
            'nexus-4tp' => ['thirdname' =>'4tp_slot','type' => 'slot', 'symbol'=>'4tp', 'skin'=>'SLOT'],                //포더플레이어 슬롯
            'nexus-nlc' => ['thirdname' =>'nlc_slot','type' => 'slot', 'symbol'=>'nlc', 'skin'=>'SLOT'],                //노리밋시티 슬롯
            'nexus-hs' => ['thirdname' =>'hs_slot','type' => 'slot', 'symbol'=>'hs', 'skin'=>'SLOT'],                   //핵쏘우게이밍 슬롯
            'nexus-joker' => ['thirdname' =>'joker_slot','type' => 'slot', 'symbol'=>'joker', 'skin'=>'SLOT'],          //조커 슬롯
            'nexus-jili' => ['thirdname' =>'jili_slot','type' => 'slot', 'symbol'=>'jili', 'skin'=>'SLOT'],             //JILI 슬롯
            'nexus-funky' => ['thirdname' =>'funkygames_slot','type' => 'slot', 'symbol'=>'funky', 'skin'=>'SLOT'],     //펀키게임즈 슬롯
            'nexus-bgaming' => ['thirdname' =>'bgaming_slot','type' => 'slot', 'symbol'=>'bgaming', 'skin'=>'SLOT'],    //비게이밍 슬롯
            'nexus-booming' => ['thirdname' =>'booming_slot','type' => 'slot', 'symbol'=>'booming', 'skin'=>'SLOT'],    //부밍게임즈 슬롯
            'nexus-expanse' => ['thirdname' =>'expanse_slot','type' => 'slot', 'symbol'=>'expanse', 'skin'=>'SLOT'],    //익스팬스 슬롯
            'nexus-ygr' => ['thirdname' =>'ygr_slot','type' => 'slot', 'symbol'=>'ygr', 'skin'=>'SLOT'],                //YGR
            'nexus-dragon' => ['thirdname' =>'dragoonsoft_slot','type' => 'slot', 'symbol'=>'dragon', 'skin'=>'SLOT'],  //드래곤소프트
            'nexus-wazdan' => ['thirdname' =>'wazdan_slot','type' => 'slot', 'symbol'=>'wazdan', 'skin'=>'SLOT'],       //와즈단
            'nexus-octo' => ['thirdname' =>'octoplay_slot','type' => 'slot', 'symbol'=>'octo', 'skin'=>'SLOT'],         //옥토플레이
            'nexus-ygg' => ['thirdname' =>'ygg_slot','type' => 'slot', 'symbol'=>'ygg', 'skin'=>'SLOT'],                //이그드라실
            'nexus-novo' => ['thirdname' =>'novomatic_slot','type' => 'slot', 'symbol'=>'novo', 'skin'=>'SLOT'],        //노보메틱
            'nexus-bt1' => ['thirdname' =>'bt1_sports','type' => 'sports', 'symbol'=>'bt1', 'skin'=>'SPORTS'],          //BT1
        ];
        public static function getGameObj($uuid)
        {
            foreach (NEXUSController::NEXUS_GAME_IDENTITY as $ref => $value)
            {
                $gamelist = NEXUSController::getgamelist($ref);
                if ($gamelist)
                {
                    foreach($gamelist as $game)
                    {
                        if ($game['gamecode'] == $uuid)
                        {
                            return $game;
                            break;
                        }
                        if ($game['gameid'] == $uuid)
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
        * CLEINT IP
        */
        public static function getIp(){
            foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key){
                if (array_key_exists($key, $_SERVER) === true){
                    foreach (explode(',', $_SERVER[$key]) as $ip){
                        $ip = trim($ip); // just to be safe
                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false){
                            return $ip;
                        }
                    }
                }
            }
        }

        /*
        * GENERATE HASH
        */
        public static function generateHash($data){
            //$opKey = 'f3fff4d3-cbf6-46ef-b710-eea686fad487';
            $secretKey = config('app.nexus_secretkey');
            if($data == null || count($data) == 0)
            {
                $result = $secretKey;
            }
            else
            {
                $body = json_encode($data);
                // json String + secretKey
                $result = $body.$secretKey;
            }

            // SHA-256 make hash
            $hash_data = hash('sha256', $result, true);
            
            // Base64 encoding
            $hash = base64_encode($hash_data);

            return $hash;
        }
        /*
        * FROM CONTROLLER, API
        */
        public static function sendRequest($sub_url, $param) {

            $url = config('app.nexus_api') ;
            $agent = config('app.nexus_agent');
            $hash = NEXUSController::generateHash($param);
            try {       
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'hash' => $hash,
                    'agent' => $agent
                    ])->asForm()->post($url . $sub_url, $param);
                
                if ($response->ok()) {
                    $res = $response->json();
                    return $res;
                }
                else
                {
                    Log::error('Nexus Request : response is not okay. api=>' . $sub_url . ', param =>' . json_encode($param) . ', body=>' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('Nexus Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Nexus Request :  Excpetion. PARAMS= ' . json_encode($param));
            }
            return null;
        }
        public static function moneyInfo($user_code) {
            
            $params = [
                'username' => $user_code,
                'siteUsername' => $user_code,
            ];

            $data = NEXUSController::sendRequest('/balance', $params);
            return $data;
        }
        
        public static function getUserBalance($href, $user) {   

            $balance = -1;
            $user_code = self::NEXUS_PROVIDER  . sprintf("%04d",$user->id);
            $data = NEXUSController::moneyInfo($user_code);

            if ($data && $data['code'] == 0)
            {
                $balance = $data['balance'];
            }
            return intval($balance);
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
            $category = NEXUSController::NEXUS_GAME_IDENTITY[$href];
            
            $skin = $category['skin'];
            $vendorKey = $category['thirdname'];
            $params = [
                'vendorKey' => $vendorKey,
                'skin' => $skin,
            ];

            $gameList = [];
            $data = NEXUSController::sendRequest('/games', $params);
            $view = 1;
            if($category['type'] == 'casino'){
                $view = 0;
            }
            if ($data && $data['code'] == 0)
            {
                foreach ($data['games'] as $game)
                {
                    $gamename = 'Unknown';
                    $gametitle = 'Unknown';
                    if(isset($game['names']))
                    {
                        $gametitle = isset($game['names']['ko'])?$game['names']['ko']:$game['names']['en'];
                        $gamename = $game['names']['en'];
                    }
                    $icon = '';
                    if(isset($game['image']))
                    {
                        $icon = $game['image'];
                    }
                    array_push($gameList, [
                        'provider' => self::NEXUS_PROVIDER,
                        'vendorKey' => $vendorKey, 
                        'href' => $href,
                        'gameid' => $game['id'],
                        'gamecode' => $vendorKey.'_'.$game['key'],
                        'symbol' => $game['key'],
                        'name' => preg_replace('/\s+/', '', $gamename),
                        'title' => $gametitle,
                        'type' => ($category['type']=='slot')?'slot':'table',
                        'skin' => $skin,
                        'icon' => $icon,
                        'view' => $view
                    ]);
                }
                //add casino lobby
                if($category['type'] == 'casino'){
                    array_push($gameList, [
                        'provider' => self::NEXUS_PROVIDER,
                        'vendorKey' => $vendorKey, 
                        'gameid' => $category['symbol'],
                        'href' => $href,
                        'gamecode' => $vendorKey,
                        'symbol' => $skin,
                        'name' => 'Lobby',
                        'title' => 'Lobby',
                        'skin' => $skin,
                        'type' => 'table',
                        'icon' => '/frontend/Default/ico/gold/EVOLUTION_Lobby.jpg',
                        'view' => 1
                    ]);
                }
            }

            //add Unknown Game item
            array_push($gameList, [
                'provider' => self::NEXUS_PROVIDER,
                'vendorKey' => $vendorKey, 
                'href' => $href,
                'gameid' => 'Unknown',
                'symbol' => 'Unknown',
                'gamecode' => 'Unknown',
                'enname' => 'UnknownGame',
                'name' => 'UnknownGame',
                'title' => 'UnknownGame',
                'skin' => $skin,
                'icon' => '',
                'type' => ($category['type']=='slot')?'slot':'table',
                'view' => 0
            ]);
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
            
        }

        public static function makegamelink($gamecode, $user) 
        {
            $user_code = self::NEXUS_PROVIDER  . sprintf("%04d",$user->id);
            $game = NEXUSController::getGameObj($gamecode);
            if (!$game)
            {
                return null;
            }
            $params = [
                'vendorKey' => $game['vendorKey'],
                'gameKey' => $game['symbol'],
                'siteUsername' => $user_code,
                'ip' => '',
                'language' => 'ko',
                'requestKey' => $user->generateCode(24)
            ];

            $data = NEXUSController::sendRequest('/play', $params);
            $url = null;

            if ($data && $data['code'] == 0)
            {
                $url = $data['url'];
            }
            else
            {
                Log::error('NEXUSMakeLink : geturl, msg=  ' . $data['msg']);
            }

            return $url;
        }
        
        public static function withdrawAll($href, $user)
        {
            $balance = NEXUSController::getuserbalance($href,$user);
            if ($balance < 0)
            {
                return ['error'=>true, 'amount'=>$balance, 'msg'=>'getuserbalance return -1'];
            }
            if ($balance > 0)
            {
                $params = [
                    'username' => self::NEXUS_PROVIDER  . sprintf("%04d",$user->id),
                    'siteUsername' => self::NEXUS_PROVIDER  . sprintf("%04d",$user->id),
                    'requestKey' => $user->generateCode(24)
                ];

                $data = NEXUSController::sendRequest('/withdraw', $params);
                if ($data==null || $data['code'] != 0)
                {
                    return ['error'=>true, 'amount'=>0, 'msg'=>'data not ok'];
                }
            }
            return ['error'=>false, 'amount'=>$balance];
        }

        public static function makelink($gamecode, $userid)
        {
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('NEXUSMakeLink : Does not find user ' . $userid);
                return null;
            }

            $game = NEXUSController::getGameObj($gamecode);
            if ($game == null)
            {
                Log::error('NEXUSMakeLink : Game not find  ' . $gamecode);
                return null;
            }
            $user_code = self::NEXUS_PROVIDER  . sprintf("%04d",$user->id);
            $balance = 0;

            //?��??�보 조회
            $data = NEXUSController::moneyInfo($user_code);
            if($data == null || $data['code'] != 0)
            {
                //?�유?� 창조
                $params = [
                    'username' => $user_code,
                    'nickname' => $user_code,
                    'siteUsername' => $user_code,
                ];

                $data = NEXUSController::sendRequest('/register', $params);
                if ($data==null || $data['code'] != 0)
                {
                    Log::error('NEXUSMakeLink : Player Create, msg=  ' . $data['msg']);
                    return null;
                }
            }
            else
            {
                $balance = $data['balance'];
            }

            if ($balance != $user->balance)
            {
                //withdraw all balance
                $data = NEXUSController::withdrawAll($game['href'], $user);
                if ($data['error'])
                {
                    return null;
                }
                //Add balance

                if ($user->balance > 0)
                {
                    $params = [
                        'username' => $user_code,
                        'siteUsername' => $user_code,
                        "amount" =>  intval($user->balance),
                        "requestKey" => $user->generateCode(24)
                    ];
    
                    $data = NEXUSController::sendRequest('/deposit', $params);
                    if ($data==null || $data['code'] != 0)
                    {
                        Log::error('NEXUSMakeLink : addMemberPoint result failed. ' . ($data==null?'null':json_encode($data)));
                        return null;
                    }
                    
                }
            }
            
            return '/followgame/nexus/'.$gamecode;

        }

        public static function getgamelink($gamecode)
        {
            if (isset(self::NEXUS_GAME_IDENTITY[$gamecode]))
            {
                $gamelist = NEXUSController::getgamelist($gamecode);
                if (count($gamelist) > 0)
                {
                    foreach ($gamelist as $g)
                    {
                        if ($g['view'] == 1)
                        {
                            $gamecode = $g['gamecode'];
                            break;
                        }
                    }
                    
                }
            }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [NEXUSController::NEXUS_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($fromtimepoint, $totimepoint)
        {
            $sdate = date('Y-m-d H:i:s', $fromtimepoint);
            $edate = date('Y-m-d H:i:s', $totimepoint);
            $params = [
                'sdate' => $sdate,
                'edate' => $edate,
                'limit' => 4000
            ];

            $data = NEXUSController::sendRequest('/transaction', $params);

            return $data;
        }

        public static function processGameRound($frompoint=-1, $checkduplicate=false)
        {
            $timepoint = strtotime(date('Y-m-d H:i:s',strtotime('-2 hours'))) - 9 * 60 * 60;
            $totimepoint = strtotime(date('Y-m-d H:i:s')) - 9 * 60 * 60;
            if ($frompoint == -1)
            {
                $tpoint = \App\Models\Settings::where('key', self::NEXUS_PROVIDER . 'timepoint')->first();
                if ($tpoint)
                {
                    $timepoint = $tpoint->value;
                }
            }
            else
            {
                $timepoint = $frompoint;
            }
            if($timepoint > $totimepoint)
            {
                $totimepoint = $timepoint + 2 * 60 * 60;
            }
            $count = 0;
            $data = null;
            $newtimepoint = $timepoint;
            $totalCount = 0;
            $data = NEXUSController::gamerounds($timepoint, $totimepoint);
            if ($data == null || $data['code'] != 0 || $data['lastObjectId'] == '')
            {
                if ($frompoint == -1)
                {

                    if ($tpoint)
                    {
                        $tpoint->update(['value' => $newtimepoint]);
                    }
                    else
                    {
                        \App\Models\Settings::create(['key' => self::NEXUS_PROVIDER .'timepoint', 'value' => $newtimepoint]);
                    }
                }

                return [0,0];
            }
            $lastObjectId = $data['lastObjectId'];
            $invalidRounds = [];
            if (isset($data['transactions']))
            {
                foreach ($data['transactions'] as $round)
                {
                    // if ($round['txn_type'] != 'CREDIT')
                    // {
                    //     continue;
                    // }
                    if($round['_id'] == $lastObjectId)
                    {
                        $timepoint = strtotime($round['createdAt']) + 1 - 9 * 60 * 60;
                    }
                    $gameObj = NEXUSController::getGameObj($round['gameId']);
                    
                    if (!$gameObj)
                    {
                        $gameObj = NEXUSController::getGameObj($round['vendorKey']);
                        if (!$gameObj)
                        {
                            Log::error('NEXUS Game could not found : '. $round['gameId']);
                            continue;
                        }
                    }
                    $bet = 0;
                    $win = 0;
                    if($round['type'] == 'turn_bet')
                    {
                        $bet = $round['cash'];
                    }
                    else if($round['type'] == 'turn_win' || $round['type'] == 'turn_draw' || $round['type'] == 'turn_cancel' || $round['type'] == 'turn_adjust')
                    {
                        $win = $round['cash'];
                        if(isset($round['updepositCash']) && $round['updepositCash'] > 0)
                        {
                            $win += $round['updepositCash'];
                        }
                    }
                    if($bet == 0 && $win == 0)
                    {
                        continue;
                    }
                    $balance = $round['afterCash'];
                    
                    $time = date('Y-m-d H:i:s',strtotime($round['createdAt']));

                    $userid = intval(preg_replace('/'. self::NEXUS_PROVIDER  .'(\d+)/', '$1', $round['siteUsername'])) ;
                    if($userid == 0){
                        $userid = intval(preg_replace('/'. self::NEXUS_PROVIDER . 'user' .'(\d+)/', '$1', $round['siteUsername'])) ;
                    }

                    $shop = \App\Models\ShopUser::where('user_id', $userid)->first();
                    $category = \App\Models\Category::where('href', $gameObj['href'])->first();

                    // if ($checkduplicate)
                    // {
                        $checkGameStat = \App\Models\StatGame::where([
                            'user_id' => $userid, 
                            'bet' => $bet, 
                            'win' => $win, 
                            'date_time' => $time,
                            'roundid' => $round['vendorKey'] . '#' . $round['gameId'] . '#' . $round['transactionKey'],
                        ])->first();
                        if ($checkGameStat)
                        {
                            if($round['type'] == 'turn_adjust')
                        {
                            $checkGameStat->update(['win' => $win]);
                            }
                            continue;
                        }
                    // }
                    $gamename = $gameObj['name'] . '_nexus';
                    if($round['type'] == 'turn_cancel')  // 취소처리??경우
                    {
                        $gamename = $gameObj['name'] . '_nexus[C'.$time.']_' . $gameObj['href'];
                    }
                    \App\Models\StatGame::create([
                        'user_id' => $userid, 
                        'balance' => $balance, 
                        'bet' => $bet, 
                        'win' => $win, 
                        'game' => $gamename, 
                        'type' => $gameObj['type'],
                        'percent' => 0, 
                        'percent_jps' => 0, 
                        'percent_jpg' => 0, 
                        'profit' => 0, 
                        'denomination' => 0, 
                        'date_time' => $time,
                        'shop_id' => $shop?$shop->shop_id:-1,
                        'category_id' => $category?$category->original_id:0,
                        'game_id' =>  $gameObj['gamecode'],
                        // 'status' =>  $gameObj['isFinished']==true ? 1 : 0,
                        'roundid' => $round['vendorKey'] . '#' . $round['gameId'] . '#' . $round['transactionKey'],
                    ]);
                    $count = $count + 1;
                }
            }
            if ($frompoint == -1)
            {

                if ($tpoint)
                {
                    $tpoint->update(['value' => $timepoint]);
                }
                else
                {
                    \App\Models\Settings::create(['key' => self::NEXUS_PROVIDER .'timepoint', 'value' => $timepoint]);
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
            $transactionKey = $betrounds[2];
            
            $params = [
                'transactionKey' => $transactionKey,
                'lang' => 'ko'
            ];

            $data = NEXUSController::sendRequest('/getdetailurl', $params);
            if ($data==null || $data['code'] != 0)
            {
                return null;
            }
            else
            {
                return $data['url'];
            }
        }


        public static function getAgentBalance()
        {

            $balance = -1;

            $params = [];

            $data = NEXUSController::sendRequest('/partner/balance', $params);
            if ($data==null || $data['code'] != 0)
            {
                return null;
            }
            if ($data && $data['code'] == 0)
            {
                $balance = $data['balance'];
            }
            return intval($balance);
        }


    }

}
