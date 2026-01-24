<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class TAISHANController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const TAISHAN_PROVIDER = 'taishan';

        public static function getGameObj($href)
        {
            $gamelist = TAISHANController::getgamelist($href);
            if ($gamelist)
            {
                foreach($gamelist as $game)
                {
                    if ($game['gamecode'] == $href)
                    {
                        return $game;
                        break;
                    }
                }
            }
            return null;
        }

        public static function refreshToken()
        {

            $url = config('app.taishan_api') . '/oauth/refreshToken';
            $op = config('app.taishan_op');
            $oppass = config('app.taishan_pass');
            $token = config('app.taishan_token');
            $curToken = \App\Models\Settings::where('key', self::TAISHAN_PROVIDER . 'token')->first();
            if ($curToken)
            {
                $token = $curToken->value;
            }
            Log::error('refresh token : old = ' . $token);

            $params = [
                'partnerid' => $op,
                'partnerpass' => $oppass,
            ];

            $headers = [
                'Authorization' => 'Bearer ' . $token
            ];

            try {       
                $response = Http::withHeaders($headers)->get($url, $params);
                
                if ($response->ok()) {
                    $res = $response->json();
        
                    if ($res!=null && isset($res['result']) && $res['result'] == 'success') {
                        $token = $res['token'];
                        if ($curToken)
                        {
                            $curToken->update(['value' => $token]);
                        }
                        else
                        {
                            \App\Models\Settings::create(['key' => self::TAISHAN_PROVIDER . 'token', 'value' => $token]);
                        }
                        Log::error('refresh token : success !!!! ');
                    }
                    else
                    {
                        Log::error('TAISAHN refreshToken : return failed. ' . $response->body());
                    }
                }
                else
                {
                    Log::error('TAISAHN refreshToken : response is not okay. ' . json_encode($params) . '===body==' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('TAISAHN refreshToken : refreshToken Excpetion. exception= ' . $ex->getMessage());
                Log::error('TAISAHN refreshToken : refreshToken Excpetion. PARAMS= ' . json_encode($params));
            }

        }

        /*
        */

        
        /*
        * FROM CONTROLLER, API
        */

        
        public static function getUserBalance($href, $user, $prefix=self::TAISHAN_PROVIDER) {
            $url = config('app.taishan_api') . '/api/userInfo';
            $op = config('app.taishan_op');
            $token = config('app.taishan_token');

            $curToken = \App\Models\Settings::where('key', self::TAISHAN_PROVIDER . 'token')->first();
            if ($curToken)
            {
                $token = $curToken->value;
            }
    
            $params = [
                'partnerid' => $op,
                'userid' => $prefix . sprintf("%05d",$user->id),
            ];

            $headers = [
                'Authorization' => 'Bearer ' . $token
            ];

            $balance = -1;

            try {       
                $response = Http::withHeaders($headers)->get($url, $params);
                
                if ($response->ok()) {
                    $res = $response->json();
        
                    if ($res!=null && isset($res['cash'])) {
                        $balance = $res['cash'];
                    }
                    else
                    {
                        Log::error('TAISAHNgetuserbalance : return failed. ' . $response->body());
                        if (isset($data['code']))
                        {
                            TAISHANController::refreshToken();
                        }
                    }
                }
                else
                {
                    Log::error('TAISAHNgetuserbalance : response is not okay. ' . json_encode($params) . '===body==' . $response->body());
                }
            }
            catch (\Exception $ex)
            {
                Log::error('TAISAHNgetuserbalance : getAccountBalance Excpetion. exception= ' . $ex->getMessage());
                Log::error('TAISAHNgamerounds : getAccountBalance Excpetion. PARAMS= ' . json_encode($params));
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
            
            $gameList = [];

            //add Unknown Game item
            array_push($gameList, [
                'provider' => self::TAISHAN_PROVIDER,
                'href' => $href,
                'symbol' => 'taishan',
                'gamecode' => $href,
                'enname' => 'TaishanGame',
                'name' => 'TaishanGame',
                'title' => __('gamename.TaishanGame'),
                'icon' => '',
                'type' => 'table',
                'view' => 1
            ]);
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
            
        }

        public static function makegamelink($gamecode, $user, $prefix=self::TAISHAN_PROVIDER) 
        {

            $op = config('app.taishan_op');
            $token = config('app.taishan_token');
            $curToken = \App\Models\Settings::where('key', self::TAISHAN_PROVIDER . 'token')->first();
            if ($curToken)
            {
                $token = $curToken->value;
            }
            
               //Create Game link

            $params = [
                'partnerid' => $op,
                'userid' => $prefix . sprintf("%05d",$user->id),
                'userpass' => '000000',
                'lang' => 'ko'
            ];
            $headers = [
                'Authorization' => 'Bearer ' . $token
            ];
            $url = config('app.taishan_api') . '/api/userLoginJson';
            $response = Http::withHeaders($headers)->get($url, $params);
            if (!$response->ok())
            {
                Log::error('TAISAHNGetLink : Game url request failed. status=' . $response->status());
                Log::error('TAISAHNGetLink : Game url request failed. param=' . json_encode($params));

                return null;
            }
            $data = $response->json();
            if ($data==null || $data['result'] != 'success')
            {
                Log::error('TAISAHNGetLink : Game url result failed. ' . $response->body());
                if (isset($data['code']))
                {
                    TAISHANController::refreshToken();
                }
                return null;
            }
            $url = $data['callbackurl'];

            return $url;
        }
        
        public static function withdrawAll($href, $user, $prefix=self::TAISHAN_PROVIDER)
        {
            $balance = TAISHANController::getuserbalance($href,$user,$prefix);
            if ($balance < 0)
            {
                return ['error'=>true, 'amount'=>$balance, 'msg'=>'getuserbalance return -1'];
            }
            if ($balance > 0)
            {
                $op = config('app.taishan_op');
                $oppass = config('app.taishan_pass');
                $token = config('app.taishan_token');
                $curToken = \App\Models\Settings::where('key', self::TAISHAN_PROVIDER . 'token')->first();
                if ($curToken)
                {
                    $token = $curToken->value;
                }

                $params = [
                    'cash' => $balance,
                    'partnerid' => $op,
                    'partnerpass' => $oppass,
                    'token' => $token,
                    'userid' => $prefix . sprintf("%05d",$user->id),
                    'cashtype' => 'withdraw'
                ];

                $headers = [
                    'Authorization' => 'Bearer ' . $token
                ];

                try {
                    $url = config('app.taishan_api') . '/api/userCash';
                    $response = Http::withHeaders($headers)->get($url, $params);
                    if (!$response->ok())
                    {
                        Log::error('TAISAHNWithdraw : subtractMemberPoint request failed. ' . $response->body());

                        return ['error'=>true, 'amount'=>0, 'msg'=>'response not ok'];
                    }
                    $data = $response->json();
                    if ($data==null || $data['result'] != 'success')
                    {
                        Log::error('TAISAHNWithdraw : subtractMemberPoint result failed. PARAMS=' . json_encode($params));
                        Log::error('TAISAHNWithdraw : subtractMemberPoint result failed. ' . $response->body());
                        if (isset($data['code']))
                        {
                            TAISHANController::refreshToken();
                        }
                        return ['error'=>true, 'amount'=>0, 'msg'=>'data not ok'];
                    }
                }
                catch (\Exception $ex)
                {
                    Log::error('TAISAHNWithdraw : subtractMemberPoint Exception. Exception=' . $ex->getMessage());
                    Log::error('TAISAHNWithdraw : subtractMemberPoint Exception. PARAMS=' . json_encode($params));
                    return ['error'=>true, 'amount'=>0, 'msg'=>'exception'];
                }
            }
            return ['error'=>false, 'amount'=>$balance];
        }

        public static function makelink($gamecode, $userid)
        {
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('TAISAHNMakeLink : Does not find user ' . $userid);
                return null;
            }

            $game = TAISHANController::getGameObj($gamecode);
            if ($game == null)
            {
                Log::error('TAISAHNMakeLink : Game not find  ' . $game);
                return null;
            }

            $op = config('app.taishan_op');
            $oppass = config('app.taishan_pass');
            $token = config('app.taishan_token');
            $curToken = \App\Models\Settings::where('key', self::TAISHAN_PROVIDER . 'token')->first();
            if ($curToken)
            {
                $token = $curToken->value;
            }

            //create taishan account
            $params = [
                'partnerid' => $op,
                'userid' => self::TAISHAN_PROVIDER . sprintf("%05d",$user->id),
                'userpass' => '000000'
            ];

            $headers = [
                'Authorization' => 'Bearer ' . $token
            ];

            try
            {

                $url = config('app.taishan_api') . '/api/userCreate';
                $response = Http::withHeaders($headers)->get($url, $params);
                if (!$response->ok())
                {
                    Log::error('TAISAHNmakelink : createAccount request failed. ' . $response->body());
                    return null;
                }
                $data = $response->json();
                if ($data==null || ($data['result'] != 'success' && $data['result'] != 'exist'))
                {
                    Log::error('TAISAHNmakelink : createAccount result failed. '  . $response->body());
                    if (isset($data['code']))
                    {
                        TAISHANController::refreshToken();
                    }
                    return null;
                }
            }
            catch (\Exception $ex)
            {
                Log::error('TAISAHNcheckuser : createAccount Exception. Exception=' . $ex->getMessage());
                Log::error('TAISAHNcheckuser : createAccount Exception. PARAMS=' . json_encode($params));
                return null;
            }
            
            $balance = TAISHANController::getuserbalance($gamecode, $user);
            if ($balance == -1)
            {
                return null;
            }

            if ($balance != $user->balance)
            {
                //withdraw all balance
                $data = TAISHANController::withdrawAll($gamecode, $user);
                if ($data['error'])
                {
                    return null;
                }
                //Add balance

                if ($user->balance > 0)
                {
                    $params = [
                        'cash' => $user->balance,
                        'partnerid' => $op,
                        'partnerpass' => $oppass,
                        'token' => $token,
                        'userid' => self::TAISHAN_PROVIDER . sprintf("%05d",$user->id),
                        'cashtype' => 'deposit'
                    ];
    
                    $headers = [
                        'Authorization' => 'Bearer ' . $token
                    ];
    
                    try {
                        $url = config('app.taishan_api') . '/api/userCash';
                        $response = Http::withHeaders($headers)->get($url, $params);
                        if (!$response->ok())
                        {
                            Log::error('TAISAHNWithdraw : addMemberPoint request failed. ' . $response->body());
    
                            return null;
                        }
                        $data = $response->json();
                        if ($data==null || $data['result'] != 'success')
                        {
                            Log::error('TAISAHNWithdraw : addMemberPoint result failed. PARAMS=' . json_encode($params));
                            Log::error('TAISAHNWithdraw : addMemberPoint result failed. ' . $response->body());
                            return null;
                        }
                    }
                    catch (\Exception $ex)
                    {
                        Log::error('TAISAHNWithdraw : addMemberPoint Exception. Exception=' . $ex->getMessage());
                        Log::error('TAISAHNWithdraw : addMemberPoint Exception. PARAMS=' . json_encode($params));
                        return null;
                    }
                }
            }
            
            return '/followgame/taishan/'.$gamecode;

        }

        public static function getgamelink($gamecode)
        {
            $game = TAISHANController::getGameObj($gamecode);
            if ($game == null)
            {
                return ['error' => true, 'msg' => 'Could not find game'];
            }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [TAISHANController::TAISHAN_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($pageIdx, $startDate, $endDate)
        {
            
            $op = config('app.taishan_op');
            $oppass = config('app.taishan_pass');
            $token = config('app.taishan_token');
            $curToken = \App\Models\Settings::where('key', self::TAISHAN_PROVIDER . 'token')->first();
            if ($curToken)
            {
                $token = $curToken->value;
            }

            $params = [
                'partnerid' => $op,
                'partnerpass' => $oppass,
                'startdate' => explode(' ',$startDate)[0],
                'starttime' => explode(' ',$startDate)[1],
                'enddate' => explode(' ',$endDate)[0],
                'endtime' => explode(' ',$endDate)[1],
                'pagesize' => 1000,
                'pageno' => $pageIdx,
                'time' => time()*1000,
                'gamecode' => 'all',
            ];

            $headers = [
                'Authorization' => 'Bearer ' . $token
            ];

            try
            {
                $url = config('app.taishan_api') . '/api/jsonBetLog';
                $response = Http::withHeaders($headers)->get($url, $params);
                if (!$response->ok())
                {
                    Log::error('TAISHANgamerounds : getBetWinHistoryAll request failed. PARAMS= ' . json_encode($params));
                    Log::error('TAISHANgamerounds : getBetWinHistoryAll request failed. ' . $response->body());

                    return null;
                }
                $data = $response->json();
                if ($data==null)
                {
                    Log::error('TAISHANgamerounds : getBetWinHistoryAll result failed. PARAMS=' . json_encode($params));
                    Log::error('TAISHANgamerounds : getBetWinHistoryAll result failed. ' . $response->body());
                    return null;
                }
                if (isset($data['code']))
                {
                    TAISHANController::refreshToken();
                }

                return $data;
            }
            catch (\Exception $ex)
            {
                Log::error('TAISHANgamerounds : getBetWinHistoryAll Excpetion. exception= ' . $ex->getMessage());
                Log::error('TAISHANgamerounds : getBetWinHistoryAll Excpetion. PARAMS= ' . json_encode($params));
            }
            return null;
        }

        public static function processGameRound($from='', $to='', $checkduplicate=false)
        {
            $count = 0;


                $category = \App\Models\Category::where([
                    'provider'=> TAISHANController::TAISHAN_PROVIDER,
                    'href' => TAISHANController::TAISHAN_PROVIDER,
                    'shop_id' => 0,
                    'site_id' => 0,
                    ])->first();

                $roundfrom = '';
                $roundto = '';

                if ($from == '')
                {
                    $roundfrom = date('Y-m-d H:i:s',strtotime('-12 hours'));
                    $lastround = \App\Models\StatGame::where('category_id', $category->original_id)->orderby('date_time', 'desc')->first();
                    if ($lastround)
                    {
                        $d = strtotime($lastround->date_time);
                        if ($d > strtotime("-12 hours"))
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
                    Log::error('TAISHAN processGameRound : '. $roundto . '>' . $roundfrom);
                    return [0, 0];
                }
                $totalcount = 0;
                do
                {
                    $curend_timeStamp = $start_timeStamp + 3600; // 1hours
                    if ($curend_timeStamp > $end_timeStamp)
                    {
                        $curend_timeStamp = $end_timeStamp;
                    }

                    $curPage = 1;
                    $data = null;
                    do
                    {
                        $data = TAISHANController::gamerounds( $curPage, date('Ymd His', $start_timeStamp), date('Ymd His', $curend_timeStamp));
                        if ($data == null)
                        {
                            Log::error('TAISHAN gamerounds failed : '. date('Y-m-d H:i:s', $start_timeStamp) . '~' . date('Y-m-d H:i:s', $curend_timeStamp));
                            sleep(60);
                            continue;
                        }
                        
                        if (isset($data['totalCount']) && $data['totalCount'] > 0)
                        {
                            foreach ($data['data'] as $round)
                            {
                                $bet = $round['TOTAL_BETTING'];
                                $win = $round['TOTAL_BETTING'] + $round['USER_BALANCE'];
                                $balance = $round['END_BALANCE'];

                                $gameName = str_replace(' ', '', $round['CHANNEL_NAME']);
                                $time = $round['REG_YMDT'];

                                $userid = intval(preg_replace('/'. self::TAISHAN_PROVIDER .'(\d+)/', '$1', $round['USER_ID'])) ;
                                $shop = \App\Models\ShopUser::where('user_id', $userid)->first();

                                if ($checkduplicate)
                                {
                                    $checkGameStat = \App\Models\StatGame::where([
                                        'user_id' => $userid, 
                                        'bet' => $bet, 
                                        'win' => $win, 
                                        'date_time' => $time,
                                        'roundid' => $round['UID'] . '_' . $round['GAME_ID'],
                                    ])->first();
                                    if ($checkGameStat)
                                    {
                                        continue;
                                    }
                                }

                                \App\Models\StatGame::create([
                                    'user_id' => $userid, 
                                    'balance' => $balance, 
                                    'bet' => $bet, 
                                    'win' => $win, 
                                    'game' =>$gameName . '_tsh', 
                                    'type' => 'table',
                                    'percent' => 0, 
                                    'percent_jps' => 0, 
                                    'percent_jpg' => 0, 
                                    'profit' => 0, 
                                    'denomination' => 0, 
                                    'date_time' => $time,
                                    'shop_id' => $shop?$shop->shop_id:0,
                                    'category_id' => isset($category)?$category->id:0,
                                    'game_id' => self::TAISHAN_PROVIDER,
                                    'roundid' => $round['UID'] . '_' . $round['GAME_ID'],
                                ]);
                                $count = $count + 1;
                            }
                        }
                        $curPage = $curPage + 1;
                        if (isset($data['totalCount']))
                        {
                            $totalcount = $data['totalCount'];
                        }
                        
                    } while ($data!=null && $curPage <= $totalcount / 1000);

                    $start_timeStamp = $curend_timeStamp;

                }while ($start_timeStamp<$end_timeStamp);         
            
            return [$count, 0];
        }
    }

}
