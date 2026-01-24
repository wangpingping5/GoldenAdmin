<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    use Carbon\Carbon;
    class HOLDEMController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const HOLDEM_GAME_IDENTITY = [
            //==== holdem ====
            'holdem-card' => ['thirdname' =>'WildGames','type' => 'card']
        ];
        const HOLDEM_PROVIDER = 'holdem';

        public static function getGameObj($uuid='')
        {
            foreach (HOLDEMController::HOLDEM_GAME_IDENTITY as $ref => $value)
            {
                $gamelist = HOLDEMController::getgamelist($ref);
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
        
        //게임 목록
        public static function getgamelist($href='')
        {
            $gameList = \Illuminate\Support\Facades\Redis::get($href.'list');
            if ($gameList)
            {
                $games = json_decode($gameList, true);
                if ($games!=null && count($games) > 0){
                    return $games;
                }
            }
            $category = HOLDEMController::HOLDEM_GAME_IDENTITY[$href];
            $type = $category['type'];
            
            $url = config('app.holdem_api') . '/getGames';
            $op = config('app.holdem_opcode');

            $providerCode = "0101";
            $date = Carbon::now();            
            $data = [
                'providerCode' => $providerCode,
                'date' => $date
            ];
            $data = json_encode($data,true);
            $baseData = base64_encode($data);
            $gameHash = HOLDEMController::generateHash($data);

            //test
            // $op = 'testopcode';

            $params = [
                'opCode' => $op,
                'hash' => $gameHash,
                'data' => $baseData,
            ];
            $gameList = [];
            try {       
                $response = Http::get($url, $params);
                
                if ($response->ok()) {
                    $data = $response->json();
                    foreach ($data['gameList'] as $game)
                    {
                        if (strtolower($game['gameSort']) == $type) 
                        {
                            $view = 1;
                            array_push($gameList, [
                                'provider' => self::HOLDEM_PROVIDER,
                                'href' => $href,
                                'gamecode' => $game['gameCode'],
                                'name' => $game['gameName'],
                                'title' => $game['gameKoreanName'],
                                'icon' => $game['gameIconUrl'],
                                'type' => $type,
                                'view' => $view
                            ]);
                        }
                    }
                    \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
                }
                else
                {
                    Log::error('Holdem Gamelist Request : response is not okay. ' . json_encode($params) . '===body==' . $response->body());
                    return [];
                }
            }
            catch (\Exception $ex)
            {
                Log::error('Holdem Gamelist Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Holdem Gamelist Request :  Excpetion. PARAMS= ' . json_encode($params));
            }
            return $gameList;
        }       

        //유저 생성/확인
        public static function createPlayer($userId){
            $date = Carbon::now();
            $user = \App\Models\User::where(['id'=> $userId])->first();
            $username = $user->username;
            $password = $user->password;
            $op = config('app.holdem_opcode');

            $prefix = HOLDEMController::HOLDEM_PROVIDER;
            $data = [
                'userId' => $prefix . sprintf("%04d",$user->id),
                'nickname' => $user->username,                
                'date' => $date
            ];
            $data = json_encode($data,true);
            $baseData = base64_encode($data);
            $createplayerHash = HOLDEMController::generateHash($data);
            $url = config('app.holdem_api') . '/player/account/create';
            
            $params = [
                'opCode' => $op,
                'hash' => $createplayerHash,
                'data' => $baseData,
            ];
            try{
                $response = Http::get($url, $params);
                if ($response->ok())
                {
                    $data = $response->json();
                    if($data['error'] == 0){
                        return $data['playerId'];
                    }else{
                        return -1;
                    }
                }else{
                    return -1;
                }                
            }
            catch (\Exception $ex)
            {
                Log::error('Holdem CreateUser Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Holdem CreateUser Request :  Excpetion. PARAMS= ' . json_encode($params));
            }
            return -1;
        }

        public static function transferMoney($userId,$transferMethod){
            $date = Carbon::now();
            $user = \App\Models\User::where(['id'=> $userId])->first();
            $safeAmount = 0;
            if(isset($user->sessiondata()['safeAmount']) && $user->sessiondata()['safeAmount'] != null){
                $safeAmount = $user->sessiondata()['safeAmount'];
            }
            $username = $user->username;
            $op = config('app.holdem_opcode');  

            $prefix = HOLDEMController::HOLDEM_PROVIDER;
            $data = [
                'userId' => strtoupper($prefix . sprintf("%04d",$user->id)),
                'referenceId' => $user->generateCode(24),  //랜덤 문자열
                'walletType' => 'board',
                'amount' => $transferMethod . ($user->balance - $safeAmount), // 다시 확인
                'safeAmount' => $safeAmount,          
                'date' => $date
            ];
            
            $data = json_encode($data,JSON_UNESCAPED_UNICODE);
            $transfermoneyHash = HOLDEMController::generateHash($data);
            $baseData = base64_encode($data);
            $url = config('app.holdem_api') . '/player/balance/transfer';
            
            $params = [
                'opCode' => $op,
                'hash' => $transfermoneyHash,
                'data' => $baseData,
            ];
            try{
                $response = Http::get($url, $params);
                // if (!$response->ok())
                // {
                //     Log::error('Holdem TransferMoney Request :  Failed ');
                //     return null;
                // }
                $data = $response->json();
                if($data['error'] == 0){
                    $responseValue = [
                        'transactionId' => $data['transactionId'],
                        'balance' => $data['balance'],
                        'safeBalance' => $data['safeBalance']
                    ];
                    $responseValue = json_encode($responseValue);
                    return $responseValue;
                }else{
                    return null;
                }
            }catch(\Exception $ex){
                Log::error('Holdem TransferMoney Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Holdem TransferMoney Request :  Excpetion. PARAMS= ' . json_encode($params));
            }
            
            return null;
        }

        public static function getUserBalance($href, $user){
            $date = Carbon::now();
            $user = \App\Models\User::where(['id'=> $user->id])->first();
            $username = $user->username;
            $op = config('app.holdem_opcode');
            $prefix = HOLDEMController::HOLDEM_PROVIDER;
            $data = [
                'userId' => $prefix . sprintf("%04d",$user->id),        
                'date' => $date
            ];
            $data = json_encode($data,true);
            $confirmmoneyHash = HOLDEMController::generateHash($data);
            $baseData = base64_encode($data);
            $url = config('app.holdem_api') . '/player/balance/current';
            
            $params = [
                'opCode' => $op,
                'hash' => $confirmmoneyHash,
                'data' => $baseData,
            ];
            try{
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('Holdem ConfirmMoney Request :  Failed ');
                    return -1;
                }
                $data = $response->json();
                if($data['error'] == 0){
                    $balance = $data['balanceList'][0]['balance'] + $data['balanceList'][0]['safeBalance'];
                    $user->sessiondata()['safeAmount'] = $data['balanceList'][0]['safeBalance'];
                    $user->save();
                    return $balance;
                }
            }catch(\Exception $ex){
                Log::error('Holdem ConfirmMoney Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Holdem ConfirmMoney Request :  Excpetion. PARAMS= ' . json_encode($params));
            }
            
            return -1;
        }


        public static function withdrawAll($href, $user)
        {
            $balance = HOLDEMController::getUserBalance($href,$user);
            if ($balance < 0)
            {
                return ['error'=>true, 'amount'=>$balance, 'msg'=>'getuserbalance return -1'];
            }
            if ($balance > 0)
            {
                $moneyTrans = HoldemController::transferMoney($user->id, '-');
                if ($moneyTrans==null)
                {
                    return ['error'=>true, 'amount'=>0, 'msg'=>'data not ok'];
                }
            }
            return ['error'=>false, 'amount'=>$balance];
        }
        public static function generateHash($data){
            //$opKey = 'f3fff4d3-cbf6-46ef-b710-eea686fad487';
            $opKey = config('app.holdem_opkey');
            $baseData = base64_encode($data);
            $hashData = md5($baseData . $opKey);
            return $hashData;
        }
        public static function getgamelink($gamecode)
        {
            $gamelist = HOLDEMController::getgamelist('card');
            if (count($gamelist) > 0)
            {
                foreach ($gamelist as $g)
                {
                    if ($g['view'] == 1 && $g['gamecode'] == $gamecode)
                    {
                        break;
                    }
                }
                
            }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [HOLDEMController::HOLDEM_PROVIDER, $gamecode])]];
        }

        public static function makelink($gamecode,$userid){
            //Check User 
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('HoldemMakeLink : Does not find user ' . $userid);
                return null;
            }

            $game = HOLDEMController::getGameObj($gamecode);
            if ($game == null)
            {
                Log::error('GOLDMakeLink : Game not find  ' . $gamecode);
                return null;
            }
            $balance = HOLDEMController::getUserBalance($game['href'], $user);
            if($balance < 0){
                //Create User
                $alreadyUser = HoldemController::createPlayer($userid);
                if($alreadyUser == -1){
                    Log::error('HoldemCreateUser : Does not find user ');
                    return null;
                }
    
            }
            if ($balance != $user->balance)
            {
                //withdraw all balance
                $data = HOLDEMController::withdrawAll($game['href'], $user);
                if ($data['error'])
                {
                    return null;
                }
                //Add balance

                if ($user->balance > 0)
                {
                    $moneyTrans = HoldemController::transferMoney($userid,'+');    
                    if($moneyTrans == null){
                        Log::error('HoldemTransferMoney : Can not Transfer money ' . $userid);
                        return null;
                    }    
                }
            }
            
            

            return '/followgame/holdem/'.$gamecode;
        }

        public static function makegamelink($gamecode, $user){
            
            $username = $user->username;
            $parse = explode('#', $user->username);
            if (count($parse) > 1)
            {
                $username = $parse[count($parse) - 1];
            }
            $date = Carbon::now();
            $providerCode = '0101';
            $op = config('app.holdem_opcode');
            //////////////////////////////////
            $prefix = HOLDEMController::HOLDEM_PROVIDER;
            $platform = 'WEB';
            $detect = new \Detection\MobileDetect();
            if( $detect->isMobile() || $detect->isTablet() ) 
            {
                $platform = 'Mobile';
            }
            $data = [
                'userId' => $prefix . sprintf("%04d",$user->id),
                'providerCode' => $providerCode,
                'gameCode' => $gamecode,   
                'platform' => $platform, 
                'date' => $date
            ];
            $data = json_encode($data,true);
            $baseData = base64_encode($data);
            $gameUrlHash = HOLDEMController::generateHash($data);
            

            $params = [
                'opCode' => $op,
                'hash' => $gameUrlHash,
                'data' => $baseData
            ];

            $url = config('app.holdem_api') . '/gameStart';
            try
            {
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('Holdem MakeGameLink Request Response Failed ');
                    return null;
                }
                $data = $response->json();
                $gameUrl = $data['gameUrl'];
                $token = $data['token'];
                $userBalance = $user->balance;
                // $record = \App\Models\HoldemTransaction::create(['error_code' => $data['error'],'gamename' => $gamecode,'amount' => 0,'balance' => $userBalance,'gamecode' => $gamecode, 'user_id' => $username,'data' => json_encode($data),'token'=>$token,'stats'=>0]);
                
                return $gameUrl;
            }catch(\Exception $ex){
                Log::error('Holdem MakeGameLink Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Holdem MakeGameLink Request :  Excpetion. PARAMS= ' . json_encode($params));
            }
            return null;
        }


         //콜백
        //인증
        public static function authentication(\Illuminate\Http\Request $request){
            // $providerCode = '0101';
            // $data = json_decode($request->getContent(), true);
            // $hashData = $data['hash'];
            // $opKey = config('app.holdem_opcode');
            // $authData = $data['data'];
            // $authData = json_encode(base64_decode($authData),true);
            // $token = $authData['token'];
            // $gameCode = $authData['gameCode'];
            // $user = \App\Models\User::where(['username'=> $data['userId']])->first();
            // $prevRecord = \App\Models\HoldemTransaction::where(['gamecode' => $gameCode,'token'=>$token,'stats'=>0])->first();
            // if(!isset($prevRecord)){
            //     return response(HOLDEMController::toText([
            //         'error' => 3,
            //         'description' => 'Invalid  token'
            //     ]))->header('Content-Type', 'text/plain');
            // }
            // $safeValue = 0;
            // $safeUse = false;
            // if(!isset($user->sessiondata()['safeAmount'])){
            //     return response(HOLDEMController::toText([
            //         'error' => 0,
            //         'balance' => $user->balance,
            //         'safeBalance' => 0,
            //         'safeUse' => false
            //     ]))->header('Content-Type', 'text/plain');
            // }else{
            //     $safeValue = $user->sessiondata()['safeAmount'];
            //     $safeUse = $user->sessiondata()['safeUse'];
            // }
            // return response(HOLDEMController::toText([
            //     'error' => 0,
            //     'balance' => $user->balance,
            //     'safeBalance' => $safeValue,
            //     'safeUse' => $safeUse
            // ]))->header('Content-Type', 'text/plain');
        }

        public static function gameEvent(\Illuminate\Http\Request $request){
            // $data = json_decode($request->getContent(), true);
            // $authData = $data['data'];
            // $authData = json_encode(base64_decode($authData),true);

            // $token = $authData['token'];
            // $gameCode = $authData['gameCode'];
            // $eventType = $authData['eventType'];
            // $amountMoney = $authData['amount'];
            // if(isset($authData['options'])){
            //     $options = $authData['options'];
            // }
            // $transactionId = $authData['transactionId'];
            // $transactionDate = $authData['transactionDate'];
            // $user = \App\Models\User::where(['username'=> $authData['userId']])->first();
            // if(!isset($user)){
            //     return response(HOLDEMController::toText([
            //         'error' => 10,
            //         'description' => 'The user was not found'
            //     ]))->header('Content-Type', 'text/plain');
            // }

            // $prevRecord = \App\Models\HoldemTransaction::where(['user_id'=>$user->username,'gamecode' => $gameCode,'token'=>$token,'stats'=>0])->first();
            // if(!isset($prevRecord)){
            //     return response(HOLDEMController::toText([
            //         'error' => 3,
            //         'description' => 'Invalid  token'
            //     ]))->header('Content-Type', 'text/plain');
            // }            

            // $preEventRecord = \App\Models\HoldemTransaction::where(['user_id'=>$user->username,'gamecode' => $gameCode,'transactionId'=>$transactionId,'stats'=>0])->first();
            // if(!isset($preEventRecord)){
            //     \App\Models\HoldemTransaction::create(['gamecode'=>$gameCode,'eventtype'=>$eventType,'eventmoney'=>$amountMoney,'transactionId'=>$transactionId,'data'=>$authData]);
            // }else{
            //     $preEventRecord->update(['gamecode'=>$gameCode,'eventtype'=>$eventType,'eventmoney'=>$amountMoney,'transactionId'=>$transactionId,'data'=>$authData]);
            // }
            // return response(HOLDEMController::toText([
            //     'error' => 0,
            //     'description' => 'OK'
            // ]))->header('Content-Type', 'text/plain');
        }


        public static function gameResult(\Illuminate\Http\Request $request){            
            // $data = json_decode($request->getContent(), true);
            // $authData = $data['data'];
            // $authData = json_encode(base64_decode($authData),true);

            // $username = $authData['userId'];
            // $token = $authData['token'];
            // $gameCode = $authData['gameCode'];
            // $gameName = $authData['gameName'];
            // $roundId = $authData['roundId'];
            // $amount = $authData['amount'];    //win+jackpot-bet+other
            // $betMoney = $authData['bet'];
            // $winMoney = $authData['win'];
            // $jackpotMoney = $authData['jackpot'];
            // $otherMoney = $authData['other'];
            // $startAmount = $authData['startAmount'];
            // $endAmount = $authData['endAmount'];
            // $balanceMoney = $authData['balance'];
            // $transactionId = $authData['transactionId'];
            // $transactionDate = $authData['transactionDate'];

            // $user = \App\Models\User::where(['username'=> $authData['userId']])->first();
            // if(!isset($user)){
            //     return response(HOLDEMController::toText([
            //         'error' => 10,
            //         'description' => 'The user was found'
            //     ]))->header('Content-Type', 'text/plain');
            // }

            // $prevRecord = \App\Models\HoldemTransaction::where(['user_id'=>$username,'gamecode' => $gameCode,'token'=>$token,'stats'=>0])->first();
            // if(!isset($prevRecord)){
            //     return response(HOLDEMController::toText([
            //         'error' => 4,
            //         'description' => 'Invalid   operator code '
            //     ]))->header('Content-Type', 'text/plain');
            // }

            // $preEventRecord = \App\Models\HoldemTransaction::where(['user_id'=>$user->username,'gamecode' => $gameCode,'transactionId'=>$transactionId,'stats'=>0])->first();

            // $userbalance = $balanceMoney;
            
            // if(isset($user->sessiondata()['safeAmount'])){
            //     $userbalance = $userbalance + $user->sessiondata()['safeAmount'];
            // }
            // if(!isset($preEventRecord)){
            //     return response(HOLDEMController::toText([
            //         'error' => 404,
            //         'description' => 'Not Found'
            //     ]))->header('Content-Type', 'text/plain');
            // }else{
            //     $data = $preEventRecord->data . ' /' . $authData; 
            //     $preEventRecord->update(['bet'=>$betMoney,'win'=>$amount,'data'=>$data,'balance'=>$userbalance,'stats'=>1]);
            // }
            
            // $user->balance = $userbalance;
            // $user->save();

            // \App\Models\StatGame::create([
            //     'user_id' => $user->id, 
            //     'balance' => $user->balance, 
            //     'bet' => $betMoney, 
            //     'win' => $amount,
            //     'game' =>  $gameName, 
            //     'type' => 'card',
            //     'percent' => 0, 
            //     'percent_jps' => 0, 
            //     'percent_jpg' => 0, 
            //     'profit' => 0, 
            //     'denomination' => 0, 
            //     'shop_id' => $user->shop_id,
            //     'category_id' => 62,
            //     'game_id' => $gameCode,
            //     'roundid' =>  $roundId,
            //     'date_time' => $transactionDate,
            //     'status' => 1
            // ]);
            // return response(HOLDEMController::toText([
            //     'error' => 0,
            //     'description' => 'OK'
            // ]))->header('Content-Type', 'text/plain');
        }


        public static function sessionClose(\Illuminate\Http\Request $request){            
            // $data = json_decode($request->getContent(), true);
            // $authData = $data['data'];
            // $authData = json_encode(base64_decode($authData),true);

            // $username = $authData['userId'];
            // $token = $authData['token'];
            // $gameCode = $authData['gameCode'];
            // if(isset($authData['authenticatedState'])){
            //     $authenState = $authData['authenticatedState'];
            // }
            // $reason = $authData['reason'];
            // $amountMoney = $authData['amount'];
            // $safeAmountMoney = $authData['safeAmount'];
            // $transactionId = $authData['transactionId'];
            // $transactionDate = $authData['transactionData'];

            // $user = \App\Models\User::where(['username'=> $username])->first();
            // if(!isset($user)){
            //     return response(HOLDEMController::toText([
            //         'error' => 10,
            //         'description' => 'The user was found'
            //     ]))->header('Content-Type', 'text/plain');
            // }

            // $prevRecord = \App\Models\HoldemTransaction::where(['user_id'=>$username,'gamecode' => $gameCode,'token'=>$token,'stats'=>0])->first();
            // if(!isset($prevRecord)){
            //     return response(HOLDEMController::toText([
            //         'error' => 4,
            //         'description' => 'Invalid   operator code'
            //     ]))->header('Content-Type', 'text/plain');
            // }
            
            // $sessionData = [
            //     'safeAmount' => 0,
            //     'safeUse' => false
            // ];
            // $user->session = json_encode($sessionData);
            // $user->save();

            // HoldemController::sessionCloseFinish();
            // return response(HOLDEMController::toText([
            //     'error' => 0,
            //     'description' => 'OK'
            // ]))->header('Content-Type', 'text/plain');
        }

        //세션 종료 완료 API
        public static function sessionCloseFinish(){
            $user= auth()->user();
            $username = $user->username;
            $date = Carbon::now();
            $prefix = HOLDEMController::HOLDEM_PROVIDER;
            $data= [
                'userId' => $prefix . sprintf("%04d",$user->id),
                'date' => $date
            ];
            $data = json_encode($data,true);
            $baseData = base64_encode($data);
            $gameUrlHash = HOLDEMController::generateHash($data);
            $op = config('app.holdem_opcode');

            $params = [
                'opCode' => $op,
                'data' => $baseData,
                'hash' => $gameUrlHash,
            ];
            $url = config('app.holdem_api') . '/game/session/close/finish';
            try{
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('Holdem sessionCloseFinish Request Failed ');
                    return [];
                }
                $data = $response->json();
                $trId = '';
                $balance = 0;
                $safebalance = 0;
                if($data['error'] != 0){
                    $trId= 0;
                    $balance = 0;
                    $safebalance = 0;
                }else{
                    $trId = $data['transactionId'];
                    $balance = $data['balance'];
                    $safebalance = $data['safeBalance'];
                }
                // $preEventRecord = \App\Models\HoldemTransaction::where(['user_id'=>$user->username,'transactionId'=>$trId,'stats'=>0])->first();
                $user = \App\Models\User::where(['username'=> $username])->first();
                if(isset($user->sessiondata()['safeAmount'])){
                    if($user->sessiondata()['safeAmount'] == $safebalance){
                        $user->balance = $balance + $safebalance;
                    }else{
                        return response(HOLDEMController::toText([
                            'error' => 21,
                            'description' => 'Insufficient safebox balance'
                        ]))->header('Content-Type', 'text/plain');
                    }
                }
                
                $user->save();
                // $data = $preEventRecord->data . ' /' . $data; 
                // $preEventRecord->update(['data'=>$data,'balance'=>$balance,'safebalance'=>$safebalance,'stats'=>1]);
            }catch(\Exception $ex){
                Log::error('Holdem sessionCloseFinish Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Holdem sessionCloseFinish Request :  Excpetion. PARAMS= ' . json_encode($params));
            }
            
        }

        public static function toText($obj) {
            $response = '';
            foreach ($obj as $key => $value) {
                if ($value !== null) {
                    $response = "{$response}\r\n{$key}={$value}";
                }
            }
            return trim($response, "\r\n");
        }

        public static function processGameRound($frompoint=-1, $checkduplicate=false)
        {
            $timepoint = 0;
            if ($frompoint == -1)
            {
                $tpoint = \App\Models\Settings::where('key', self::HOLDEM_PROVIDER . 'timepoint')->first();
                if ($tpoint)
                {
                    $timepoint = $tpoint->value;
                }
            }
            else
            {
                $timepoint = $frompoint;
            }

            $count = 0;
            $data = null;
            $newtimepoint = $timepoint;
            $totalCount = 0;      
            $data = HOLDEMController::gamerounds($timepoint);
            if ($data == null)
            {
                if ($frompoint == -1)
                {

                    if ($tpoint)
                    {
                        $tpoint->update(['value' => $newtimepoint]);
                    }
                    else
                    {
                        \App\Models\Settings::create(['key' => self::HOLDEM_PROVIDER .'timepoint', 'value' => $newtimepoint]);
                    }
                }

                return [0,0];
            }
            $invalidRounds = [];
            if (isset($data) && isset($data['roundList']))
            {
                foreach ($data['roundList'] as $round)
                {
                    // if ($round['txn_type'] != 'CREDIT')
                    // {
                    //     continue;
                    // }

                    $gameObj = self::getGameObj($round['gameCode']);
                    
                    if (!$gameObj)
                    {
                        Log::error('HOLDEM Game could not found : '. $round['gameCode']);
                            continue;
                    }
                    $bet = $round['bet'];
                    $win = $round['win'];
                    $balance = $round['balance'];
                    
                    $time = $round['date'];

                    $userid = intval(preg_replace('/'. self::HOLDEM_PROVIDER .'(\d+)/', '$1', $round['userId'])) ;
                    if ($userid == 0)
                    {
                        $userid = intval(preg_replace('/'. self::HOLDEM_PROVIDER .'(\d+)/', '$1', $round['userId'])) ;
                        continue;
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
                            'roundid' => $round['providerCode'] . '#' . $round['gameCode'] . '#' . $round['roundId'],
                        ])->first();
                        if ($checkGameStat)
                        {
                            continue;
                        }
                    // }
                    $gamename = $gameObj['name'] . '_holdem';
                   
                    \App\Models\StatGame::create([
                        'user_id' => $userid, 
                        'balance' => $balance, 
                        'bet' => $bet, 
                        'win' => $win, 
                        'game' => $gamename, 
                        'type' => 'card',
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
                        'roundid' => $round['providerCode'] . '#' . $round['gameCode'] . '#' . $round['roundId'],
                    ]);
                    $count = $count + 1;
                }
            }
            if(isset($data['listEndIndex']) && $data['listEndIndex'] > -1){
                $timepoint = $data['listEndIndex']; // 다시 확인 +1;
            }

            if ($frompoint == -1)
            {

                if ($tpoint)
                {
                    $tpoint->update(['value' => $timepoint]);
                }
                else
                {
                    \App\Models\Settings::create(['key' => self::HOLDEM_PROVIDER .'timepoint', 'value' => $timepoint]);
                }
            }
           
            return [$count, $timepoint];
        }


        public static function gamerounds($lastid)
        {
            $date = Carbon::now();
            $op = config('app.holdem_opcode');
            $data = [
                'walletType' => 'board',
                'listIndex' => $lastid,
                'date' => $date
            ];
            $data = json_encode($data,true);
            $baseData = base64_encode($data);
            $gameRoundHash = HOLDEMController::generateHash($data);
            

            $params = [
                'opCode' => $op,
                'hash' => $gameRoundHash,
                'data' => $baseData
            ];
            
            $url = config('app.holdem_api') . '/history/DataFeeds/gamerounds';
            try
            {
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('Holdem GetGameRounds Request Response Failed ');
                    return null;
                }
                $data = $response->json();
                if($data['error'] == 0){
                    return $data;
                }else{
                    Log::error('Holdem GetGameRounds Request Response Error ');
                    return null;
                }
            }catch(\Exception $ex){
                Log::error('Holdem MakeGameLink Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Holdem MakeGameLink Request :  Excpetion. PARAMS= ' . json_encode($params));
            }
            return null;
        }

        public static function getAgentBalance(){
            $date = Carbon::now();
            $op = config('app.holdem_opcode');
            $data = [
                'date' => $date
            ];
            $data = json_encode($data,true);
            $baseData = base64_encode($data);
            $agentBalanceHash = HOLDEMController::generateHash($data);

            $params = [
                'opCode' => $op,
                'hash' => $agentBalanceHash,
                'data' => $baseData
            ];

            $url = config('app.holdem_api') . '/operator/balance/current';

            try{
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('Holdem GetAgentBalance Request Response Failed ');
                    return -1;
                }
                $data = $response->json();
                if($data['error'] == 0){
                    if(isset($data['balanceList'])){
                        for($k = 0; $k < count($data['balanceList']); $k++){
                            if($data['balanceList'][$k]['walletType'] == 'board'){
                                return intval($data['balanceList'][$k]['balance']);
                            }
                        }
                    }
                }else{
                    Log::error('Holdem GetAgentBalance Request Response Error ');
                    return -1;
                }
            }catch(\Exception $ex){
                Log::error('Holdem GetAgentBalance Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Holdem GetAgentBalance Request :  Excpetion. PARAMS= ' . json_encode($params));
            }
            return -1;
        }
        public static function terminate($userId){
            $date = Carbon::now();
            $op = config('app.holdem_opcode');
            $prefix = HOLDEMController::HOLDEM_PROVIDER;
            $data= [
                'userId' => $prefix . sprintf("%04d",$userId),
                'date' => $date
            ];
            $data = json_encode($data,true);
            $baseData = base64_encode($data);
            $agentBalanceHash = HOLDEMController::generateHash($data);

            $params = [
                'opCode' => $op,
                'hash' => $agentBalanceHash,
                'data' => $baseData
            ];
            $url = config('app.holdem_api') . '/game/session/terminate';
            try{
                $response = Http::get($url, $params);
                if (!$response->ok())
                {
                    Log::error('Holdem Terminate Request Response Failed ');
                    return ['error' => '-1', 'description' => '제공사응답 오류'];
                }
                $data = $response->json();
                if($data['error'] == 0){
                    $data = $response->json();
                    return $data;
                }else{
                    Log::error('Holdem Terminate Request Response Error ');
                    return ['error' => '-1', 'description' => '제공사응답 오류'];
                }
            }catch(\Exception $ex){
                Log::error('Holdem Terminate Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Holdem Terminate Request :  Excpetion. PARAMS= ' . json_encode($params));
            }
            return ['error' => '-1', 'description' => '제공사응답 오류'];
        }
    }
}
