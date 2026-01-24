<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class HPCController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */
        const HPC_PROVIDER = 'hpc';

        public function microtime_string()
        {
            $microstr = time();
            return $microstr;
        }

        public static function getGameObj($tableId)
        {
            $gamelist = HPCController::getgamelist(self::HPC_PROVIDER);
            $tableName = preg_replace('/\s+/', '', $tableId);
            if ($gamelist)
            {
                foreach($gamelist as $game)
                {
                    if ($game['gamecode'] == $tableName)
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

        public function userSignal(\Illuminate\Http\Request $request)
        {
            $user = \App\Models\User::lockForUpdate()->where('id',auth()->id())->first();
            if (!$user)
            {
                return response()->json([
                    'error' => '1',
                    'description' => 'unlogged']);
            }
            if ($user->playing_game != self::HPC_PROVIDER)
            {
                return response()->json([
                    'error' => '1',
                    'description' => 'Idle TimeOut']);
            }

            if ($request->name == 'exitGame')
            {
                Log::channel('monitor_game')->info(self::HPC_PROVIDER . ' : ' . $user->id . ' is exiting game');
                $user->update([
                    'playing_game' => self::HPC_PROVIDER . 'exit',
                    'played_at' => time()
                ]);
            }
            else
            {
                $balance = HPCController::getUserBalance($user);
                if ($balance >= 0)
                {
                    $user->update([
                        'balance' => $balance,
                        'played_at' => time()
                    ]);
                }
            }
            return response()->json([
                'error' => '0',
                'description' => 'OK']);
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
            $headers = HPCController::getApiHeaderInfo(null);
            $url = config('app.hpc_api') . '/games/get';
            $params = [
                'language' => 'kr'
            ];
            $hpc_games = [];
            try {
                $response = Http::withHeaders($headers)->post($url, $params);
                if (!$response->ok())
                {
                    return [];
                }
                $data = $response->json();
                if ($data['status'] != 1)
                {
                    return [];
                }
                $hpc_games = $data['game_list'];
            } catch (\Exception $e) {
                Log::error('HPC : getgamelist request failed. ' . $e->getMessage());
                return [];
            }
            foreach ($hpc_games as $game)
            {
                array_push($gameList, [
                    'provider' => 'hpc',
                    'gamecode' => $game['tableid'],
                    'name' => preg_replace('/\s+/', '', $game['name']),
                    'title' => $game['name'],
                    'type' => 'live',
                    'href' => $href,
                    'view' => 0,
                    ]);
            }
            array_push($gameList, [
                'provider' => 'hpc',
                'gamecode' => 'hpclobby',
                'name' => 'HPCLobby',
                'title' => '에볼루션 로비',
                'type' => 'live',
                'icon' => '/frontend/Default/ico/hpc/hpclobby.jpg',
                'href' => $href,
                'view' => 1,
                ]);
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
            
        }

        public static function getApiHeaderInfo($user)
        {
            $recommend = config('app.hpc_key');
            if ($user != null) {
                $master = $user;
                while ($master!=null && !$master->isInoutPartner())
                {
                    $master = $master->referral;
                }
                if ($master == null)
                {
                    return [];
                }
                $query = 'SELECT * FROM w_provider_info WHERE provider="hpc" and user_id=' . $master->id;
                $hpc_info = \DB::select($query);
                foreach ($hpc_info as $info)
                {
                    $recommend = $info->config;
                }
            }
            $hpc_params = explode(':', $recommend);

            $headers = [
                'ag-code' => $hpc_params[0],
                'ag-token' => $hpc_params[1]
            ];
            return $headers;
        }

        public static function getUserBalance($user)
        {
            //get user balance
            $params = [
                'user_id' => self::HPC_PROVIDER . $user->id,
            ];
            $headers = HPCController::getApiHeaderInfo($user);

            $url = config('app.hpc_api') . '/customer/get';
            $response = Http::withHeaders($headers)->post($url, $params);
            if (!$response->ok())
            {
                Log::error('HPC : get user info request failed. ' . $response->body());
                return -1;
            }
            $data = $response->json();
            if ($data==null || $data['status']!=1)
            {
                return -1;
            }
            return $data['balance'];
        }

        public static function withdrawAll($user)
        {
            $balance = HPCController::getUserBalance($user);

            if ($balance <= 0)
            {
                return ['error'=>false, 'amount'=>0];
            }
            //sub balance
            $params = [
                'user_id' => self::HPC_PROVIDER . $user->id,
                'balance' => $balance
            ];
            $headers = HPCController::getApiHeaderInfo($user);
            $url = config('app.hpc_api') . '/customer/sub_balance';
            $response = Http::withHeaders($headers)->post($url, $params);
            if (!$response->ok())
            {
                Log::error('HPC : sub balance request failed. ' . $response->body());
                return ['error'=>true, 'amount'=>-1, 'msg' => $response->body()];
            }
            $data = $response->json();
            if ($data==null || $data['status']!=1)
            {
                return ['error'=>true, 'amount'=>-1, 'msg' => $response->body()];
            }
            return ['error'=>false, 'amount'=>$data['amount']];
        }

        public static function makelink($gamecode, $userid)
        {
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('HPC : Does not find user ' . $userid);
                return null;
            }
            //create hpc account
            
            $params = [
                'id' => self::HPC_PROVIDER . $user->id,
                'name' => $user->username,
                'balance' => 0,
                'language' => 'kr'
            ];
            $headers = HPCController::getApiHeaderInfo($user);

            $url = config('app.hpc_api') . '/customer/user_create';

            $response = Http::withHeaders($headers)->post($url, $params);
            if (!$response->ok())
            {
                Log::error('HPC : Create account request failed. ' . $response->body());
                return null;
            }
            $data = $response->json();
            if ($data==null || ($data['status']==0 && $data['error']!='DOUBLE_USER')) //create success or already exist
            {
                Log::error('HPC : Create account result failed. ' . ($data==null?'null':$data['error']));
                return null;
            }

            if ($data['status']==0) { //already user
                //withdraw all balance
                $data = HPCController::withdrawAll($user);
                if ($data['error'])
                {
                    return null;
                }
            }
            

            //Add balance
            if ($user->balance > 0) {
                $params = [
                    'user_id' => self::HPC_PROVIDER . $user->id,
                    'balance' => intval($user->balance)
                ];
                $url = config('app.hpc_api') . '/customer/add_balance';
                $response = Http::withHeaders($headers)->post($url, $params);
                if (!$response->ok())
                {
                    Log::error('HPC : add balance request failed. ' . $response->body());
                    return null;
                }
                $data = $response->json();
                if ($data==null || $data['status']!=1)
                {
                    return null;
                }
            }

            return '/followgame/hpc/'.$gamecode;

        }

        public static function makegamelink($gamecode)
        {
            $user = auth()->user();
            if ($user == null)
            {
                return null;
            }
            $is_test = str_contains($user->username, 'testfor');
            if ($is_test) //it must be hidden from all providers
            {
                return null;
            }
            $params = [
                'user' => [
                    'id' => self::HPC_PROVIDER . $user->id,
                    'name' => $user->username,
                    'balance' => intval($user->balance),
                    'language' => 'kr'
                ]
            ];
            $headers = HPCController::getApiHeaderInfo($user);
            
            $url = config('app.hpc_api') . '/account/login';
            $launchurl = null;
            try {
                $response = Http::withHeaders($headers)->post($url, $params);
                if (!$response->ok())
                {
                    return null;
                }
                $data = $response->json();
                if ($data && $data['status'] == 1){
                    $launchurl = $data['launch_url'];
                }
            }
            catch (\Exception $ex)
            {
                Log::error('HPC : Auth request failed. ' . $e->getMessage());
                return null;
            }
            return $launchurl;
        }

        public static function getgamelink($gamecode)
        {
            $user = auth()->user();
            if ($user->playing_game != null) //already playing game.
            {
                return ['error' => true, 'data' => '이미 실행중인 게임을 종료해주세요. 이미 종료했음에도 불구하고 이 메시지가 계속 나타난다면 매장에 문의해주세요.'];
            }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [self::HPC_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($master, $timepoint)
        {
            $pageSize = 1000;
            $params = [
                'txn_no' => $timepoint,
                'page' => 0,
                'perPage' => $pageSize                
            ];
            $headers = HPCController::getApiHeaderInfo($master);
            
            $url = config('app.hpc_api') . '/agent/transaction_with_no';
            $rounds = null;
            try {
                $response = Http::withHeaders($headers)->post($url, $params);
                if (!$response->ok())
                {
                    return null;
                }
                $data = $response->json();
                if ($data && $data['status'] == 1){
                    $rounds = $data;
                }
            }
            catch (\Exception $e)
            {
                Log::error('HPC : transaction_with_no request failed. ' . $e->getMessage());
                return null;
            }
            return $rounds;
        }

        public static function processGameRound($master=null)
        {
            $idx = 0;
            if ($master != null)
            {
                $idx = $master->id;
            }
            $tpoint = \App\Models\Settings::where('key', 'HPCtimepoint' . $idx)->first();
            if ($tpoint)
            {
                $timepoint = $tpoint->value;
            }
            else
            {
                $timepoint = 0;
            }

            //get category id
            $category = \App\Models\Category::where(['provider' => self::HPC_PROVIDER, 'shop_id' => 0, 'href' => self::HPC_PROVIDER])->first();
            
            $data = HPCController::gamerounds($master, $timepoint);
            $count = 0;
            if ($data && $data['status'] == 1 && $data['total_count'] > 0)
            {
                $betrounds = array_values(array_filter($data['data'] , function($v) {
                    return $v['type'] == 0;
                }));
    
                $winrounds = array_values(array_filter($data['data'] , function($v) {
                    return $v['type'] == 1;
                }));

                foreach ($betrounds as $round)
                {
                    $winIdx = array_search($round['txn_id'], array_column($winrounds,'txn_id'));
                    $betAmount = $round['amount'];
                    $winAmount = 0;
                    if ($winIdx !== false)
                    {
                        $winAmount = $winrounds[$winIdx]['amount'];
                    }


                    $balance = -1;
                    $time = strtotime($round['created_at'].' UTC');
                    $dateInLocal = date("Y-m-d H:i:s", $time);
                    $userid = preg_replace('/'. self::HPC_PROVIDER .'(\d+)/', '$1', $round['site_user']) ;
                    $shop = \App\Models\ShopUser::where('user_id', $userid)->first();
                    $gameObj =  HPCController::getGameObj($round['table_id']);
                    if (!$gameObj)
                    {
                        //try again.
                        \Illuminate\Support\Facades\Redis::del(self::HPC_PROVIDER . 'list');
                        $gameObj =  HPCController::getGameObj($round['table_id']);
                        if (!$gameObj)
                        {
                            $gameObj= [
                                'name'=>'Unknown'
                            ];
                        }
                    }
                    \App\Models\StatGame::create([
                        'user_id' => $userid, 
                        'balance' => $balance, 
                        'bet' => $betAmount, 
                        'win' => $winAmount, 
                        'game' =>$gameObj['name'] . '_hpc', 
                        'type' => 'table',
                        'percent' => 0, 
                        'percent_jps' => 0, 
                        'percent_jpg' => 0, 
                        'profit' => 0, 
                        'denomination' => 0, 
                        'date_time' => $dateInLocal,
                        'shop_id' => isset($shop)?$shop->shop_id:0,
                        'category_id' => isset($category)?$category->id:0,
                        'game_id' =>$round['table_id'],
                        'roundid' => $round['txn_id'],
                    ]);
                    if ($round['txn_no'] > $timepoint)
                    {
                        $timepoint = $round['txn_no'];
                    }
                    $count = $count + 1;
                }

                $timepoint = $timepoint+1;
                if ($tpoint)
                {
                    $tpoint->update(['value' => $timepoint]);
                    $tpoint->save();
                }
                else
                {
                    \App\Models\Settings::create(['key' => 'HPCtimepoint' . $idx, 'value' => $timepoint]);
                }
            }
            return [$count, $timepoint];
        }

    }

}
