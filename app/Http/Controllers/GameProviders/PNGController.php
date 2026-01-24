<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    class PNGController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        public function checktransaction($id)
        {
            $record = \App\Models\PNGTransaction::Where('transactionId',$id)->first();
            return $record;
        }

        public function microtime_string()
        {
            $microstr = sprintf('%.4f', microtime(TRUE));
            $microstr = str_replace('.', '', $microstr);
            return $microstr;
        }

        public static function gameIdtoname($code)
        {
            $gamelist = PNGController::getgamelist('playngo');
            $gamename = $code;
            $gamecode = $code;
            $type = 'table';
            if ($code > 100000)
            {
                $code = $code - 100000;
            }
            if ($gamelist)
            {
                foreach($gamelist as $game)
                {
                    if ($game['gameid'] == $code)
                    {
                        $gamename = $game['name'];
                        $type = $game['type'];
                        $gamecode = $game['gamecode'];
                        break;
                    }
                }
            }
            return [$gamename,$type, $gamecode];
        }

        /*
        * FROM Play'n GO, BACK API
        */

        public function endpoint($service, \Illuminate\Http\Request $request)
        {
            if (!$request->isXml()) {
                return response()->xml(['statusCode' => 4], 200, [], $service);

            }
            // do something
            $data = $request->xml();
            if (!isset($data['accessToken']) || $data['accessToken'] != config('app.png_access_token'))
            {
                //token is not valid
                return response()->xml(['statusCode' => 4], 200, [], $service);
            }

            \DB::beginTransaction();
            if (isset($data['transactionId']) && $service != 'cancelReserve'){
                $record = $this->checktransaction($data['transactionId']);
                if ($record)
                {
                    \DB::commit();
                    return response()->xml(json_decode($record->response, true), 200, [], $service);
                }
            }

            switch ($service)
            {
                case 'authenticate':
                    $response = $this->auth($data);
                    break;
                case 'reserve':
                    $response = $this->reserve($data);
                    break;
                case 'release':
                    $response = $this->release($data);
                    break;
                case 'balance':
                    $response = $this->balance($data);
                    break;
                case 'cancelReserve':
                    $response = $this->cancelReserve($data);
                    break;
                default:
                    $response = ['status' => 4];
            }
            if (isset($data['transactionId']))
            {
                $data['service'] = $service;
                \App\Models\PNGTransaction::create([
                    'transactionId' => $data['transactionId'], 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($data),
                    'response' => json_encode($response)
                ]);
            }
            \DB::commit();
            return response()->xml($response, 200, [], $service);
        }

        public function auth($data)
        {
            $token = $data['username'];
            $response = [
                'externalId' => -1,
                'statusCode' => 0,
                'statusMessage' => 'OK',
                'userCurrency' => 'KRW',
                'nickname' => '',
                'country' => 'KR',
                'birthdate' => '1970-01-01',
                'registration' => '2010-05-05',
                'language' => 'ko_KR',
                'affiliateId' => '',
                'real' => 0,
                'externalGameSessionId' => '',
            ];

            $user = \App\Models\User::lockForUpdate()->Where('api_token',$token)->get()->first();
            if (!$user || !$user->hasRole('user')){
                return ['statusCode' => 4,'statusMessage' => 'Wrong username or password'];
            }

            $response['externalId'] = $user->id;
            $response['nickname'] = $user->username;
            $response['registration'] = $user->created_at;
            $response['real'] = floatval($user->balance);
            return $response;
        }

        public function reserve($data)
        {
            $userId = $data['externalId'];
            $transactionId = $data['transactionId'];
            $bet = $data['real'];
            $gameId = $data['gameId'];

            $response = [
                'externalTransactionId' => $transactionId,
                'real' => 0,
                'statusCode' => 0,
                'statusMessage' => 'OK',
            ];


            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                $response['statusCode'] = 1;
                $response['statusMessage'] = 'NOUSER';
                return $response;
            }

            if ($user->balance < $bet)
            {
                $response['real'] = $user->balance;
                $response['statusCode'] = 7;
                $response['statusMessage'] = 'NOTENOUGHMONEY';
                return $response;
            }
            
            $user->balance = floatval(sprintf('%.4f', $user->balance - floatval($bet)));
            $user->save();

            $response['real'] = $user->balance;

            $game = $this->gameIdtoname($gameId);
            $category = \App\Models\Category::where(['provider' => 'png', 'shop_id' => 0, 'href' => 'playngo'])->first();
            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => floatval($bet), 
                'win' => 0, 
                'game' => $game[0] . '_png', 
                'type' => $game[1],
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => $game[2],
                'roundid' => 0,
            ]);

            return $response;
        }

        public function release($data)
        {
            $userId = $data['externalId'];
            $transactionId = $data['transactionId'];
            $win = $data['real'];
            $type = $data['type'];
            $gameId = $data['gameId'];

            $response = [
                'externalTransactionId' => $transactionId,
                'real' => 0,
                'statusCode' => 0,
            ];

            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                $response['statusCode'] = 1;
                return $response;
            }
            if ($win >0 || $type==0){

                $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($win)));
                $user->save();
                $game = $this->gameIdtoname($gameId);
                
                $category = \App\Models\Category::where(['provider' => 'png', 'shop_id' => 0, 'href' => 'playngo'])->first();

                \App\Models\StatGame::create([
                    'user_id' => $user->id, 
                    'balance' => floatval($user->balance), 
                    'bet' => 0, 
                    'win' => floatval($win), 
                    'game' => $game[0] . '_png' . ($type==0?'':' FG'), 
                    'type' => $game[1],
                    'percent' => 0, 
                    'percent_jps' => 0, 
                    'percent_jpg' => 0, 
                    'profit' => 0, 
                    'denomination' => 0, 
                    'shop_id' => $user->shop_id,
                    'category_id' => isset($category)?$category->id:0,
                    'game_id' => $game[2],
                    'roundid' => 0,
                ]);
            }
            $response['real'] = $user->balance;

            return $response;

        }

        public function balance($data)
        {
            $userId = $data['externalId'];
            $response = [
                'real' => 0,
                'statusCode' => 0,
            ];

            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                $response['statusCode'] = 1;
                return $response;
            }

            $response['real'] = floatval($user->balance);
            return $response;

        }

        public function cancelReserve($data)
        {
            $userId = $data['externalId'];
            $transactionId = $data['transactionId'];
            $bet = $data['real'];
            $gameId = $data['gameId'];

            $response = [
                'externalTransactionId' => $transactionId,
                'statusCode' => 0,
            ];

            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                $response['statusCode'] = 1;
                return $response;
            }

            $transaction = $this->checktransaction($transactionId);
            if (!$transaction)
            {
                //$response['statusCode'] = 2; //internal server error
                return $response;
            }

            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($bet)));
            $user->save();
            $game = $this->gameIdtoname($gameId);

            $category = \App\Models\Category::where(['provider' => 'png', 'shop_id' => 0, 'href' => 'playngo'])->first();

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => floatval($bet), 
                'game' => $game[0] . '_png refund', 
                'type' => $game[1],                
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => $game[2],
                'roundid' => 0,
            ]);

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
            $newgames = \App\Models\NewGame::where('provider', 'png')->get()->pluck('gameid')->toArray();
            $query = 'SELECT * FROM w_provider_games WHERE view=1 AND provider="png"';
            $png_games = \DB::select($query);
            foreach ($png_games as $game)
            {
                $icon_name = str_replace(' ', '_', $game->name);
                $icon_name = strtolower(preg_replace('/\s+/', '', $icon_name));
                if (in_array($game->gamecode , $newgames))
                {
                    array_unshift($gameList, [
                        'provider' => 'png',
                        'gameid' => $game->gameid,
                        'gamecode' => $game->gamecode,
                        'enname' => $game->name,
                        'name' => preg_replace('/\s+/', '', $game->name),
                        'title' => $game->title,
                        'type' => $game->type,
                        'icon' => '/frontend/Default/ico/png/'. $icon_name . '.jpg',
                    ]);
                }
                else
                {
                    array_push($gameList, [
                        'provider' => 'png',
                        'gameid' => $game->gameid,
                        'gamecode' => $game->gamecode,
                        'enname' => $game->name,
                        'name' => preg_replace('/\s+/', '', $game->name),
                        'title' => $game->title,
                        'type' => $game->type,
                        'icon' => '/frontend/Default/ico/png/'. $icon_name . '.jpg',
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
                return null;
            }
            $channel = ($detect->isMobile() || $detect->isTablet())?'mobile':'desktop';
             $url = config('app.png_root_url') . '/casino/ContainerLauncher?pid='.config('app.png_pid').'&gid='.$gamecode.'&channel='.$channel.'&lang=ko_KR&practice=0&ticket='.$user->api_token.'&origin=' . url('/');
            return $url;
        }

        public static function getgamelink($gamecode)
        {
            $url = PNGController::makegamelink($gamecode);
            if ($url)
            {
                return ['error' => false, 'data' => ['url' => $url]];
            }
            return ['error' => true, 'msg' => '로그인하세요'];
            
        }

    }

}
