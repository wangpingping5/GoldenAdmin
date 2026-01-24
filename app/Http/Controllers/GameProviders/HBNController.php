<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    class HBNController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        public function checktransfer($tid)
        {
            $record = \App\Models\HBNTransaction::Where('transferid',$tid)->get()->first();
            return $record;
        }
        public static function generateCode($limit){
            $code = 0;
            for($i = 0; $i < $limit; $i++) { $code .= mt_rand(0, 9); }
            return $code;
        }

        public function microtime_string()
        {
            $microstr = sprintf('%.4f', microtime(TRUE));
            $microstr = str_replace('.', '', $microstr);
            return $microstr;
        }
        public static function getGameObj($code)
        {

            $categories = \App\Models\Category::where(['provider' => 'hbn', 'shop_id' => 0, 'site_id' => 0])->get();
            $gamelist = [];
            foreach ($categories as $category)
            {
                $gamelist = HBNController::getgamelist($category->href);
                if (count($gamelist) > 0 )
                {
                    foreach($gamelist as $game)
                    {
                        if ($game['gamecode'] == $code)
                        {
                            $game['cat_id'] = $category->original_id;
                            return $game;
                            break;
                        }
                    }
                }
            }
            return null;
        }
        /*
        * FROM HABANERO, BACK API
        */

        public function endpoint(\Illuminate\Http\Request $request)
        {
            $data = json_decode($request->getContent());            
            $type = $data->type;

            $response = [];
            \DB::beginTransaction();
            switch ($type)
            {
                case 'playerdetailrequest':
                    $response = $this->verifyAuth($data);
                    break;
                case 'fundtransferrequest':
                    $response = $this->makeFundTransferResponse($data);
                    break;
                case 'queryrequest':
                    $response = $this->makeQueryResponse($data);
                    break;
                default:
                    $response = ['error' => 'Invalid Message type.'];
            }
            \DB::commit();
            return response()->json($response, 200);
        }

        public function verifyAuth($data)
        {
            if (!isset($data->playerdetailrequest) || !isset($data->playerdetailrequest->token))
            {
                return ['error' => 'invalid request'];
            }

            $token = $data->playerdetailrequest->token;
            $user = \App\Models\User::lockForUpdate()->Where('api_token',$token)->get()->first();
            if (!$user || !$user->hasRole('user')){
                $externalResponse = $this->externalResponse();
                $playerResponse = $this->playerResponse();
                $fundsReponse = $this->fundsResponse();
                $playerResponse['status']['message'] = 'Invalid Auth token';
                $fundsReponse['status']['message'] = 'Invalid Auth token';
                $externalResponse['playerdetailresponse'] = $playerResponse;
                $externalResponse['fundtransferresponse'] = $fundsReponse;
                return $externalResponse;
            }

            $playerResponse =	$this->playerResponse();
            $playerResponse['status']['autherror'] = false;
            $playerResponse['status']['success'] = true;
            $playerResponse['accountid'] = strval($user->id);
            $playerResponse['accountname'] = $user->username;
            $playerResponse['currencycode'] = 'KRW';
            $playerResponse['balance'] = $user->balance;

            $externalResponse = $this->externalResponse();
            $externalResponse['playerdetailresponse'] = $playerResponse;
            return $externalResponse;
        }

        function makeQueryResponse($data){
            $queryResponse = $this->externalQueryResponse();
            if (!isset($data->queryrequest) || !isset($data->queryrequest->transferid))
            {
                return ['error' => 'invalid request'];
            }

            $transferId = $data->queryrequest->transferid;
    
            $transaction = $this->checktransfer($transferId);
    
            if($transaction != null){
                $queryResponse['fundtransferresponse']['status']['success'] =  true;
                $queryResponse['fundtransferresponse']['remotetransferid'] = $transaction->timestamp;
            }
    
            return $queryResponse;
        }

        function makeFundTransferResponse($data){
            $transactionStatus = true;
            $response = $this->externalResponse();
    
            $response['fundtransferresponse'] = $this->fundsResponse();
    
            $fundtransferrequest = $data->fundtransferrequest;
            $accountid = $fundtransferrequest->accountid;
            $token = $fundtransferrequest->token;
            $user = \App\Models\User::lockForUpdate()->find($accountid);

            if (!$user || !$user->hasRole('user') || ($fundtransferrequest->isretry==false && $user->api_token != $token)){
                $externalResponse = $this->externalResponse();
                $playerResponse = $this->playerResponse();
                $fundsReponse = $this->fundsResponse();
                $playerResponse['status']['message'] = 'Invalid Auth token';
                $fundsReponse['status']['message'] = 'Invalid Auth token';
                $externalResponse['playerdetailresponse'] = $playerResponse;
                $externalResponse['fundtransferresponse'] = $fundsReponse;
                return $externalResponse;
            }
            $response['fundtransferresponse']['status']['autherror'] = false;
            $total_bet = 0;
            $total_win = 0;
    
            if($fundtransferrequest->funds->debitandcredit == true){
                // Debit as well as credit is passed
    
                $debit = $fundtransferrequest->funds->fundinfo[0];
                $credit = $fundtransferrequest->funds->fundinfo[1];
    
                $debitResult = $this->updateBalance($user,$debit->amount, $fundtransferrequest->gameinstanceid, $debit);
    
                if($debitResult['Success'] == true){
                    if ($debit->amount < 0)  {
                        $total_bet = $total_bet + abs($debit->amount);
                    }
                    else
                    {
                        $total_win = $total_win + abs($debit->amount);
                    }
                    $response['fundtransferresponse']['status']['successdebit'] = true;			
                    //now do the Credit
                    $creditResult = $this->updateBalance($user, $credit->amount, $fundtransferrequest->gameinstanceid, $credit);
                            
                    if ($creditResult['Success'])
                    {
                        if ($credit->amount < 0)  {
                            $total_bet = $total_bet + abs($credit->amount);
                        }
                        else
                        {
                            $total_win = $total_win + abs($credit->amount);
                        }

                        $response['fundtransferresponse']['status']['successcredit'] = true;
                        $response['fundtransferresponse']['status']['success'] = true;
                    }
                    else
                    {
                        $response['fundtransferresponse']['status']['success'] = false;
                        $response['fundtransferresponse']['status']['message'] = "Couldn't credit";
                    }
                    $response['fundtransferresponse']['balance'] = $creditResult['BalanceAfterTransaction']; //set back the current balance after the credit
    
                }else{
                    //if the debit failed, then return...
                    $response['fundtransferresponse']['status']['message'] = "Insufficient funds";
                    $response['fundtransferresponse']['status']['nofunds'] = $debitResult['NoFunds'];
                    $response['fundtransferresponse']['balance'] = $debitResult['BalanceAfterTransaction']; //set back the current balance after the credit
                }
    
                $response['fundtransferresponse']['remotetransferid'] = uniqid('', true);

                if ($total_bet > 0 || $total_win > 0)
                {
                    // $category = \App\Models\Category::where(['provider' => 'hbn', 'shop_id' => 0, 'href' => 'hbn'])->first();
                    $gmObj = HBNController::getGameObj($fundtransferrequest->gamedetails->brandgameid);
                    \App\Models\StatGame::create([
                        'user_id' => $user->id, 
                        'balance' => floatval($user->balance), 
                        'bet' => $total_bet, 
                        'win' => $total_win, 
                        'game' => $fundtransferrequest->gamedetails->keyname . '_' . $gmObj['href'], 
                        'percent' => 0, 
                        'percent_jps' => 0, 
                        'percent_jpg' => 0, 
                        'profit' => 0, 
                        'denomination' => 0, 
                        'shop_id' => $user->shop_id,
                        'category_id' => isset($gmObj['cat_id'])?$gmObj['cat_id']:0,
                        'game_id' => $fundtransferrequest->gamedetails->brandgameid,
                        'roundid' => 0,
                    ]);
                }
    
                return $response;
            }
    
            //This is NOT a D&C (Debit and Credit in one request) Package
            if($fundtransferrequest->funds->debitandcredit == false){
            
                //If its a refund request then:
                if($fundtransferrequest->isrefund){
                    $refund = $fundtransferrequest->funds->refund;
    
                    //check if the refund request has been processed before?
                    $previousRefundTransfer = $this->checktransfer($refund->transferid);
                    if($previousRefundTransfer != null){
                        $response['fundtransferresponse']['status']['success'] = true;
                        $response['fundtransferresponse']['status']['refundstatus'] = "1";
                        return $response;
                    }
                    //else we have not done the refund before, so now lets check the status of the transaction we trying to refund.. and see if it must be refunded or not.
    
                    $originalTransfer = $this->checktransfer($refund->originaltransferid);
                    if($originalTransfer != null){
                        $refundResult = $this->updateBalance($user, $refund->amount, $fundtransferrequest->gameinstanceid, $refund);
                        if($refundResult['Success']){
                            if ($refund->amount < 0)  {
                                $total_bet = $total_bet + abs($refund->amount);
                            }
                            else
                            {
                                $total_win = $total_win + abs($refund->amount);
                            }

                            $response['fundtransferresponse']['status']['success'] = true;
                            $response['fundtransferresponse']['status']['refundstatus'] = "1";
                        }else{
                            $response['fundtransferresponse']['status']['success'] = false;
                            $response['fundtransferresponse']['status']['message'] = "Could Not Refund";
    
                        }
                    }else{
                        $response['fundtransferresponse']['status']['success'] = true;
                        $response['fundtransferresponse']['status']['refundstatus'] = "2";
                        $response['fundtransferresponse']['status']['message'] = "Original request never debited. So closing record.";
                    }
    
                    $response['fundtransferresponse']['balance'] = $user->balance;
                    if ($total_bet > 0 || $total_win > 0)
                    {
                        // $category = \App\Models\Category::where(['provider' => 'hbn', 'shop_id' => 0, 'href' => 'hbn'])->first();
                        $gmObj = HBNController::getGameObj($fundtransferrequest->gamedetails->brandgameid);
                        \App\Models\StatGame::create([
                            'user_id' => $user->id, 
                            'balance' => floatval($user->balance), 
                            'bet' => $total_bet, 
                            'win' => $total_win, 
                            'game' => $fundtransferrequest->gamedetails->keyname . '_' . $gmObj['href'] . ' refund', 
                            'percent' => 0, 
                            'percent_jps' => 0, 
                            'percent_jpg' => 0, 
                            'profit' => 0, 
                            'denomination' => 0, 
                            'shop_id' => $user->shop_id,
                            'category_id' => isset($gmObj['cat_id'])?$gmObj['cat_id']:0,
                            'game_id' => $fundtransferrequest->gamedetails->brandgameid,
                            'roundid' => 0,
                        ]);
                    }
                    return $response;
                }
    
                //Is this a ReCredit request?
                if($fundtransferrequest->isrecredit){
                    $recreditRequest = $fundtransferrequest->funds->fundinfo[0];
                    $originalTransfer = $this->checktransfer($recreditRequest->originaltransferid);
    
                    if($originalTransfer != null){
                        $response['fundtransferresponse']['status']['autherror'] = false;
                        $response['fundtransferresponse']['status']['success'] = true;
                        $response['fundtransferresponse']['balance'] = $user->balance;
                        $response['fundtransferresponse']['status']['message'] = "Orignal Credit was done - so no need to do it again.";
                        return $response;
                    }
                    
                    //credit the amount
                    $recreditResult = $this->updateBalance($user, $recreditRequest->amount, $fundtransferrequest->gameinstanceid, $recreditRequest);
                    if($recreditResult['Success']){
                        if ($recreditRequest->amount < 0)  {
                            $total_bet = $total_bet + abs($recreditRequest->amount);
                        }
                        else
                        {
                            $total_win = $total_win + abs($recreditRequest->amount);
                        }
                        $response['fundtransferresponse']['status']['autherror'] = false;
                        $response['fundtransferresponse']['status']['success'] = true;
                        $response['fundtransferresponse']['balance'] = $user->balance;
                        $response['fundtransferresponse']['status']['message'] = "Original request never credited, so we did the re-credit";
                    }
                    if ($total_bet > 0 || $total_win > 0)
                    {
                        // $category = \App\Models\Category::where(['provider' => 'hbn', 'shop_id' => 0, 'href' => 'hbn'])->first();
                        $gmObj = HBNController::getGameObj($fundtransferrequest->gamedetails->brandgameid);
                        \App\Models\StatGame::create([
                            'user_id' => $user->id, 
                            'balance' => floatval($user->balance), 
                            'bet' => $total_bet, 
                            'win' => $total_win, 
                            'game' => $fundtransferrequest->gamedetails->keyname . '_' . $gmObj['href'] . ' recredit', 
                            'percent' => 0, 
                            'percent_jps' => 0, 
                            'percent_jpg' => 0, 
                            'profit' => 0, 
                            'denomination' => 0, 
                            'shop_id' => $user->shop_id,
                            'category_id' => isset($gmObj['cat_id'])?$gmObj['cat_id']:0,
                            'game_id' => $fundtransferrequest->gamedetails->brandgameid,
                            'roundid' => 0,
                        ]);
                    }
                    return $response;
                }
    
                //**** It's not a Refund and not a Recredit, then its just a regular single transaction for debit OR credit ***
                $transferRequest = $fundtransferrequest->funds->fundinfo[0];
                
                $transferResult = $this->updateBalance($user, $transferRequest->amount, $fundtransferrequest->gameinstanceid, $transferRequest);
        
                if($transferResult['Success']){
                    if ($transferRequest->amount < 0)  {
                        $total_bet = $total_bet + abs($transferRequest->amount);
                    }
                    else
                    {
                        $total_win = $total_win + abs($transferRequest->amount);
                    }
                    $response['fundtransferresponse']['status']['success'] = true;				
                }else{
                    $response['fundtransferresponse']['status']['success'] = false;
                    $response['fundtransferresponse']['status']['autherror'] = false;
                    $response['fundtransferresponse']['status']['nofunds'] = $transferResult['NoFunds'];
                    $response['fundtransferresponse']['status']['message'] = 'Insufficient funds';
                }
                $response['fundtransferresponse']['remotetransferid'] = uniqid('', true);
                $response['fundtransferresponse']['balance'] = $transferResult['BalanceAfterTransaction'];
    
                if($response['fundtransferresponse']['balance'] == 0){
                    $response['fundtransferresponse']['balance'] = $user->balance;
                }
                if ($total_bet > 0 || $total_win > 0)
                {
                    // $category = \App\Models\Category::where(['provider' => 'hbn', 'shop_id' => 0, 'href' => 'hbn'])->first();
                    $gmObj = HBNController::getGameObj($fundtransferrequest->gamedetails->brandgameid);
                    \App\Models\StatGame::create([
                        'user_id' => $user->id, 
                        'balance' => floatval($user->balance), 
                        'bet' => $total_bet, 
                        'win' => $total_win, 
                        'game' => $fundtransferrequest->gamedetails->keyname . '_' . $gmObj['href'], 
                        'percent' => 0, 
                        'percent_jps' => 0, 
                        'percent_jpg' => 0, 
                        'profit' => 0, 
                        'denomination' => 0, 
                        'shop_id' => $user->shop_id,
                        'category_id' => isset($gmObj['cat_id'])?$gmObj['cat_id']:0,
                        'game_id' => $fundtransferrequest->gamedetails->brandgameid,
                        'roundid' => 0,
                    ]);
                }
                return $response;
            }
        }
        public function updateBalance($user, $amount, $gameinstanceid, $fundinfo){

            $result = array(
                'NoFunds' => false,
                'Success' => false,
                'BalanceAfterTransaction' => 0,
                'TransactionId' => ''
            );

            $continueTransaction = false;
    
            if($amount < 0){
                // It is a debit
                if($user->balance >= abs($amount)){
                    $continueTransaction = true;
                }
                else{
                    $continueTransaction = false;
                    $result['NoFunds'] = true;
    
                }
            }else{
                // It is a credit
                $continueTransaction = true;
            }

            $transactions = \App\Models\HBNTransaction::where('gameinstanceid', $gameinstanceid)->get();
            foreach ($transactions as $tr)
            {
                $data = json_decode($tr->data);
                if ($data->gamestatemode == 2) //round is already ended
                {
                    $continueTransaction = false;
                    break;
                }
            }
    
            if($continueTransaction){
                $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
                $user->save();
                $result['Success'] = true;
                $result['BalanceAfterTransaction'] = $user->balance;
            }

            $transaction = \App\Models\HBNTransaction::create([
                'transferid' => $fundinfo->transferid, 
                'gameinstanceid' => $gameinstanceid,
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($fundinfo),
            ]);
    
            $result['TransactionId'] = $transaction->timestamp;
    
            return $result;
        }

        /*
        * FROM CONTROLLER, API
        */
        
        public static function getgamelist($href)
        {
            $gameList = \Illuminate\Support\Facades\Redis::get($href . 'list');
            if ($gameList)
            {
                $games = json_decode($gameList, true);
                if ($games!=null && count($games) > 0){
                    return $games;
                }
            }

            $data = [
                'BrandId' => config('app.hbn_brandid'),
                'APIKey' => config('app.hbn_apikey'),
            ];
            $reqbody = json_encode($data);
            $response = Http::withBody($reqbody, 'application/json')->post(config('app.hbn_api') . '/GetGames');
            if (!$response->ok())
            {
                return [];
            }
            $data = $response->json();
            $gameList = [];
            $exProv = ($href != 'hbn');

            foreach ($data['Games'] as $game)
            {
                if ($game['GameTypeName'] == "Video Slots" && $game['ExProv'] == $exProv)
                {
                    $gameList[] = [
                        'provider' => 'hbn',
                        'href' => $href,
                        'gamecode' => $game['BrandGameId'],
                        'name' => preg_replace('/\s+/', '', $game['KeyName']),
                        'title' => \Illuminate\Support\Facades\Lang::has('gameprovider.'.$game['Name'], 'ko')?__('gameprovider.'.$game['Name']):$game['Name'],
                        'icon' => config('app.hbn_game_server') . '/img/rect/300/'. $game['KeyName'] . '.png',
                        // 'demo' => HBNController::makegamelink($game['BrandGameId'], 'fun')
                    ];
                }
            }
            \Illuminate\Support\Facades\Redis::set($href. 'list', json_encode($gameList));
            return $gameList;
        }

        public static function makegamelink($gamecode, $mode)
        {
            $user = auth()->user();
            if ($user == null)
            {
                return null;
            }
            $detect = new \Detection\MobileDetect();
            $key = [
                'brandid' => config('app.hbn_brandid'),
                'brandgameid' => $gamecode,
                'token' => $user->api_token,
                'mode' => $mode,
                'locale' => 'ko',
            ];
            $str_params = implode('&', array_map(
                function ($v, $k) {
                    return $k.'='.$v;
                }, 
                $key,
                array_keys($key)
            ));
            return  config('app.hbn_game_server') . '/go.ashx?' . $str_params;
        }

        public static function getgamelink($gamecode)
        {
            return ['error' => false, 'data' => ['url' => route('frontend.providers.hbn.render', $gamecode)]];
            // $url = HBNController::makegamelink($gamecode, 'real');
            // if ($url){
            //     return ['error' => false, 'data' => ['url' => $url]];
            // }
            // else
            // {
            //     return ['error' => true, 'msg' => '로그인하세요'];
            // }
        }

        public static function playerResponse(){
            return array(
                "status" =>
                    array(
                        "success" => false,
                        "nofunds" => false,
                        "successdebit" => false,
                        "successcredit" => false,
                        "message" => "",
                        "autherror" => true,
                        "refundstatus" => 0
                    ),
                "accountid" => "",
                "accountname" => "",
                "balance" => 0,
                "currencycode" => "",
                "country" => null,
                "fname" => null,
                "lname" => null,
                "email" => null,
                "tel" => null,
                "telalt" => null
            );
        }
    
        public static function fundsResponse(){
            return array("status" =>
                array(
                    "success" => false,
                    "nofunds" => false,
                    "successdebit" => false,
                    "successcredit" => false,
                    "message" => null,
                    "autherror" => true,
                    "refundstatus" => 0
                ),
                "balance" => "",
                "currencycode" => "KRW",
                "remoteid" => null,
                "remotetransferid" => ""
            );
        }
    
        public static function externalResponse(){
            return array(
                "playerdetailresponse" => null,
                "configdetailresponse" => null,
                "fundtransferresponse" => null,
                "jackpotdetailresponse" => null
            );
        }
    
        public static function externalQueryResponse(){
            return array(
                "fundtransferresponse" => array(
                    "status" => array(
                        "success" => false
                    ),
                    "remotetransferid" => null
                )
            );
        }
        
        // History
        public function historyIndex(\Illuminate\Http\Request $request)
        {
            return view('frontend.Default.games.hbn.history');
        }
        public function history($requestname, \Illuminate\Http\Request $request)
        {
            $paramData = preg_replace('/\s+/', '',(file_get_contents('php://input')));
            $paramData = preg_replace('/\'/','"', $paramData);
            $param = json_decode($paramData);
            if ($requestname == 'GetHistory'){
                $d = [];
                $user = \App\Models\User::where('api_token', $param->pid)->first();
                if ($user)
                {
                    $starttimeUTC = substr($param->dtStartUtc,0,4) . '-' . substr($param->dtStartUtc,4,2) . '-' .substr($param->dtStartUtc,6,2) . ' ' . substr($param->dtStartUtc,8,2) . ':' . substr($param->dtStartUtc,10,2) . ':00';
                    $date = new \DateTime($starttimeUTC, new \DateTimeZone('UTC'));
                    $timezoneName = timezone_name_from_abbr("", +9*3600, false);
                    $date->setTimezone(new \DateTimeZone($timezoneName));
                    $starttime= $date->format('Y-m-d H:i:s');
                    
                    $endtimeUTC = substr($param->dtEndUtc,0,4) . '-' . substr($param->dtEndUtc,4,2) . '-' .substr($param->dtEndUtc,6,2) . ' ' . substr($param->dtEndUtc,8,2) . ':' . substr($param->dtEndUtc,10,2) . ':00';
                    $date = new \DateTime($endtimeUTC, new \DateTimeZone('UTC'));
                    $date->setTimezone(new \DateTimeZone($timezoneName));
                    $endtime= $date->format('Y-m-d H:i:s');

                    $stat_games = \App\Models\StatGame::where(['user_id'=>$user->id, 'game_id'=>$param->bid])->orderby('date_time', 'desc')->where('date_time', '>=', $starttime)->where('date_time', '<=', $endtime)->take(100)->get();
                    foreach ($stat_games as $stat)
                    {
                        $d[] = [
                            "IsTestSite"=> false,
                            "DateToShow"=> strtotime($stat->date_time) * 1000,
                            "DtCompleted"=> "\/Date(".(strtotime($stat->date_time) * 1000).")\/",
                            "FriendlyId"=> 53645 . $stat->id,
                            "GameInstanceId"=> strval($stat->id),
                            "RealPayout"=> $stat->win,
                            "RealStake"=> $stat->bet,
                            "CurrencyCode"=> "KRW",
                            "GameStateId"=> 3,
                            "GameKeyName"=> "SGTheKoiGate",
                            "ExtRoundId"=> null,
                            "Exponent"=> 0
                        ];
                    }
                }
                return response()->json(['d' => $d]);
            }
            elseif ($requestname == 'GetGameDetails')
            {
                $report = [
                    "numcoins"=> 0,
                    "bettype"=> "Lines",
                    "paylinecount"=> 18,
                    "coindenomination"=> 100.0,
                    "linebet"=> 100.0,
                    "betlevel"=> 1,
                    "totalbet"=> 1800.0,
                    "wincash"=> 0.0,
                    "winfreegames"=> 0,
                    "events"=> [[
                        "dt"=> 637740989754015341,
                        "type"=> "spin",
                        "gamemode"=> "MAIN",
                        "wincash"=> 0.0,
                        "winfreegames"=> 0.0,
                        "winmultiplier"=> 0,
                        "reels"=> [
                            ["idWave", "idLeaves", "idLeaves"],
                            ["idBamboo", "idBamboo", "idBamboo"],
                            ["idK", "idLeaves", "idLeaves"],
                            ["idK", "idK", "idQ"],
                            ["idWave", "idWave", "idWave"]
                        ]
                    ]]
                ];
                $d = [
                    "GameType"=> 11,
                    "GameState"=> "GameState_3",
                    "GameKeyName"=> "SGTheKoiGate",
                    "CurrencyCode"=> "KRW",
                    "FriendlyId"=> "53645806817",
                    "DtStarted"=> 1638502175397.0,
                    "DtCompleted"=> 1638502175400.0,
                    "RealStake"=> 1800.0000,
                    "RealPayout"=> 0.0000,
                    "BonusStake"=> 0.0,
                    "BonusPayout"=> 0.0,
                    "BonusToReal"=> 0.0,
                    "VideoSlotGameDetails"=> [
                        "ReportInfo"=> json_encode($report)
                    ],
                    "CurrencyExponent"=> 0,
                    "BalanceAfter"=> 996160.0000,
                    "IsCheat"=> false
                ];
            }
            return response(['d' => json_encode([])], 200)->header('Content-Type', 'application/json; charset=utf-8');
        }
    }
}
