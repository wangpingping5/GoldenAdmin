<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class PPController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */
        public static function calcHash($params)
        {
            ksort($params);
            $str_params = implode('&', array_map(
                function ($v, $k) {
                    return $k.'='.$v;
                }, 
                $params, 
                array_keys($params)
            ));
            $calc_hash = md5($str_params . config('app.ppsecretkey'));
            return $calc_hash;
        }
        public function checkreference($reference)
        {
            $record = \App\Models\PPTransaction::Where('reference',$reference)->get()->first();
            return $record;
        }
        public function generateCode($limit){
            $code = 0;
            for($i = 0; $i < $limit; $i++) { $code .= mt_rand(0, 9); }
            return $code;
        }

        public static function gameCodetoObj($code)
        {
            $gamelist = PPController::getgamelist('pp');
            $gamelist_live = PPController::getgamelist('live');
            $gamelist = array_merge_recursive($gamelist, $gamelist_live);
            $gamename = $code;
            if ($gamelist)
            {
                foreach($gamelist as $game)
                {
                    if ($game['gamecode'] == $code)
                    {
                        return $game;
                    }
                }
            }
            return null;
        }

        public static function gamecodetoname($code)
        {
            $gamelist = PPController::getgamelist('pp');
            $gamelist_live = PPController::getgamelist('live');
            $gamelist = array_merge_recursive($gamelist, $gamelist_live);
            $gamename = $code;
            $type = 'table';
            if ($gamelist)
            {
                foreach($gamelist as $game)
                {
                    if ($game['gamecode'] == $code)
                    {
                        $gamename = $game['name'];
                        $type = $game['type'];
                        break;
                    }
                }
            }
            return [$gamename,$type];
        }

        public function microtime_string()
        {
            $microstr = sprintf('%.4f', microtime(TRUE));
            $microstr = str_replace('.', '', $microstr);
            return $microstr;
        }
        public static function microtime_string_st()
        {
            $microstr = sprintf('%.4f', microtime(TRUE));
            $microstr = str_replace('.', '', $microstr);
            return $microstr;
        }
        /*
        * FROM Pragmatic Play, BACK API
        */

        public function auth(\Illuminate\Http\Request $request)
        {
            $token = $request->token;
            $providerId = $request->providerId;
            if (!$token || !$providerId)
            {
                return response()->json([
                    'error' => 7,
                    'description' => 'paramenter incorrect']);
            }


            $user = \App\Models\User::lockForUpdate()->Where('api_token',$token)->get()->first();
            if (!$user || !$user->hasRole('user')){
                return response()->json([
                    'error' => 2,
                    'description' => 'player not found']);
            }

            return response()->json([
                'userId' => $user->id,
                'currency' => 'KRW',
                'cash' => floatval($user->balance),
                'bonus' => 0,
                'error' => 0,
                'description' => 'success']);
        }

        public function balance(\Illuminate\Http\Request $request)
        {
            $userId = $request->userId;
            $providerId = $request->providerId;
            if (!$userId || !$providerId)
            {
                return response()->json([
                    'error' => 7,
                    'description' => 'paramenter incorrect']);
            }

            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                return response()->json([
                    'error' => 2,
                    'description' => 'player not found']);
            }

            return response()->json([
                'currency' => 'KRW',
                'cash' => floatval($user->balance),
                'bonus' => 0,
                'error' => 0,
                'description' => 'success']);
        }

        public function bet(\Illuminate\Http\Request $request)
        {
            $userId = $request->userId;
            $gameId = $request->gameId;
            $roundId = $request->roundId;
            $amount = $request->amount;
            $reference = $request->reference;
            $providerId = $request->providerId;
            $timestamp = $request->timestamp;
            $herestamp = $this->microtime_string();
            if (!$userId || !$gameId || !$roundId || !$amount || !$reference || !$providerId || !$timestamp || $amount < 0)
            {
                return response()->json([
                    'error' => 7,
                    'description' => 'paramenter incorrect']);
            }

            // // \DB::beginTransaction();

            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                // // ::commit();
                return response()->json([
                    'error' => 2,
                    'description' => 'player not found']);
            }

            $transaction = $this->checkreference($reference);
            if ($transaction)
            {
                return response()->json([
                    'transactionId' => strval($transaction->timestamp),
                    'currency' => 'KRW',
                    'cash' => floatval($user->balance),
                    'bonus' => 0,
                    'usedPromo' => 0,
                    'error' => 0,
                    'description' => 'success']);
            }

            if ($user->balance < $amount)
            {
                // ::commit();
                return response()->json([
                    'error' => 1,
                    'description' => 'insufficient balance']);
            }

            $user->balance = floatval(sprintf('%.4f', $user->balance - floatval($amount)));
            $user->save();

            $game = $this->gamecodetoname($gameId);

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => floatval($amount), 
                'win' => 0, 
                'game' => $game[0] . '_pp', 
                'type' => $game[1],
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id
            ]);

            $req = $request->all();
            $req['herestamp'] = $herestamp;

            $transaction = \App\Models\PPTransaction::create([
                'reference' => $reference, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($req)
            ]);

            // ::commit();

            return response()->json([
                'transactionId' => strval($transaction->timestamp),
                'currency' => 'KRW',
                'cash' => floatval($user->balance),
                'bonus' => 0,
                'usedPromo' => 0,
                'error' => 0,
                'description' => 'success']);
        }

        public function result(\Illuminate\Http\Request $request)
        {
            $userId = $request->userId;
            $gameId = $request->gameId;
            $roundId = $request->roundId;
            $amount = $request->amount;
            $reference = $request->reference;
            $providerId = $request->providerId;
            $timestamp = $request->timestamp;
            $roundDetails = $request->roundDetails;
            $promoWinAmount = $request->promoWinAmount;
            $bonusCode = $request->bonusCode;
            if (!$userId || !$gameId || !$roundId || !$amount || !$reference || !$providerId || !$timestamp || $amount < 0)
            {
                return response()->json([
                    'error' => 7,
                    'description' => 'paramenter incorrect']);
            }

            // \DB::beginTransaction();

            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                // ::commit();
                return response()->json([
                    'error' => 2,
                    'description' => 'player not found']);
            }
            $transaction = $this->checkreference($reference);
            if ($transaction)
            {
                return response()->json([
                    'transactionId' => strval($transaction->timestamp),
                    'currency' => 'KRW',
                    'cash' => floatval($user->balance),
                    'bonus' => 0,
                    'error' => 0,
                    'description' => 'success']);
            }

            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
            $game = $this->gamecodetoname($gameId);
            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => floatval($amount), 
                'game' => $game[0] . '_pp', 
                'type' => $game[1],
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id
            ]);
            if ($promoWinAmount)
            {
                $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($promoWinAmount)));
                $game = $this->gamecodetoname($gameId);
                \App\Models\StatGame::create([
                    'user_id' => $user->id, 
                    'balance' => floatval($user->balance), 
                    'bet' => 0, 
                    'win' => floatval($promoWinAmount), 
                    'game' => $game[0] . '_pp promo', 
                    'type' => $game[1],
                    'percent' => 0, 
                    'percent_jps' => 0, 
                    'percent_jpg' => 0, 
                    'profit' => 0, 
                    'denomination' => 0, 
                    'shop_id' => $user->shop_id
                ]);
            }
            $user->save();


            $transaction = \App\Models\PPTransaction::create([
                'reference' => $reference, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($request->all())
            ]);

            // ::commit();
            

            return response()->json([
                'transactionId' => strval($transaction->timestamp),
                'currency' => 'KRW',
                'cash' => floatval($user->balance),
                'bonus' => 0,
                'error' => 0,
                'description' => 'success']);
        }
        public function bonuswin(\Illuminate\Http\Request $request)
        {
            $userId = $request->userId;
            $amount = $request->amount;
            $reference = $request->reference;
            $providerId = $request->providerId;
            $timestamp = $request->timestamp;
            if (!$userId ||  !$amount || !$reference || !$providerId || !$timestamp || $amount < 0)
            {
                return response()->json([
                    'error' => 7,
                    'description' => 'paramenter incorrect']);
            }

            // \DB::beginTransaction();

            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                // ::commit();
                return response()->json([
                    'error' => 2,
                    'description' => 'player not found']);
            }

            $transaction = $this->checkreference($reference);
            if ($transaction)
            {
                return response()->json([
                    'transactionId' => strval($transaction->timestamp),
                    'currency' => 'KRW',
                    'cash' => floatval($user->balance),
                    'bonus' => 0,
                    'error' => 0,
                    'description' => 'success']);
            }


            //$user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
            //$user->save();
            $transaction = \App\Models\PPTransaction::create([
                'reference' => $reference, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($request->all())
            ]);
            // ::commit();

            return response()->json([
                'transactionId' => strval($transaction->timestamp),
                'currency' => 'KRW',
                'cash' => floatval($user->balance),
                'bonus' => 0,
                'error' => 0,
                'description' => 'success']);
        }

        public function jackpotwin(\Illuminate\Http\Request $request)
        {
            $userId = $request->userId;
            $gameId = $request->gameId;
            $roundId = $request->roundId;
            $amount = $request->amount;
            $reference = $request->reference;
            $providerId = $request->providerId;
            $timestamp = $request->timestamp;
            $jackpotId = $request->jackpotId;
            if (!$userId || !$gameId || !$roundId || !$amount || !$reference || !$providerId || !$timestamp || !$jackpotId || $amount < 0)
            {
                return response()->json([
                    'error' => 7,
                    'description' => 'paramenter incorrect']);
            }

            // \DB::beginTransaction();

            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                // ::commit();
                return response()->json([
                    'error' => 2,
                    'description' => 'player not found']);
            }

            $transaction = $this->checkreference($reference);
            if ($transaction)
            {
                return response()->json([
                    'transactionId' => strval($transaction->timestamp),
                    'currency' => 'KRW',
                    'cash' => floatval($user->balance),
                    'bonus' => 0,
                    'error' => 0,
                    'description' => 'success']);
            }

            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
            $user->save();
            $transaction = \App\Models\PPTransaction::create([
                'reference' => $reference, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($request->all())
            ]);
            $game = $this->gamecodetoname($gameId);
            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => floatval($amount), 
                'game' => $game[0] . '_pp JP', 
                'type' => $game[1],
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id
            ]);

            // ::commit();

            return response()->json([
                'transactionId' => strval($transaction->timestamp),
                'currency' => 'KRW',
                'cash' => floatval($user->balance),
                'bonus' => 0,
                'error' => 0,
                'description' => 'success']);
        }

        public function endround(\Illuminate\Http\Request $request)
        {
            $userId = $request->userId;
            $gameId = $request->gameId;
            $roundId = $request->roundId;
            $providerId = $request->providerId;
            if (!$userId || !$gameId || !$roundId || !$providerId)
            {
                return response()->json([
                    'error' => 7,
                    'description' => 'paramenter incorrect']);
            }

            return response()->json([
                'cash' => floatval($user->balance),
                'bonus' => 0,
                'error' => 0,
                'description' => 'success']);
        }

        public function refund(\Illuminate\Http\Request $request)
        {
            $userId = $request->userId;
            $reference = $request->reference;
            $providerId = $request->providerId;
            if (!$userId ||  !$reference || !$providerId )
            {
                return response()->json([
                    'error' => 7,
                    'description' => 'paramenter incorrect']);
            }

            // \DB::beginTransaction();

            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                // ::commit();
                return response()->json([
                    'error' => 2,
                    'description' => 'player not found']);
            }

            $transaction = $this->checkreference($reference);
            if (!$transaction)
            {
                // ::commit();
                return response()->json([
                    'transactionId' => strval($this->generateCode(24)),
                    'error' => 0,
                    'description' => 'reference not found']);
            } 
            if ($transaction->refund > 0)
            {
                // ::commit();
                return response()->json([
                    'transactionId' => strval('refund-' . $transaction->timestamp),
                    'error' => 0,
                    'description' => 'success']);
            }

            $data = json_decode($transaction->data, true);
            if (!isset($data['amount']))
            {
                // ::commit();
                return response()->json([
                    'error' => 7,
                    'description' => 'bad reference to refund']);
            }


            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($data['amount'])));
            $user->save();
            $transaction->refund = 1;
            $transaction->save();
            $game = $this->gamecodetoname($data['gameId']);
            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => floatval($data['amount']), 
                'game' => $game[0] . '_pp refund', 
                'type' => $game[1],
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id
            ]);
            // ::commit();



            return response()->json([
                'transactionId' => strval($transaction->timestamp),
                'error' => 0,
                'description' => 'success']);
        }

        public function promowin(\Illuminate\Http\Request $request)
        {
            $userId = $request->userId;
            $amount = $request->amount;
            $reference = $request->reference;
            $providerId = $request->providerId;
            $timestamp = $request->timestamp;
            $campaignId = $request->campaignId;
            $campaignType = $request->campaignType;
            $currency = $request->currencty;
            if (!$userId || !$campaignId || !$campaignType || !$amount || !$reference || !$providerId || !$timestamp || $amount < 0)
            {
                return response()->json([
                    'error' => 7,
                    'description' => 'paramenter incorrect']);
            }
            // \DB::beginTransaction();

            $user = \App\Models\User::lockForUpdate()->find($userId);
            if (!$user || !$user->hasRole('user')){
                // ::commit();
                return response()->json([
                    'error' => 2,
                    'description' => 'player not found']);
            }
            $transaction = $this->checkreference($reference);
            if ($transaction)
            {
                return response()->json([
                    'transactionId' => strval($transaction->timestamp),
                    'currency' => 'KRW',
                    'cash' => floatval($user->balance),
                    'bonus' => 0,
                    'error' => 0,
                    'description' => 'success']);
            }

            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
            $user->save();
            $transaction = \App\Models\PPTransaction::create([
                'reference' => $reference, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($request->all())
            ]);

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => floatval($amount), 
                'game' => 'PragmaticPlay_pp promo', 
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id
            ]);
            // ::commit();

            return response()->json([
                'transactionId' => strval($transaction->timestamp),
                'currency' => 'KRW',
                'cash' => floatval($user->balance),
                'bonus' => 0,
                'error' => 0,
                'description' => 'success']);
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
                'secureLogin' => config('app.ppsecurelogin'),
            ];
            $data['hash'] = PPController::calcHash($data);
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->get(config('app.ppapi') . '/http/CasinoGameAPI/getCasinoGames/', $data);
                if (!$response->ok())
                {
                    return [];
                }
                $data = $response->json();
                // $newgames = \App\Models\NewGame::where('provider', $href)->get()->toArray();
                if ($data['error'] == "0"){
                    $gameList = [];
                    foreach ($data['gameList'] as $game)
                    {
                        if ($game['gameTypeID'] == 'r2') //exclude fishing game
                        {
                            continue;
                        }
                        else if (str_contains($game['platform'], 'WEB'))
                        {
                            $bExclude = ($href == 'live')  ^ ($game['gameTypeID'] == 'lg');
                            if ($bExclude == false){
                                if ($game['gameID'] == 'r2lobby') //exclusive games
                                {
                                    array_push($gameList, [
                                        'provider' => 'pp',
                                        'gamecode' => $game['gameID'],
                                        'enname' => $game['gameName'],
                                        'name' => preg_replace('/\s+/', '', $game['gameName']),
                                        'title' => \Illuminate\Support\Facades\Lang::has('gameprovider.'.$game['gameName'])? __('gameprovider.'.$game['gameName']):$game['gameName'],
                                        'icon' => '/frontend/Default/ico/pp/'. $game['gameID'] . '/' . $game['gameID'] . '.png',
                                        'type' => ($game['gameTypeID']=="vs" || $game['gameTypeID']=="cs" || $game['gameTypeID']=="sc" || $game['gameTypeID']=="r2")?'slot':'table',
                                        'gameType' => $game['gameTypeID'],
                                    ]);
                                }
                                else
                                {
                                    $view = 1;
                                    if ($href == 'live' && $game['gameID'] != 101)
                                    {
                                        $view = 0;
                                    }
                                    array_push($gameList, [
                                        'provider' => 'pp',
                                        'gamecode' => $game['gameID'],
                                        'enname' => $game['gameName'],
                                        'name' => preg_replace('/\s+/', '', $game['gameName']),
                                        'title' => \Illuminate\Support\Facades\Lang::has('gameprovider.'.$game['gameName'])? __('gameprovider.'.$game['gameName']):$game['gameName'],
                                        'icon' => config('app.ppgameserver') . '/game_pic/rec/325/'. $game['gameID'] . '.png',
                                        'type' => ($game['gameTypeID']=="vs" || $game['gameTypeID']=="cs" || $game['gameTypeID']=="sc" || $game['gameTypeID']=="r2")?'slot':'table',
                                        'gameType' => $game['gameTypeID'],
                                        'view' => $view
                                    ]);
                                }
                                
                                
                            }
                        }
                    }
                    //add manually spaceman game
                    if ($href == 'pp') {
                        //vs20farmfest
                        array_unshift($gameList, [
                            'provider' => 'pp',
                            'gamecode' => 'vs20farmfest',
                            'enname' => 'Barn Festival',
                            'name' => 'BarnFestival',
                            'title' => 'BarnFestival',
                            'icon' => config('app.ppgameserver') . '/game_pic/rec/325/vs20farmfest.png',
                            'type' => 'slot',
                            'gameType' => 'r2',
                            'view' => 1
                        ]);
                        //vs20farmfest
                        array_unshift($gameList, [
                            'provider' => 'pp',
                            'gamecode' => 'vs40samurai3',
                            'enname' => 'Rise of Samurai 3',
                            'name' => 'RiseofSamurai3',
                            'title' => 'RiseofSamurai3',
                            'icon' => config('app.ppgameserver') . '/game_pic/rec/325/vs40samurai3.png',
                            'type' => 'slot',
                            'gameType' => 'r2',
                            'view' => 1
                        ]);
                    }
    
                    \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
                    return $gameList;
                }
                return [];
            }
            catch (\Exception $ex)
            {
                return [];
            }
            return [];
        }

        public static function makelink($gamecode, $userid)
        {
            $user = \App\Models\User::where('id', $userid)->first();
            
            // check transfer status
            // $tryCount = 0;
            // $resdata['status'] = 'Not found';
            // while ($tryCount < 5 && $resdata['status'] != 'success') {
            //     $data = [
            //         'secureLogin' => config('app.ppsecurelogin'),
            //         'externalTransactionId' => $userid,
            //     ];
            //     $data['hash'] = PPController::calcHash($data);
            //     $response = Http::withHeaders([
            //         'Content-Type' => 'application/x-www-form-urlencoded'
            //         ])->asForm()->post(config('app.ppapi') . '/http/CasinoGameAPI/balance/transfer/status/', $data);

            //     if ($response->ok())
            //     {
            //         $resdata = $response->json();
            //         if ($resdata == null)
            //         {
            //             $resdata['status'] = 'Not found';
            //         }
            //     }
            //     $tryCount = $tryCount + 1;
            // }
            // if ($resdata['status'] != 'success')
            // {
            //     return null;
            // }
            
            $data = PPController::getBalance($userid);
            if ($data['error'] == -1) {
                //연동오류
                $data['msg'] = 'balance';
                return null;
            }
            else if ($data['error'] == 17) //Player not found
            {
                $data = PPController::createPlayer($user->id);
                if ($data['error'] != 0) //create player failed
                {
                    //오류
                    $data['msg'] = 'createplayer';
                    return null;
                }
            }
            else if ($data['error'] == 0)
            {
                // if ($data['balance']==null)
                // {
                //     return null;
                // }
                if ($data['balance'] >= 0) //이미 밸런스가 있다면
                {
                    if ($user->playing_game == 'pp') //이전에 게임을 하고있었다면??
                    {
                        //유저의 밸런스를 업데이트
                        $user->update(['balance' => $data['balance']]);
                    }
                    else if ($data['balance'] > 0) //밸런스 초기화
                    {
                        $data = PPController::transfer($user->id, -$data['balance']);
                        if ($data['error'] != 0)
                        {
                            return null;
                        }
                    }
                }
            }
            else //알수 없는 오류
            {
                //오류
                $data['msg'] = 'balance';
                return null;
            }
            //밸런스 넘기기
            if ($user->playing_game == 'pp') //이전에 게임을 하고있었다면??,
            {
                //유저의 밸런스가 게임사 밸런스로 업데이트되었으므로, ...
            }
            else
            {
                if ($user->balance > 0) {
                    $data = PPController::transfer($user->id, $user->balance);
                    if ($data['error'] != 0)
                    {
                        return null;
                    }
                }
            }
                
            return '/providers/pp/'.$gamecode;
        }

        public static function getgamelink_pp($gamecode, $user) //at BT integration, $token is userId
        {
            $detect = new \Detection\MobileDetect();
            if (config('app.ppmode') == 'bt') // BT integration mode
            {
                $data = [
                    'secureLogin' => config('app.ppsecurelogin'),
                    'externalPlayerId' => $user->id,
                    'gameId' => $gamecode,
                    'language' => 'ko',
                    'lobbyURL' => \URL::to('/'),
                ];
                $data['hash'] = PPController::calcHash($data);
                $response = Http::withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->asForm()->post(config('app.ppapi') . '/http/CasinoGameAPI/game/start/', $data);
                if (!$response->ok())
                {
                    return ['error' => true, 'data' => '제공사응답 오류', 'original' => $response->body()];
                }
                $data = $response->json();
                if ($data['error'] == 0)
                {
                    return ['error' => false, 'data' => ['url' => $data['gameURL']]];
                }
                return ['error' => true, 'data' => '제공사응답 오류', 'original' => json_encode($data)];
            }
            else // seamless integration mode
                {
                
                $key = [
                    'token' => $user->api_token,
                    'symbol' => $gamecode,
                    'language' => 'ko',
                    'technology' => 'H5',
                    'platform' => ($detect->isMobile() || $detect->isTablet())?'MOBILE':'WEB',
                    'cashierUrl' => \URL::to('/'),
                    'lobbyUrl' => \URL::to('/'),
                ];
                $str_params = implode('&', array_map(
                    function ($v, $k) {
                        return $k.'='.$v;
                    }, 
                    $key,
                    array_keys($key)
                ));
                $url = config('app.ppgameserver') . '/gs2c/playGame.do?key='.urlencode($str_params) . '&stylename=' . config('app.ppsecurelogin');
            }
            return ['error' => false, 'data' => ['url' => $url]];
        }

        /*
            FOR BALANCE TRANSFER INTEGRATION
        */

        public function userbet(\Illuminate\Http\Request $request)
        {
            $user = \App\Models\User::lockForUpdate()->Where('id',auth()->id())->get()->first();
            if (!$user)
            {
                return response()->json([
                    'error' => '1',
                    'description' => 'unlogged']);
            }
            if ($request->name == 'notifyCloseContainer')
            {
                $data = PPController::getBalance($user->id);
                if ($user->playing_game=='pp' && $data['error'] == 0 /*&& $data['balance']!=null*/) {
                    $user->update([
                        'balance' => $data['balance'],
                        'playing_game' => null,
                        'played_at' => time()
                    ]);
                }
                else
                {
                    $user->update([
                        'playing_game' => null,
                        'played_at' => time()
                    ]);
                }
            }
            else
            {
                $user->update([
                    'playing_game' => 'pp',
                    'played_at' => time()
                ]);
            }
            return response()->json([
                'error' => '0',
                'description' => 'OK']);
        }
        public static function createPlayer($userId)
        {
            $data = [
                'secureLogin' => config('app.ppsecurelogin'),
                'externalPlayerId' => $userId,
                'currency' => 'KRW',
            ];
            $data['hash'] = PPController::calcHash($data);
            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded'
                ])->asForm()->post(config('app.ppapi') . '/http/CasinoGameAPI/player/account/create/', $data);
            if (!$response->ok())
            {
                return ['error' => '-1', 'description' => '제공사응답 오류'];
            }
            $data = $response->json();
            return $data;
        }
        public static function transfer($userId, $amount)
        {
            $transaction = \App\Models\PPTransaction::create([
                'reference' => $userId, 
                'timestamp' => PPController::microtime_string_st(),
                'data' => $amount
            ]);
            $data = [
                'secureLogin' => config('app.ppsecurelogin'),
                'externalPlayerId' => $userId,
                'externalTransactionId' => $transaction->id,
                'amount' => $amount,
            ];
            $data['hash'] = PPController::calcHash($data);
            try{
                $response = Http::withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->asForm()->post(config('app.ppapi') . '/http/CasinoGameAPI/balance/transfer/', $data);
                if (!$response->ok())
                {
                    $transaction->update(['refund' => 1]);
                    return ['error' => '-1', 'description' => '제공사응답 오류'];
                }
                $data = $response->json();
                if ($data['error'] != 0)
                {
                    $transaction->update(['refund' => 1]);
                    return ['error' => '-1', 'description' => '밸런스 전송 오류'];
                }
                return $data;
            }
            catch (\Exception $ex)
            {
                Log::error($ex->getMessage());
                $transaction->update(['refund' => 1]);
                return ['error' => -1];
            }

        }

        public static function terminate($userId)
        {
            $data = [
                'secureLogin' => config('app.ppsecurelogin'),
                'externalPlayerId' => $userId,
            ];
            $data['hash'] = PPController::calcHash($data);
            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded'
                ])->asForm()->post(config('app.ppapi') . '/http/CasinoGameAPI/game/session/terminate/', $data);
            if (!$response->ok())
            {
                return ['error' => '-1', 'description' => '제공사응답 오류'];
            }
            $data = $response->json();
            return $data;
        }

        public static function gamerounds($timepoint, $dataType='RNG')
        {
            $data = [
                'login' => config('app.ppsecurelogin'),
                'password' => config('app.ppsecretkey'),
                'dataType' => $dataType,
                'timepoint' => $timepoint,
                'options' => 'addBalance'
            ];
            $response = null;
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->get(config('app.ppapi') . '/DataFeeds/gamerounds/finished/', $data);
            } catch (\Exception $e) {
                return null;
            }
            if (!$response->ok())
            {
                return null;
            }
            $data = $response->body();
            return $data;
        }
        public static function getRoundBalance($gamestat)
        {
            $datetime = explode(' ', $gamestat->date_time);
            $data = [
                'secureLogin' => config('app.ppsecurelogin'),
                'playerId' => $gamestat->user_id,
                'datePlayed' => $datetime[0],
                'timeZone' => 'GMT+9',
                'gameId' => $gamestat->game_id,
                'hour' => explode(':',  $datetime[1])[0],
            ];
            $data['hash'] = PPController::calcHash($data);
            $response = null;
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->asForm()->post(config('app.ppapi') . '/http/HistoryAPI/GetGameRounds/', $data);
            } catch (\Exception $e) {
                return null;
            }
            if (!$response->ok())
            {
                return null;
            }
            $data = $response->json();
            if (!isset($data['rounds']))
            {
                return null;
            }
            $balance = 0;
            foreach ($data['rounds'] as $round)
            {
                if ($round['roundId'] == $gamestat->roundid)
                {
                    $balance = $round['balance'];
                }
            }
            return $balance;
        }

        public static function processGameRound($dataType)
        {
            $tpoint = \App\Models\Settings::where('key', $dataType . 'timepoint')->first();
            if ($tpoint)
            {
                $timepoint = $tpoint->value;
            }
            else
            {
                $timepoint = null;
            }

            //get category id
            $category = null;
            if ($dataType == 'RNG' || $dataType == 'R2'){
                $category = \App\Models\Category::where(['provider' => 'pp', 'shop_id' => 0, 'href' => 'pp'])->first();
            }
            else
            {
                $category = \App\Models\Category::where(['provider' => 'pp', 'shop_id' => 0, 'href' => 'live'])->first();
            }
            
            $data = PPController::gamerounds($timepoint, $dataType);
            $count = 0;
            if ($data)
            {
                $parts = explode("\n", $data);
                $updatetime = explode("=",$parts[0]);
                if (count($updatetime) > 1) {
                    $timepoint = $updatetime[1];
                    if ($tpoint)
                    {
                        $tpoint->update(['value' => $timepoint]);
                    }
                    else
                    {
                        \App\Models\Settings::create(['key' => $dataType .'timepoint', 'value' => $timepoint]);
                    }
                }
                //ignore $parts[2]
                for ($i=2;$i<count($parts);$i++)
                {
                    $round = explode(",", $parts[$i]);
                    if (count($round) < 11)
                    {
                        continue;
                    }
                    $balance = (count($round)>13)?$round[13]:(-1);
                    if ($round[7] == "C") {
                        $time = strtotime($round[6].' UTC');
                        $dateInLocal = date("Y-m-d H:i:s", $time);
                        $shop = \App\Models\ShopUser::where('user_id', $round[1])->first();
                        $gameObj =  PPController::gamecodetoname($round[2]);
                        \App\Models\StatGame::create([
                            'user_id' => $round[1], 
                            'balance' => $balance, 
                            'bet' => $round[9], 
                            'win' => $round[10], 
                            'game' =>$gameObj[0] . '_pp', 
                            'type' => $gameObj[1],//($dataType=='RNG')?'slot':'table',
                            'percent' => 0, 
                            'percent_jps' => 0, 
                            'percent_jpg' => 0, 
                            'profit' => 0, 
                            'denomination' => 0, 
                            'date_time' => $dateInLocal,
                            'shop_id' => $shop->shop_id,
                            'category_id' => isset($category)?$category->id:0,
                            'game_id' => $round[2],
                            'roundid' => $round[3],
                        ]);
                        $count = $count + 1;
                    }
                }
            }
            return [$count, $timepoint];
        }

        public static function getBalance($userId)
        {
            $data = [
                'secureLogin' => config('app.ppsecurelogin'),
                'externalPlayerId' => $userId,
            ];
            $data['hash'] = PPController::calcHash($data);
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->asForm()->post(config('app.ppapi') . '/http/CasinoGameAPI/balance/current/', $data);
                if (!$response->ok())
                {
                    return ['error' => '-1', 'description' => '제공사응답 오류'];
                }
                $data = $response->json();
                return $data;
            }
            catch (\Exception $ex)
            {
                Log::error($ex->getMessage());
                return ['error' => -1];
            }
        }

        public static function getgamelink($gamecode)
        {
            $user = auth()->user();
            $tp_cat = \App\Models\Category::where(['href'=> TPController::TP_PP_HREF, 'shop_id' => $user->shop_id])->first();
            if ($tp_cat && $tp_cat->view == 2) {
                //check if the plus support this game.
                $gameObj = TPController::getGameObjBySymbol(8, $gamecode);
                if ($gameObj)
                {
                    $data = TPController::getgamelink($gameObj['gamecode']);
                    return $data;
                }
            }
            if ($user->playing_game == 'pp') //already playing game.
            {
                PPController::terminate($user->id);
                $data = PPController::getBalance($user->id);
                if ($data['error'] == 0) {
                    $user->update([
                        'balance' => $data['balance'],
                        'playing_game' => null,
                        'played_at' => time()
                    ]);
                }
            }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', ['pp', $gamecode])]];
        }

        public static function createfrb($frbdata)
        {
            $data = [
                'secureLogin' => config('app.ppsecurelogin'),
                'currency' => 'KRW',
            ];
            $data = $data + $frbdata;
            $data['hash'] = PPController::calcHash($data);
            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded'
                ])->asForm()->post(config('app.ppapi') . '/http/FreeRoundsBonusAPI/createFRB', $data);
            if (!$response->ok())
            {
                return ['error' => '-1', 'description' => '제공사응답 오류'];
            }
            $data = $response->json();
            return $data;
        }
        public static function cancelfrb($bonusCode)
        {
            $data = [
                'secureLogin' => config('app.ppsecurelogin'),
                'bonusCode' => $bonusCode
            ];
            $data['hash'] = PPController::calcHash($data);
            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded'
                ])->asForm()->post(config('app.ppapi') . '/http/FreeRoundsBonusAPI/cancelFRB', $data);
            if (!$response->ok())
            {
                return ['error' => '-1', 'description' => '제공사응답 오류'];
            }
            $data = $response->json();
            return $data;
        }

        public static function getplayersfrb($userid)
        {
            $data = [
                'secureLogin' => config('app.ppsecurelogin'),
                'playerId' => $userid
            ];
            $data['hash'] = PPController::calcHash($data);
            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded'
                ])->asForm()->post(config('app.ppapi') . '/http/FreeRoundsBonusAPI/getPlayersFRB', $data);
            if (!$response->ok())
            {
                return ['error' => '-1', 'description' => '제공사응답 오류'];
            }
            $data = $response->json();
            return $data;
        }

        /**
         * FROM CLIENT
         */

        public function minilobby_games_json(\Illuminate\Http\Request $request)
        {
            $promo = \App\Models\PPPromo::take(1)->first();
            if ($promo){
                $data = $promo->games;
                $json_data = json_decode($data, true);
                $json_data['gameLaunchURL'] = "/gs2c/minilobby/start";
                $data = json_encode($json_data);
            }
            else{
                $data = '';
            }
            return response($data, 200)->header('Content-Type', 'application/json');
        }

        public function minilobby_start(\Illuminate\Http\Request $request)
        {
            $gamecode = $request->gameSymbol;
            $shop_id = auth()->user()->shop_id;
            $game = \App\Models\Game::where('label', $gamecode)->where('shop_id', $shop_id)->where('view',1)->first();
            if ($game)
            {
                return redirect(url('/game/' . $game->name));
            }
            else
            {
                abort(404);
            }
           
        }
        
        public function promoactive(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            $promo = \App\Models\PPPromo::take(1)->first();
            if ($promo){
                $data = $promo->active;
                $json_data = json_decode($data, true);
                $json_data['serverTime'] = time();
                if (isset($json_data['tournaments'])){
                    $tour_count = count($json_data['tournaments']);
                    for ($i = 0; $i < $tour_count; $i++ )
                    {
                        $isChoice = false;
                        if(isset($user)){
                            $promo_choice = \App\Models\PPPromoChoice::where(['user_id' => $user->id, 'promo_id' => $json_data['tournaments'][$i]['id']])->first();
                            if(isset($promo_choice)){
                                $isChoice = true;
                            }
                        }
                        $json_data['tournaments'][$i]['optin'] = $isChoice;
                    }
                }
                if (isset($json_data['races'])){
                    $race_count = count($json_data['races']);
                    for ($i = 0; $i < $race_count; $i++ )
                    {
                        $isChoice = false;
                        if(isset($user)){
                            $promo_choice = \App\Models\PPPromoChoice::where(['user_id' => $user->id, 'promo_id' => $json_data['races'][$i]['id']])->first();
                            if(isset($promo_choice)){
                                $isChoice = true;
                            }
                        }
                        $json_data['races'][$i]['optin'] = $isChoice;
                    }
                }
                $data = json_encode($json_data);
            }
            else{
                $data = '';
            }
            return response($data, 200)->header('Content-Type', 'application/json');

        }
        public function promoracedetails(\Illuminate\Http\Request $request)
        {
            $promo = \App\Models\PPPromo::take(1)->first();
            if ($promo){
                $data = $promo->racedetails;
            }
            else{
                $data = '';
            }
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public function promoraceprizes(\Illuminate\Http\Request $request)
        {
            $promo = \App\Models\PPPromo::take(1)->first();
            if ($promo){
                $data = $promo->raceprizes;
            }
            else{
                $data = '';
            }
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public function promoracewinners(\Illuminate\Http\Request $request)
        {
            $promo = \App\Models\PPPromo::take(1)->first();
            if ($promo){
                $data = $promo->racewinners;
            }
            else{
                $data = '';
            }
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public function promotournamentdetails(\Illuminate\Http\Request $request)
        {
            $promo = \App\Models\PPPromo::take(1)->first();
            if ($promo){
                $data = $promo->tournamentdetails;
            }
            else{
                $data = '';
            }
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public function promotournamentleaderboard(\Illuminate\Http\Request $request)
        {
            $promo = \App\Models\PPPromo::take(1)->first();
            if ($promo){
                $data = $promo->tournamentleaderboard;
            }
            else{
                $data = '';
            }
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public function promotournamentchoice(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            $promo = \App\Models\PPPromo::take(1)->first();
            if ($promo && $user){
                $data = $promo->active;
                $json_data = json_decode($data, true);
                $json_data['serverTime'] = time();
                if (isset($json_data['tournaments'])){
                    $tour_count = count($json_data['tournaments']);
                    for ($i = 0; $i < $tour_count; $i++ )
                    {
                        $promo_choice = \App\Models\PPPromoChoice::where(['user_id' => $user->id, 'promo_id' => $json_data['tournaments'][$i]['id']])->first();
                        if(!isset($promo_choice)){
                            \App\Models\PPPromoChoice::create(['user_id' => $user->id, 'promo_id' => $json_data['tournaments'][$i]['id']]);
                        }
                    }
                }
                $data = '{"error":0,"description":"OK"}';
            }
            else{
                $data = '';
            }
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public function promoracechoice(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            $promo = \App\Models\PPPromo::take(1)->first();
            if ($promo && $user){
                $data = $promo->active;
                $json_data = json_decode($data, true);
                if (isset($json_data['races'])){
                    $race_count = count($json_data['races']);
                    for ($i = 0; $i < $race_count; $i++ )
                    {
                        $promo_choice = \App\Models\PPPromoChoice::where(['user_id' => $user->id, 'promo_id' => $json_data['races'][$i]['id']])->first();
                        if(!isset($promo_choice)){
                            \App\Models\PPPromoChoice::create(['user_id' => $user->id, 'promo_id' => $json_data['races'][$i]['id']]);
                        }
                    }
                }
                $data = '{"error":0,"description":"OK"}';
            }
            else{
                $data = '';
            }
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public function promofrbavailable(\Illuminate\Http\Request $request)
        {
            $data = [
                "error" => 0,
                "description" => "OK"
            ];
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public function announcementsunread(\Illuminate\Http\Request $request)
        {
            $data = [
                "error" => 0,
                "description" => "OK",
                "announcements" => []
            ];
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public function promotournamentscores(\Illuminate\Http\Request $request)
        {
            $data = [
                "error" => 0,
                "description" => "OK",
                "scores" => []
            ];
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public static function syncpromo()
        {
            if (config('app.ppmode') == 'bt') // BT integration mode
            {
                $anyuser = \App\Models\User::where('role_id', 1)->whereNull('playing_game')->first();
            }
            else
            {
                $anyuser = \App\Models\User::where('role_id', 1)->whereNotNull('api_token')->first();
            }
            
            if (!$anyuser)
            {
                return ['error' => true, 'msg' => 'not found any available user.'];
            }
            $gamelist = PPController::getgamelist('pp');
            $len = count($gamelist);
            if ($len > 10) {$len = 10;}
            if ($len == 0)
            {
                return ['error' => true, 'msg' => 'not found any available game.'];
            }
            $rand = mt_rand(0,$len);
            
            $gamecode = $gamelist[$rand]['gamecode'];
            $data = PPController::createPlayer($anyuser->id);
            if ($data['error'] != 0) //create player failed
            {
                //오류
                return ['error' => true, 'msg' => 'crete player error '];
            }
            $url = PPController::getgamelink_pp($gamecode, $anyuser);
            if ($url['error'] == true)
            {
                return ['error' => true, 'msg' => 'game link error ' . $url['original']];
            }

            //emulate client
            $response = Http::withOptions(['allow_redirects' => false,'proxy' => config('app.ppproxy')])->get($url['data']['url']);
            if ($response->status() == 302)
            {
                $location = $response->header('location');
                $keys = explode('&', $location);
                $mgckey = null;
                foreach ($keys as $key){
                    if (str_contains( $key, 'mgckey='))
                    {
                        $mgckey = $key;
                        break;
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
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/promo/active/?symbol='.$gamecode.'&' . $mgckey );
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
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/promo/tournament/details/?symbol='.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->tournamentdetails = $response->body();
                }
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/promo/race/details/?symbol='.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->racedetails = $response->body();
                }
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/promo/tournament/v3/leaderboard/?symbol='.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->tournamentleaderboard = $response->body();
                }
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/promo/race/prizes/?symbol='.$gamecode.'&' . $mgckey );
                if ($response->ok())
                {
                    $promo->raceprizes = $response->body();
                }

                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->post(config('app.ppgameserver') . '/gs2c/promo/race/winners/?symbol='.$gamecode.'&' . $mgckey , ['latestIdentity' => $raceIds]);
                if ($response->ok())
                {
                    $promo->racewinners = $response->body();
                }

                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/minilobby/games?' . $mgckey );
                if ($response->ok())
                {
                    $promo->games = $response->body();
                }

                $promo->save();
                return ['error' => false, 'msg' => 'synchronized successfully.'];
            }
            else
            {
                return ['error' => true, 'msg' => 'server response is not 302.'];
            }
            
        }
        public function ppHistory(\Illuminate\Http\Request $request)
        {
            $symbol = $request->symbol;
            $usertoken = $request->token;
            
            $user = \Auth()->user();
            
            if ($usertoken=='' && $user)
            {
                $usertoken = $user->api_token;
            }
            if ($user==null)
            {
                $user = \App\Models\User::where('role_id', 1)->whereNull('playing_game')->first();
            }
            // if (!$user)
            // {
            //     return redirect()->back();
            // }
            // if ($user->hasRole('user'))
            // {
            //     $usertoken = $user->api_token;
            // }
            
            $hash = $user->generateCode(8);
            return view('frontend.Default.games.pp.history', compact('usertoken','hash', 'symbol'));
        }
        public function historymaincss($symbol, $file, $hash, \Illuminate\Http\Request $request)
        {
            $css = file_get_contents(public_path('pphistory/'. $symbol . '/' . $file.'.min.css'), true);
            $css = preg_replace('/f2e3afd2/', $hash, $css);
            return response($css, 200)->header('Content-Type', 'text/css');
        }
        public function historymainjs($symbol, $file, $hash, \Illuminate\Http\Request $request)
        {
            $js = file_get_contents(public_path('pphistory/'. $symbol . '/'. $file.'.min.js'), true);
            $js = preg_replace('/f2e3afd2/', $hash, $js);
            return response($js, 200)->header('Content-Type', 'application/javascript');
        }
        public function general(\Illuminate\Http\Request $request)
        {
            return response()->json([
                "language" => "en",
                "jurisdiction" =>"99",
                "jurisdictionRequirements" => [],
                "brandRequirements" =>[]
            ]);
        }

        public function last_items(\Illuminate\Http\Request $request)
        {
            $symbol = $request->symbol;
            $token = $request->token;
            $tokens = explode('-', $token);
            $gameid = -1;
            $user = null;
            if (count($tokens) > 1)
            {
                $userid = $tokens[0];
                $gameid = $tokens[1];
                $user = \App\Models\User::where('id', $userid)->first();
            }
            else
            {
                $user = \App\Models\User::where('api_token', $token)->first();
            }
            if (!$user )
            {
                return response()->json([ ]);
            }
            // $gamename = PPController::gamecodetoname($symbol)[0];
            // $game = TPController::getGameObjBySymbol(8, $symbol);
            // if (!$game)
            // {
            //     return response()->json([ ]);
            // }
            // $gamename = preg_replace('/[^a-zA-Z0-9 ]+/', '', $game['name']) . 'PM';
            // $gamename = preg_replace('/^(\d)([a-zA-Z0-9 ]+)/', '_$1$2', $gamename);
            $shop_id = $user->shop_id;
            $pm_games = \App\Models\Game::where([
                'shop_id' => $shop_id,
                'label' => $symbol,
                ]
            )->first();
            $data = [];
            if ($pm_games)
            {
                
                
                if ($gameid > 0)
                {
                    $stat_games = \App\Models\StatGame::where('id', $gameid)->get();
                }
                else
                {
                    $stat_games = \App\Models\StatGame::where(['user_id'=>$user->id, 'game_id'=>$pm_games->original_id])->orderby('date_time', 'desc')->take(100)->get();
                }
                
                foreach ($stat_games as $stat)
                {
                    $data[] = 
                        [
                            "roundId"=> $stat->roundid,
                            "dateTime"=> strtotime($stat->date_time) * 1000,
                            "bet"=> $stat->bet,
                            "win"=> $stat->win,
                            "balance"=> $stat->balance,
                            "roundDetails"=> null,
                            "currency"=> "KRW",
                            "currencySymbol"=> "₩",
                            "hash"=> "8df8f32a51b7fbd3ea4c8b2dd20435d6"
                        ];
                }
            }

            return response()->json($data);
        }

        public function children(\Illuminate\Http\Request $request)
        {
            $id = $request->id;
            $token = $request->token;
            $symbol = $request->symbol;
            $tokens = explode('-', $token);
            $gameid = -1;
            $user = null;
            if (count($tokens) > 1)
            {
                $userid = $tokens[0];
                $gameid = $tokens[1];
                $user = \App\Models\User::where('id', $userid)->first();
            }
            else
            {
                $user = \App\Models\User::where('api_token', $token)->first();
            }
            
            if (!$user )
            {
                return response()->json([ ]);
            }

            $logdata = \App\Models\PPGameLog::where(['roundid'=>$id, 'user_id'=>$user->id])->orderBy('time')->get();
            if (!$logdata)
            {
                return response()->json([ ]);
            }
            $data = [];
            foreach ($logdata as $log)
            {
                $data[] = json_decode($log->str);
            }

            // $data = '[{"roundId":2753568256,"request":{"symbol":"vs20hburnhs","c":"100","repeat":"0","action":"doSpin","index":"4","counter":"7","l":"20"},"response":{"mo":"0,0,0,20,20,0,0,0,0,20,0,0,0,0,0","tw":"0.00","c":"100.00","mo_t":"r,r,r,v,v,r,r,r,r,v,r,r,r,r,r","sver":"5","index":"4","balance_cash":"48,778,643.00","stime":"1634565943531","counter":"8","l":"20","reel_set":"0","sa":"10,10,1,11,8","sb":"8,11,10,10,9","balance_bonus":"0.00","na":"s","s":"10,3,9,13,13,4,4,9,10,13,8,8,9,10,1","balance":"48,778,643.00","sh":"3","w":"0.00"},"currency":"KRW","currencySymbol":"₩","configHash":"02344a56ed9f75a6ddaab07eb01abc54"}]';
            // return response($data, 200)->header('Content-Type', 'application/json');
            return response()->json($data);
            
        }
        
        public function verify($gamecode, \Illuminate\Http\Request $request){
            $failed_url = "http://404.pragmaticplay.com";
            $user = \Auth()->user();
            if (!$user)
            {
                return redirect($failed_url);
            }
            event(new \App\Events\Game\PPGameVerified($user->username . ' / ' . $gamecode));

            //마지막으로 플레이한 게임로그 얻기
            $pm_category = \App\Models\Category::where('href', 'pragmatic')->first();
            $cat_id = $pm_category->original_id;

            $stat_game = \App\Models\StatGame::where('user_id', $user->id)->where('category_id', $cat_id)->orderby('date_time', 'desc')->first();

            if ($stat_game)
            {
                $gamename = $stat_game->game_item->title;

                $pm_games = \App\Models\Game::where('id', $stat_game->game_id)->first();
                $gamelist1 = KTENController::getgamelist('kten-pp');
                foreach($gamelist1 as $game)
                {
                    // $gamename = $game['name'];
                    if ($game['gamecode'] == $pm_games->label)
                    {
                        $gamename = $game['enname'];
                        break;
                    }
                }
            
                $data = [
                    'brand_id' => config('app.stylename'),
                    'username' => $user->username,
                    'game_id' => $stat_game->game_id,
                    'balance' => $stat_game->balance,
                    'rounds' => urlencode(json_encode(
                        [
                            [
                                'id' => $stat_game->roundid,
                                'name' => $gamename,
                                'symbol' => $stat_game->game_id,
                                'date' => strtotime($stat_game->date_time) * 1000,
                                'betAmount' => $stat_game->bet,
                            ]
                        ]
                    ))
                ];
            }
            else
            {
                $data = [
                    'brand_id' => config('app.stylename'),
                    'username' => $user->username,
                    'game_id' => 0,
                    'balance' => 0,
                    'rounds' => ''
                ];
            }
            try {
                $response = Http::timeout(10)->asForm()->post(config('app.ppverify') . '/api/verify/create', $data);
                if (!$response->ok())
                {
                    return redirect($failed_url);
                }
                $res = $response->json();
                if ($res['success'])
                {
                    return redirect(config('app.ppverify') . '/verify?lang=ko&mgckey='.$res['mgckey']);
                }
                else
                {
                    return redirect($failed_url);
                }
            }
            catch (\Exception $ex)
            {
                return redirect($failed_url);
            }               

        }

        public static function verify_bet(){
            $gamecode = 'vs20doghouse';
            if (config('app.ppmode') == 'bt') // BT integration mode
            {
                $user = \App\Models\User::where('role_id', 1)->whereNull('playing_game')->first();
            }
            else
            {
                $user = \App\Models\User::where('role_id', 1)->whereNotNull('api_token')->first();
            }
            
            if (!$user)
            {
                return ['error' => true, 'msg' => 'not found any available user.'];
            }

            //마지막으로 플레이한 게임로그 얻기
            $is_local = false;
            $gamename = PPController::gamecodetoname($gamecode)[0];
            $stat_game = \App\Models\StatGame::where('user_id', $user->id)->orderby('date_time', 'desc')->first();
            if(isset($stat_game) && strpos($stat_game->game, '_pp') == false){
                $is_local = true;
            }
            $data = PPController::getBalance($user->id);
            if ($data['error'] == -1) {
                //연동오류
                return ['error' => true, 'msg' => 'getBalance Error.'];
            }
            else if ($data['error'] == 17) //Player not found
            {
                $data = PPController::createPlayer($user->id);
                if ($data['error'] != 0) //create player failed
                {
                    //오류
                    return ['error' => true, 'msg' => 'createPlayer Error.'];
                }
            }
            else if ($data['error'] == 0)
            {
                if ($data['balance'] > 0) //밸런스 초기화
                {
                    $data = PPController::transfer($user->id, -$data['balance']);//이미 밸런스가 있다면
                    if ($data['error'] != 0)
                    {
                        return ['error' => true, 'msg' => 'transfer init Error.'];
                    }
                }
            }
            else //알수 없는 오류
            {
                //오류
                return ['error' => true, 'msg' => 'unknown Error.'];
            }
            //밸런스 넘기기
            if ($user->balance > 0) {
                $data = PPController::transfer($user->id, $user->balance);
                if ($data['error'] != 0)
                {
                    return ['error' => true, 'msg' => 'transfer Error.'];
                }
            }
            
            $url = PPController::getgamelink_pp($gamecode, $user);
            if ($url['error'] == true)
            {
                return ['error' => true, 'msg' => 'getgamelink Error.'];
            }
            $response = Http::withOptions(['allow_redirects' => false, 'proxy' => config('app.ppproxy')])->get($url['data']['url']);
            if ($response->status() == 302)
            {
                $location = $response->header('location');
                $keys = explode('&', $location);
                $mgckey = null;
                foreach ($keys as $key){
                    if (str_contains( $key, 'mgckey='))
                    {
                        $mgckey = $key;
                        break;
                    }
                }
                if (!$mgckey){
                    return ['error' => true, 'msg' => 'no mgckey Error.'];
                }
                $cver = 99951;
                $response =  Http::withOptions(['proxy' => config('app.ppproxy')])->get(config('app.ppgameserver') . '/gs2c/common/games-html5/games/vs/'. $gamecode .'/desktop/bootstrap.js');
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
                $response = Http::withOptions(['proxy' => config('app.ppproxy')])->withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded'
                    ])->asForm()->post(config('app.ppgameserver') . '/gs2c/ge/v3/gameService', $data);
                if (!$response->ok())
                {
                    return ['error' => true, 'msg' => 'doInit Error'];
                }

                if($is_local == true){
                    // 본장에 베팅하기
                    $result = PPController::toJson($response->body());
                    $stat_game = \App\Models\StatGame::where('user_id', $user->id)->where('game', 'like', $gamename . 'PM%' )->orderby('date_time', 'desc')->first();
                    $line = (int)$result['l'] ?? 20;
                    $bet = (float)$result['c'] ?? 100;
                    if(isset($stat_game)){
                        $bet = $stat_game->bet / $line;
                    }
                    $data = [
                        'action' => 'doSpin',
                        'symbol' => $gamecode,
                        'c' => $bet,
                        'l' => $line,
                        'index' => 2,
                        'counter' => 3,
                        'repeat' => 0,
                        'mgckey' => explode('=', $mgckey)[1]
                    ];
                    $response = Http::withOptions(['proxy' => config('app.ppproxy')])->withHeaders([
                        'Content-Type' => 'application/x-www-form-urlencoded'
                        ])->asForm()->post(config('app.ppgameserver') . '/gs2c/ge/v3/gameService', $data);
                    if (!$response->ok())
                    {
                        return ['error' => true, 'msg' => 'doSpin Error, Body => ' . $response->body()];
                    }
                    $result_bet = PPController::toJson($response->body());
                    $win = (float)$result_bet['tw'] ?? 0;
                    $diffMoney = $bet - $win;
                    $data = PPController::transfer($user->id, $diffMoney);
                    if ($data['error'] != 0)
                    {
                        return ['error' => true, 'msg' => 'transfer diffMoney Error'];
                    }
                    else
                    {
                        return ['error' => false, 'msg' => 'Bet Body => ' . $response->body()];
                    }
                }

                return ['error' => false, 'msg' => 'No Bet'];
            }
            else
            {
                return ['error' => true, 'msg' => 'Error'];
            }
        }

        public static function toJson($data) {
            $keys = explode('&', $data);
            $response = [];
            foreach($keys as $key){
                $item_arr = explode('=', $key);
                if(is_array($item_arr) && count($item_arr) == 2){
                    $response[$item_arr[0]] = $item_arr[1];
                }
            }
            return $response;
        }

        public function savesettings($ppgame, \Illuminate\Http\Request $request)
        {
            if( !\Illuminate\Support\Facades\Auth::check() ) 
            {
                return response('SoundState=true_true_true_false_false');
            }
            $userId = auth()->user()->id;
            $object = '\App\Models\Games\\' . $ppgame . '\SlotSettings';
            if (!class_exists($object))
            {
                return response('SoundState=true_true_true_false_false');
            }
            \DB::beginTransaction();
            $slot = new $object($ppgame, $userId);

            if ($request->method == 'load') // send settings to client from server
            {
                $settings = $slot->GetGameData('settings');
                if ($settings)
                {
                    return response($settings);
                }
                else
                {
                    return response('SoundState=true_true_true_false_false');
                }
            }
            else //save settings to server from client
            {
                $slot->SetGameData('settings', $request->settings);
                $slot->SaveGameData();
                \DB::commit();
                return response($request->settings);
            }
        }
    }
}
