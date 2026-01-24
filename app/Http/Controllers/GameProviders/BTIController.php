<?php 
namespace App\Http\Controllers\GameProviders
{

    use Carbon\Carbon;
    use DateTimeZone;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    use DateTime;
    class BTIController extends \App\Http\Controllers\Controller
    {
        public static function getGameObj($uuid)
        {
            $gamelist = BTIController::getgamelist();
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
        public static function getgamelist($href='')
        {
            return [[
                'provider' => 'bti',
                'gamecode' => 'bti-sports',
                'enname' => 'sports',
                'name' => '스포츠',
                'title' => '스포츠',
                'type' => 'sports',
                'href' => 'bti-sports',
                'view' => 1,
                'icon' => '/frontend/Default/ico/bti_sports.jpg',
                ]];            
        }
        public function ValidateToken(\Illuminate\Http\Request $request){
            // $data = json_decode($request->getContent(), true);
            // $authToken = isset($data['auth_token'])?$data['auth_token']:0;
            $authToken = $request->auth_token;
            if($authToken == null){
                return response(BTIController::toText([
                    'error_code' => -1,
                    'error_message' => 'Generic Error'
                ]))->header('Content-Type', 'text/plain');
            }
            $user = \App\Models\User::where(['api_token'=> $authToken])->first();
            if (!isset($user))
            {
                return response(BTIController::toText([
                    'error_code' => -1,
                    'error_message' => 'Generic Error'
                ]))->header('Content-Type', 'text/plain');
            }
            $username = 'BTI' . sprintf("%04d",$user->id);
            return response(BTIController::toText([
                'error_code' => 0,
                'error_message' => 'No Error',
                'cust_id' => $user->id,
                'balance' => sprintf('%0.2f',$user->balance),
                'cust_login' => $username,
                'city' => 'City',
                'country' => 'KR',
                'currency_code' => 'KRW'
            ]))->header('Content-Type', 'text/plain');
        }

        public function reserve(\Illuminate\Http\Request $request){
            \DB::beginTransaction();
            $xmlObject = simplexml_load_string($request->getContent());            
            $json = json_encode($xmlObject);
            $array = json_decode($json,true); 
            $custId = $request->cust_id;// $array['@attributes']['cust_id'];
            $reserveId = $request->reserve_id;// $array['@attributes']['reserve_id'];
            $amount = $request->amount;// $array['@attributes']['amount'];
            if(count($array['Bet'])>1){  //system: off, riskfreebet: on
                if(isset($array['Bet'][0])){
                    $betTypeId = $array['Bet'][0]['@attributes']['BetTypeID'];
                    $betTypeName = $array['Bet'][0]['@attributes']['BetTypeName'];
                }else{
                    $betTypeId = $array['Bet']['@attributes']['BetTypeID'];
                    $betTypeName = $array['Bet']['@attributes']['BetTypeName'];
                }
                
            }else{
                $betTypeId = $array['Bet']['@attributes']['BetTypeID'];
                $betTypeName = $array['Bet']['@attributes']['BetTypeName'];
            }

            $user = \App\Models\User::where(['id'=> $custId])->first();
            //No User
            if (!isset($user) || !is_numeric($custId))
            {
                $record = \App\Models\BTiTransaction::create(['error_code' => -2,'error_message' => 'Invalid Customer','amount' => $amount,'balance' => 0.00,'user_id' => $custId,'reserve_id' => $reserveId,'data' => json_encode($array),'status' => -1,'bet_type_id'=>$betTypeId,'bet_type_name'=>$betTypeName]);

                return response(BTIController::toText([
                    'error_code' => -2,
                    'error_message' => 'Invalid Customer',
                    'trx_id' => $record->id,
                    'balance' => 0.00
                ]))->header('Content-Type', 'text/plain');
            }
            $userBalance = $user->balance;
            //bet amount is greater than user balance
            if($amount > $userBalance){
                $record = \App\Models\BTiTransaction::create(['error_code' => -4,'error_message' => 'Insufficient Amount','amount' => $amount,'balance' => $userBalance,'user_id' => $custId,'reserve_id' => $reserveId,'data' => json_encode($array),'status' => -1,'bet_type_id'=>$betTypeId,'bet_type_name'=>$betTypeName]);

                return response(BTIController::toText([
                    'error_code' => -4,
                    'error_message' => 'Insufficient Amount',
                    'trx_id' => $record->id,
                    'balance' => sprintf('%0.2f',$userBalance)
                ]))->header('Content-Type', 'text/plain');
            }

            $sameReserve = false;
            $record = \App\Models\BTiTransaction::where(['user_id' => $custId,'status'=>0,'reserve_id'=>$reserveId])->first();
            $balance = 0;
            $sendreserveId = 0;
            
            
            if(!isset($record)){    
                $balance = $userBalance - $amount;            
                $record_1 = \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'No Error','amount' => $amount,'balance' => $balance,'user_id' => $custId,'reserve_id' => $reserveId,'data' => json_encode($array),'status' => 0,'bet_type_id'=>$betTypeId,'bet_type_name'=>$betTypeName]);

                $user->balance = $balance;
                $user->save();

                \DB::commit();
                
                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'No Error',
                    'trx_id' => $record_1->id,
                    'balance' => sprintf('%0.2f',$balance)
                ]))->header('Content-Type', 'text/plain');
            }else{
                $sameRequest = false;
                $records = \App\Models\BTiTransaction::where(['user_id' => $custId,'reserve_id'=>$reserveId])->get();
                foreach($records as $recordx){
                    if($recordx->status == 2){
                        $sameRequest = true;
                    }
                }
                $balance = 0;
                if($sameRequest){
                    $user->balance = $user->balance - $amount;
                    $user->save();
                    $balance = $user->balance;
                }else{
                    $balance = $record->balance;
                }

                //$this->getgamedetail();   //test
                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'No Error',
                    'trx_id' => $record->id,
                    'balance' => sprintf('%0.2f',$balance)
                ]))->header('Content-Type', 'text/plain');
            }
        }



        public function cancelreserve(\Illuminate\Http\Request $request){
            \DB::beginTransaction();

            $agentId = $request->agent_id;// isset($data['agent_id'])?$data['agent_id']:0;
            $customerId = $request->cust_id;//isset($data['customer_id'])?$data['customer_id']:0;
            $custId = $request->customer_id;//isset($data['cust_id'])?$data['cust_id']:0;
            $reserveId = $request->reserve_id;//isset($data['reserve_id'])?$data['reserve_id']:0;

            $user = \App\Models\User::where(['id'=> $customerId])->first();
            
            $record = \App\Models\BTiTransaction::where(['user_id' => $customerId,'reserve_id'=>$reserveId])->first();  //status:-1 : error, status:0 : reserve, status:1 : debit, status:2 : cancel,status:4 : commited reserve, 5: debit customer, 6: credit customer

            if(!isset($record) || !isset($user)){
                
                \DB::commit();
                $balance = 0;
                if(!isset($record)){
                    $balance = $user->balance;
                }
                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'ReserveID not exists ',
                    'balance' => sprintf('%0.2f',$balance)
                ]))->header('Content-Type', 'text/plain');
            }else{
                $sameRequest = false;
                $records = \App\Models\BTiTransaction::where(['user_id' => $customerId,'reserve_id'=>$reserveId])->get();
                foreach($records as $recordx){
                    if($recordx->status == 2){
                        $sameRequest = true;
                    }
                }
                if($sameRequest){
                    \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'No Error','amount' => 0,'balance' => $user->balance,'user_id' => $customerId,'reserve_id' => $reserveId,'data' => '','status' => 2]);
                
                    \DB::commit();
                    return response(BTIController::toText([
                        'error_code' => 0,
                        'error_message' => 'No Error',
                        'balance' => sprintf('%0.2f',$user->balance)
                    ]))->header('Content-Type', 'text/plain');
                }else{
                    if($record->status == 1){   //Debitted Reserve
                        \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'Already Debitted Reserve','amount' => $record->amount,'balance' => $user->balance,'user_id' => $customerId,'reserve_id' => $reserveId,'data'=>$record->data,'status' => 2,'bet_type_id'=>$record->bet_type_id,'bet_type_name'=>$record->bet_type_name]);
        
                        $user->balance = $user->balance + $record->amount;
                        $user->save();
                        \DB::commit();
                        return response(BTIController::toText([
                            'error_code' => 0,
                            'error_message' => 'Already Debitted Reserve',
                            'balance' => sprintf('%0.2f',$user->balance)
                        ]))->header('Content-Type', 'text/plain');
                    }else{  //cancel reserve action : status == 0
                        
                        $reserveBalance = $record->amount + $user->balance;
                        
                        \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'No Error','amount' => $record->amount,'balance' => $reserveBalance,'user_id' => $customerId,'reserve_id' => $reserveId,'data'=>$record->data,'status' => 2,'bet_type_id'=>$record->bet_type_id,'bet_type_name'=>$record->bet_type_name]);
        
                        $user->balance = $reserveBalance;
                        $user->save();
        
                        \DB::commit();
                        return response(BTIController::toText([
                            'error_code' => 0,
                            'error_message' => 'No Error',
                            'balance' => sprintf('%0.2f',$reserveBalance)
                        ]))->header('Content-Type', 'text/plain');
                        
                    }
                }

                
            }

            

        }

        public function debitreserve(\Illuminate\Http\Request $request){
            
            $xmlObject = simplexml_load_string($request->getContent());            
            $json = json_encode($xmlObject);
            $array = json_decode($json,true); 
            $custId = $request->cust_id;//$array['@attributes']['cust_id'];
            $reserveId = $request->reserve_id;//$array['@attributes']['reserve_id'];
            $amount = $request->amount;//$array['@attributes']['amount'];
            $reqId = $request->req_id;
            $purchaseId = $request->purchase_id;

            if(count($array['Bet'])>1){
                if(isset($array['Bet'][0])){
                    $betTypeId = $array['Bet'][0]['@attributes']['BetTypeID'];
                    $betTypeName = $array['Bet'][0]['@attributes']['BetTypeName'];
                }else{
                    $betTypeId = $array['Bet']['@attributes']['BetTypeID'];
                    $betTypeName = $array['Bet']['@attributes']['BetTypeName'];
                }
            }else{
                $betTypeId = $array['Bet']['@attributes']['BetTypeID'];
                $betTypeName = $array['Bet']['@attributes']['BetTypeName'];
            }

            $user = \App\Models\User::where(['id'=> $custId])->first();

            $record = \App\Models\BTiTransaction::where(['user_id' => $custId,'reserve_id'=>$reserveId])->get();

            if(count($record) == 0 || !isset($user)){
                $record_1 = \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'ReserveID Not Exist','amount' => 0,'balance' => 0,'user_id' => $custId,'reserve_id' => $reserveId,'data' => json_encode($array),'req_id'=>$reqId,'status' => -1,'bet_type_id'=>$betTypeId,'bet_type_name'=>$betTypeName,'purchase_id'=>$purchaseId]);
                $balance = 0;
                if(!isset($record)){
                    $balance = $user->balance;
                }
                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'ReserveID Not Exist',
                    'trx_id' => $record_1->id,
                    'balance' => sprintf('%0.2f',$record_1->balance)
                ]))->header('Content-Type', 'text/plain');
            }
            $canceledreserve = false;
            $commitedreserve = false;
            foreach($record as $recordx){
                if($recordx->status == 2){
                    $canceledreserve = true;
                }else if($recordx->status == 4){
                    $commitedreserve = true;
                }
            }
            $reserveRecord = \App\Models\BTiTransaction::where(['user_id' => $custId,'reserve_id'=>$reserveId,'status'=>0])->first();
            if($canceledreserve){
                \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'Already cancelled reserve','amount' => $reserveRecord->amount,'balance' => $reserveRecord->balance,'user_id' => $custId,'reserve_id' => $reserveId,'data' => json_encode($array),'req_id'=>$reqId,'status' => 2,'bet_type_id'=>$betTypeId,'bet_type_name'=>$betTypeName,'purchase_id'=>$purchaseId]);

                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'Already cancelled reserve',
                    'trx_id' => $reserveRecord->id,
                    'balance' => sprintf('%0.2f',$reserveRecord->balance)
                ]))->header('Content-Type', 'text/plain');
            }else if($commitedreserve){
                \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'Already committed reserve','amount' => $reserveRecord->amount,'balance' => $reserveRecord->balance,'user_id' => $custId,'reserve_id' => $reserveId,'data' => json_encode($array),'req_id'=>$reqId,'status' => 4,'bet_type_id'=>$betTypeId,'bet_type_name'=>$betTypeName,'purchase_id'=>$purchaseId]);

                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'Already committed reserve',
                    'trx_id' => $reserveRecord->id,
                    'balance' => sprintf('%0.2f',$reserveRecord->balance)
                ]))->header('Content-Type', 'text/plain');
            }

            $sameRequest = false;
            $debitRecords = \App\Models\BTiTransaction::where(['user_id' => $custId,'reserve_id'=>$reserveId,'status'=>1])->get();

            $sameBalance = 0;
            $sameId = 0;
            //$sameDebitRecord = \App\Models\BTiTransaction::where(['user_id' => $custId,'reserve_id'=>$reserveId,'status'=>1,'req_id'=>$reqId])->first();
            // if(isset($sameDebitRecord)){
            //     $sameRequest = true;
            //     $sameBalance = $sameDebitRecord->balance;
            //     $sameId = $sameDebitRecord->id;
            // }
            foreach($debitRecords as $debitRecord){
                if($debitRecord->req_id == $reqId){
                    $sameRequest = true;
                    $sameDebitRecord = \App\Models\BTiTransaction::where(['user_id' => $custId,'reserve_id'=>$reserveId,'status'=>0])->first();
                    $sameBalance = $sameDebitRecord->balance;
                    $sameId = $sameDebitRecord->id;
                    break;
                }
            }            


            if($sameRequest){

                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'No Error',
                    'trx_id' => $sameId,
                    'balance' => sprintf('%0.2f',$sameBalance)
                ]))->header('Content-Type', 'text/plain');
            }else{
                if($amount > $reserveRecord->amount && ($amount - $reserveRecord->amount >= 0.02)){

                    \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'Total DebitReserve amount larger than Reserve amount','amount' => $amount,'balance' => $user->balance,'user_id' => $custId,'reserve_id' => $reserveId,'req_id'=>$reqId,'data' => json_encode($array),'status' => -1,'bet_type_id'=>$betTypeId,'bet_type_name'=>$betTypeName,'purchase_id'=>$purchaseId]);
    
                    return response(BTIController::toText([
                        'error_code' => 0,
                        'error_message' => 'Total DebitReserve amount larger than Reserve amount',
                        'trx_id' => $reserveRecord->id,
                        'balance' => sprintf('%0.2f',$reserveRecord->balance)
                    ]))->header('Content-Type', 'text/plain');
                }
                $recBalance = $user->balance;
                
                \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'No Error','amount' => $amount,'balance' => $recBalance,'user_id' => $custId,'reserve_id' => $reserveId,'req_id'=>$reqId,'data' => json_encode($array),'status' => 1,'bet_type_id'=>$betTypeId,'bet_type_name'=>$betTypeName,'purchase_id'=>$purchaseId]);

                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'No Error',
                    'trx_id' => $reserveRecord->id,
                    'balance' => sprintf('%0.2f',$reserveRecord->balance)
                ]))->header('Content-Type', 'text/plain');
            }
            

            

        }

        public function commitreserve(\Illuminate\Http\Request $request){
            \DB::beginTransaction();
            $data = json_decode($request->getContent(), true);
            $agentId = $request->agent_id;// isset($data['agent_id'])?$data['agent_id']:0;
            $customerId = $request->cust_id;//isset($data['customer_id'])?$data['customer_id']:0;
            $custId = $request->customer_id;//isset($data['cust_id'])?$data['cust_id']:0;
            $reserveId = $request->reserve_id;//isset($data['reserve_id'])?$data['reserve_id']:0;
            $purchaseId = $request->purchase_id;//isset($data['purchase_id'])?$data['purchase_id']:0;

            $user = \App\Models\User::lockForUpdate()->where(['id'=> $customerId])->first();
            $debitamount = 0;
            $debitbased_Record = \App\Models\BTiTransaction::where(['user_id' => $customerId,'reserve_id'=>$reserveId,'status' => 1])->get(); 
            
            
            if(!isset($debitbased_Record) || !isset($user)){
                $balance = 0;
                if(!isset($debitbased_Record)){
                    $balance = $user->balance;
                }

                $record_1 = \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'ReserveID Not Exist','amount' => 0,'balance' => '0','user_id' => $customerId,'reserve_id' => $reserveId,'req_id'=>'0', 'status' => -1,'bet_type_id'=>'0','bet_type_name'=>'0']);
                
                \DB::commit();
                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'ReserveID Not Exists',
                    'trx_id' => $record_1->id,
                    'balance' => sprintf('%0.2f',$balance)
                ]))->header('Content-Type', 'text/plain');
            }

            $sameReserve = false;
            $reserveBased_Record = \App\Models\BTiTransaction::where(['user_id' => $customerId, 'reserve_id' => $reserveId,'status' => 0])->first();
            if(!$reserveBased_Record){
                \DB::commit();
                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'ReserveID Not Exists',
                    'balance' => sprintf('%0.2f',$user->balance)
                ]))->header('Content-Type', 'text/plain');
            }else{
                $reserveamount = $reserveBased_Record->amount;
                //$reserveBalance = $reserveBased_Record->balance;
                $reserveBalance = $user->balance;
                $betTypeID = '';
                $betTypeName = '';
                $betDate = '';
                $betReqId = '';
                $sameRecord = false;         
                foreach($debitbased_Record as $debitRecord){
                    $transData = json_decode($debitRecord->data,true);
                        //if($transData['Bet']['@attributes']['PurchaseBetID'] == $purchaseId){
                            $debitamount += $debitRecord->amount;
                            $betTypeID = $debitRecord->bet_type_id;
                            $betTypeName = $debitRecord->bet_type_name;
                            if(isset($transData['Bet']['Lines'])){
                                $betDate = $transData['Bet']['Lines'][0]['@attributes']['CreationDate'];
                            }else{
                                $betDate = $transData['Bet']['@attributes']['CreationDate'];
                            }
                            $newDateTime = new DateTime($betDate,new DateTimeZone('UTC'));
                            $newDateTime->setTimezone(new DateTimeZone('Asia/Seoul'));
                            $betDate = $newDateTime->format('Y-m-d H:i:s');
                            $betReqId = $debitRecord->req_id;
                        //}  
                }
    
                if($debitamount == $reserveamount + 0.01){
                    $debitamount = $reserveamount;
                }else{
                    if($debitamount > $reserveamount){
                        $record_1 = \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'Total DebitReserve amount larger than 
                        Reserve amount','amount' => $debitamount,'balance' => $user->balance,'user_id' => $customerId,'reserve_id' => $reserveId,'req_id'=>'0', 'status' => -1,'bet_type_id'=>'0','bet_type_name'=>'0']);
                
                        \DB::commit();
                        return response(BTIController::toText([
                            'error_code' => 0,
                            'error_message' => 'Total DebitReserve amount larger than 
                            Reserve amount',
                            'trx_id' => $record_1->id,
                            'balance' => sprintf('%0.2f',$user->balance)
                        ]))->header('Content-Type', 'text/plain');
                    }
                }
                $userbalance = ($reserveBalance + $reserveamount) - $debitamount;
                $record_1 = \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'No error','amount' => $reserveamount,'balance' => $userbalance,'user_id' => $customerId,'reserve_id' => $reserveId,'req_id'=>$betReqId,'data'=>$reserveBased_Record->data, 'status' => 4,'bet_type_id'=>$betTypeID,'bet_type_name'=>$betTypeName,'purchase_id'=>$purchaseId]);
    
                $user->balance = $userbalance;
                $user->save();
    
                \App\Models\StatGame::create([
                    'user_id' => $user->id, 
                    'balance' => $user->balance, 
                    'bet' => $debitamount, 
                    'win' => 0,
                    'game' =>  'sports', 
                    'type' => 'sports',
                    'percent' => 0, 
                    'percent_jps' => 0, 
                    'percent_jpg' => 0, 
                    'profit' => 0, 
                    'denomination' => 0, 
                    'shop_id' => $user->shop_id,
                    'category_id' => 61,
                    'game_id' => $reserveId,
                    'roundid' =>  $purchaseId,
                    'date_time' => $betDate,
                    'status' => 0
                ]);
    
                \DB::commit();
                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'No error',
                    'trx_id' => $record_1->id,
                    'balance' => sprintf('%0.2f',$userbalance)
                ]))->header('Content-Type', 'text/plain');
            }
            
            
        }


        public function debitcustomer(\Illuminate\Http\Request $request){
            \DB::beginTransaction();
            $data = json_decode($request->getContent(), true);
            $agentId = $request->agent_id;//isset($data['agent_id'])?$data['agent_id']:0;
            $customerId = $request->customer_id;//isset($data['customer_id'])?$data['customer_id']:0;
            $custId = $request->cust_id;//isset($data['cust_id'])?$data['cust_id']:0;
            $reqId = $request->req_id;//isset($data['req_id'])?$data['req_id']:0;
            $purchaseId = $request->purchase_id;//isset($data['purchase_id'])?$data['purchase_id']:0;
            

            $xmlObject = simplexml_load_string($request->getContent());            
            $json = json_encode($xmlObject);
            $array = json_decode($json,true); 

            $amount = $array['@attributes']['Amount'];
            $reserveId = $array['Purchases']['Purchase']['@attributes']['ReserveID'];
            
            $user = \App\Models\User::lockForUpdate()->where(['id'=> $custId])->first();

            $debitedBalance = 0;


            $record = \App\Models\BTiTransaction::where(['user_id' => $custId,'purchase_id'=>$purchaseId,'status'=>4])->first();   
            if(!isset($record) || !isset($user)){
                \DB::commit();
                $balance = 0;
                if(!isset($record)){
                    $balance = $user->balance;
                }
                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'ReserveID Not Exist',
                    'trx_id' => 0,
                    'balance' => sprintf('%0.2f',$balance)
                ]))->header('Content-Type', 'text/plain');
            }         

            $debitCustomerRecord = \App\Models\BTiTransaction::where(['user_id' => $custId,'req_id'=>$reqId,'purchase_id'=>$purchaseId])->first();            
            if($record){
                $betTypeId = intval($record->bet_type_id);
                $betTypeName = $record->bet_type_name;
            }else{
                $betTypeId = 0;
                $betTypeName = '';
            }
            $prevStatRecord = \App\Models\StatGame::where(['user_id' => $custId,'game_id'=>$reserveId,'roundid'=>$purchaseId,'status'=>1])->first();
            if(!$debitCustomerRecord || $debitCustomerRecord == null){
                
                $debitCustomerRecord = \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'No error','amount' => $amount,'balance' => $user->balance,'user_id' => $custId,'reserve_id' => $array['Purchases']['Purchase']['@attributes']['ReserveID'],'req_id'=>$reqId, 'data'=>json_encode($array),'status' => 5,'bet_type_id' => $betTypeId,'bet_type_name' => $betTypeName,'purchase_id' => $purchaseId]);               
                
                if(isset($prevStatRecord)){
                    $winmoney = $prevStatRecord->win + $amount;
                    $user->balance = $user->balance + $amount;
                    $debitedBalance = $user->balance;
                    $user->save();

                    $newDateTime = new DateTime($array['Purchases']['Purchase']['@attributes']['CreationDateUTC'],new DateTimeZone('UTC'));
                    $newDateTime->setTimezone(new DateTimeZone('Asia/Seoul'));
                    $dateTime = $newDateTime->format('Y-m-d H:i:s');

                    $prevStatRecord->update(['balance'=>$user->balance,'win' => $winmoney,'type'=>'sports','game_id'=>$reserveId,'roundid'=>$purchaseId,'date_time'=>$dateTime]);    
                    $debitCustomerRecord->update(['balance'=>$user->balance]) ;             
                }else{
                    $prevStatRecord = \App\Models\StatGame::where(['user_id' => $custId,'game_id'=>$reserveId,'roundid'=>$purchaseId,'status'=>0])->first();
                    $tempWinMoney = 0;
                    $creditRecords = \App\Models\BTiTransaction::where(['user_id' => $custId,'reserve_id'=>$reserveId,'status'=>6])->get();
                    if(isset($creditRecords)){
                        foreach($creditRecords as $creditRecord){
                            $tempWinMoney += $creditRecord->amount;
                        }                                                                
                    }
                    $tempWinMoney = $amount + $tempWinMoney;
    
    
                    $debitedBalance = $user->balance + $tempWinMoney;
                    $user->balance = $debitedBalance;
                    $user->save();
                    $debitCustomerRecord->update(['balance'=>$user->balance]) ;    
                    $dateTime = '';
                    if(isset($array['Purchases']['Purchase']['Selections']['Selection'][0])){
                        $dateTime = $array['Purchases']['Purchase']['Selections']['Selection'][0]['@attributes']['EventDateUTC'];
                    }else{
                        $dateTime = $array['Purchases']['Purchase']['Selections']['Selection']['@attributes']['EventDateUTC'];
                    }
                    $betDate = '';
                    $newDateTime = new DateTime($dateTime,new DateTimeZone('UTC'));
                    $newDateTime->setTimezone(new DateTimeZone('Asia/Seoul'));
                    $betDate = $newDateTime->format('Y-m-d H:i:s');
                        
                    $prevStatRecord->update(['balance'=>$user->balance,'win' => $tempWinMoney,'type'=>'sports','game_id'=>$reserveId,'roundid'=>$purchaseId,'date_time'=>$betDate,'status' => 1]);  
                                                          
                }         
                
            }else{
                $debitedBalance = $debitCustomerRecord->balance;
            }
            
            
            \DB::commit();
            return response(BTIController::toText([
                'error_code' => 0,
                'error_message' => 'No error',
                'trx_id' => $debitCustomerRecord->id,
                'balance' => sprintf('%0.2f',$debitedBalance)
            ]))->header('Content-Type', 'text/plain');
        }


        public function creditcustomer(\Illuminate\Http\Request $request){
            \DB::beginTransaction();
            $data = json_decode($request->getContent(), true);
            $agentId = $request->agent_id;//isset($data['agent_id'])?$data['agent_id']:0;
            $customerId = $request->customer_id;//isset($data['customer_id'])?$data['customer_id']:0;
            $custId = $request->cust_id;//isset($data['cust_id'])?$data['cust_id']:0;
            $reqId = $request->req_id;//isset($data['req_id'])?$data['req_id']:0;
            $purchaseId = $request->purchase_id;//isset($data['purchase_id'])?$data['purchase_id']:0;
            $amount = $request->amount;//isset($data['amount'])?$data['amount']:0;

            $xmlObject = simplexml_load_string($request->getContent());            
            $json = json_encode($xmlObject);
            $array = json_decode($json,true); 
            // $custId = $array['@attributes']['cust_id'];
             $reserveId = $array['Purchases']['Purchase']['@attributes']['ReserveID'];            
            $betTypeId = 0;
            $betTypeName = '';            

            $user = \App\Models\User::lockForUpdate()->where(['id'=> $custId])->first();

            $debitedBalance = 0;
            $sendTrxId = 0;

            $record = \App\Models\BTiTransaction::where(['user_id' => $custId,'purchase_id'=>$purchaseId,'status'=>4])->first();     

            if(!isset($record) || !isset($user)){
                \DB::commit();
                $balance = 0;
                if(!isset($record)){
                    $balance = $user->balance;
                }
                return response(BTIController::toText([
                    'error_code' => 0,
                    'error_message' => 'ReserveID Not Exist',
                    'trx_id' => 0,
                    'balance' => sprintf('%0.2f',$balance)
                ]))->header('Content-Type', 'text/plain');
            }  
            $debitCustomerRecord = \App\Models\BTiTransaction::where(['user_id' => $custId,'req_id'=>$reqId,'purchase_id'=>$purchaseId])->first();            
            if($record){
                $betTypeId = intval($record->bet_type_id);
                $betTypeName = $record->bet_type_name;
            }else{
                $betTypeId = 0;
                $betTypeName = '';
            }
            
            $winmoney = 0;

            if(!$debitCustomerRecord || $debitCustomerRecord == null){
                $betfinish = false;
                $winmoney = $amount;

                $debitCustomerRecord = \App\Models\BTiTransaction::create(['error_code' => 0,'error_message' => 'No error','amount' => $amount,'balance' => $debitedBalance,'user_id' => $custId,'reserve_id' => $reserveId,'req_id'=>$reqId, 'data'=>json_encode($array),'status' => 6,'bet_type_id' => $betTypeId,'bet_type_name' => $betTypeName,'purchase_id' => $purchaseId]);
                $sendTrxId = $debitCustomerRecord->id;
                
                $settledNumber = $array['Purchases']['Purchase']['@attributes']['NumberOfSettledLines'];
                $betlinesNumber = $array['Purchases']['Purchase']['@attributes']['NumberOfLines'];
                $lostNumber = $array['Purchases']['Purchase']['@attributes']['NumberOfLostLines'];
                $wonNumber = $array['Purchases']['Purchase']['@attributes']['NumberOfWonLines'];
                $actionType = '';
                if(isset($array['Purchases']['Purchase']['Selections']['Selection']['Changes']['Change']['Bets']['Bet']['@attributes']['ActionType'])){
                    $actionType = $array['Purchases']['Purchase']['Selections']['Selection']['Changes']['Change']['Bets']['Bet']['@attributes']['ActionType'];
                }
                $dateTime = '';
                $dateTime = $array['Purchases']['Purchase']['@attributes']['CreationDateUTC'];
                $betDate = '';
                $newDateTime = new DateTime($dateTime,new DateTimeZone('UTC'));
                $newDateTime->setTimezone(new DateTimeZone('Asia/Seoul'));
                $betDate = $newDateTime->format('Y-m-d H:i:s');

                $tempWinMoney = 0;
                $comboCase = false;
                $comboMoney = 0;
                $prevStatRecord = \App\Models\StatGame::where(['user_id' => $custId,'game_id'=>$reserveId,'roundid'=>$purchaseId,'status'=>1])->first();
                if($betTypeId == 2){
                    if($settledNumber == $betlinesNumber){
                        
                        if($actionType == 'Combo Bonus' || $actionType == 'Risk Free Bet'){                            
                            if(isset($prevStatRecord)){
                                $winmoney = $prevStatRecord->win + $amount;
                                $user->balance = $user->balance + $amount;
                                $user->save();
    
                               $prevStatRecord->update(['balance'=>$user->balance,'win' => $winmoney,'date_time'=>$betDate]);
                               
                                $winmoney = 0;
                            }else{
                                $winmoney = $amount;
                            }
                            
                        }else{
                            if($lostNumber > 0){
                                if(isset($prevStatRecord)){
                                    $winmoney = $prevStatRecord->win + $amount;
                                    $user->balance = $user->balance + $amount;
                                    $user->save();

                                    $prevStatRecord->update(['balance'=>$user->balance,'win' => $winmoney,'game_id'=>$reserveId,'date_time'=>$betDate]);
                                    
                                    $winmoney = 0;
                                }else{
                                    $betfinish = true;
                                    $winmoney = $amount;
                                }
                            }else if($wonNumber = $betlinesNumber){

                                if(isset($prevStatRecord)){
                                    $winmoney = $prevStatRecord->win + $amount;
                                    $user->balance = $user->balance + $amount;
                                    $user->save();

                                    $prevStatRecord->update(['balance'=>$user->balance,'win' => $winmoney,'game_id'=>$reserveId,'date_time'=>$betDate]);
                                    
                                    $winmoney = 0;
                                }else{
                                    $creditRecords = \App\Models\BTiTransaction::where(['user_id' => $custId,'purchase_id'=>$purchaseId,'status'=>6])->get();
                                    if(isset($creditRecords)){
                                        foreach($creditRecords as $creditRecord){
                                            if(strpos($creditRecord,'Combo Bonus')== false){
                                            }else{
                                                $comboCase = true;
                                            }                                    
                                        }
                                    }
                                    $winmoney = $amount;
                                    $betfinish = true;
                                }
                            }
                        }
                        $prevTransactionRecords = \App\Models\BTiTransaction::where(['user_id' => $custId,'reserve_id'=>$reserveId])->get();      
                        if(isset($prevTransactionRecords)){
                            foreach($prevTransactionRecords as $prevRecord){
                                $prevRecord->update(['stats'=>1]);
                            }
                        }
                    } else{
                        $winmoney = $amount;
                    }
                }else{
                    if( $array['Purchases']['Purchase']['@attributes']['CurrentStatus'] = 'Closed' && $betlinesNumber == $settledNumber){
                        
                        if(!$prevStatRecord){                                                    
                            $winmoney = $amount; 
                            $debitRecords = \App\Models\BTiTransaction::where(['user_id' => $custId,'purchase_id'=>$purchaseId,'status'=>5])->get();      
                            $debitWinMoney = 0;
                            if(isset($debitRecords)){
                                foreach($debitRecords as $debitRecord){
                                    $debitWinMoney += $debitRecord->amount;
                                }
                            }           
                            $winmoney = $winmoney + $debitWinMoney;
                            $betfinish = true;
                        }else{
                            $winmoney = $amount;
                            $user->balance = $user->balance + $winmoney;
                            $user->save();
                            $prevStatRecord->update(['balance'=>$user->balance,'win' => $prevStatRecord->win + $amount,'game_id'=>$reserveId,'date_time'=>$betDate]);
                            $winmoney = 0;
                        }

                        $prevTransactionRecords = \App\Models\BTiTransaction::where(['user_id' => $custId,'reserve_id'=>$reserveId])->get();      
                        if(isset($prevTransactionRecords)){
                            foreach($prevTransactionRecords as $prevRecord){
                                $prevRecord->update(['stats'=>1]);
                            }
                        }
                        
                    }else{
                        if(isset($prevStatRecord)){
                            $winmoney = $amount;
                            $user->balance = $user->balance + $winmoney;
                            $user->save();
                            if($amount != 0){
                                $prevStatRecord->update(['balance'=>$user->balance,'win' => $prevStatRecord->win + $amount,'game_id'=>$reserveId,'date_time'=>$betDate]);    
                            }                                                        
                            $winmoney = 0;
                        }else{
                            $winmoney = $amount;
                        }
                    }
                }

                $debitedBalance = $user->balance + $winmoney;
                $user->balance = $debitedBalance;
                $user->save();
                $debitCustomerRecord->update(['balance'=>$debitedBalance]);
                
                if($betfinish == true){   //winMoney가 0이 아닌경우에만 stat_game에 저장(win/lost에서 win이 0으로 들어오는 경우)
                    $prevStatRecord = \App\Models\StatGame::where(['user_id' => $custId,'game_id'=>$reserveId,'roundid'=>$purchaseId])->first();
                    
                    // $user->balance = $debitedBalance;
                    // $user->save();
                    $transactionRecords = \App\Models\BTiTransaction::where(['user_id' => $custId,'reserve_id'=>$reserveId,'purchase_id'=>$purchaseId,'status'=>6])->get(); 
                    $winmoney = 0;  
                    if(isset($transactionRecords)){
                        foreach($transactionRecords as $transactionRecord){
                            $winmoney += $transactionRecord->amount;
                        }
                    }  
                    $debitRecords = \App\Models\BTiTransaction::where(['user_id' => $custId,'reserve_id'=>$reserveId,'purchase_id'=>$purchaseId,'status'=>5])->get();      
                    $debitWinMoney = 0;
                    if(isset($debitRecords)){
                        foreach($debitRecords as $debitRecord){
                            $debitWinMoney += $debitRecord->amount;
                        }
                    }           
                    $winmoney = $winmoney + $debitWinMoney;
                    
                    if($comboCase == true){
                        $winmoney = $winmoney + $comboMoney;
                    }
                    $prevStatRecord->update(['balance'=>$user->balance,'win' => $winmoney,'game_id'=>$reserveId,'date_time'=>$betDate,'status' => 1]);
                }                
            }else{
                $debitedBalance = $user->balance;
                $sendTrxId = $debitCustomerRecord->id;
            }
            $sendBalance = sprintf('%0.2f', $debitedBalance);
            \DB::commit();
            return response(BTIController::toText([
                'error_code' => 0,
                'error_message' => 'No error',
                'trx_id' => $sendTrxId,
                'balance' => sprintf('%0.2f',$sendBalance)
            ]))->header('Content-Type', 'text/plain');
        }

        public function sendSession(\Illuminate\Http\Request $request){
            $data = json_decode($request->getContent(), true);
            //$userToken = isset($data['token'])?$data['token']:'';
            $userToken = $request->token;

            $userRecord = \App\Models\User::where(['api_token' => $userToken])->first();
            $status = 'success';
            $balance = 0;
            if(!$userRecord || $userRecord == null){
                $status = 'failure';
                return response()->json([
                    'status' => $status,
                    'balance' => sprintf('%0.2f',0)
                ])->header('Access-Control-Allow-Origin','*');
            }
            $balance = $userRecord->balance;
            $sendBalance = sprintf('%0.2f', $balance);
            return response()->json([
                'status' => $status,
                'balance' => sprintf('%0.2f',$sendBalance)
            ])->header('Access-Control-Allow-Origin','*');
        }


        public static function getgamelink($gamecode)
        {
            $detect = new \Detection\MobileDetect();

            if ($detect->isiOS() || $detect->isiPadOS())
            {
                // $url = BTIController::makegamelink();
                $url = '/spbt1/golobby?code=' . $gamecode;
            }
            else
            {
                $url = '/spbt1/golobby?code=' . $gamecode;
            }
            if ($url)
            {
                return ['error' => false, 'data' => ['url' => $url]];
            }
            return ['error' => true, 'msg' => '로그인하세요'];
        }

        public function embedGACgame(\Illuminate\Http\Request $request)
        {
            $url = BTIController::makegamelink();
            if ($url)
            {
                return view('frontend.Default.games.apigame',compact('url'));
            }
            else
            {
                abort(404);
            }
        }

        public static function makegamelink()
        {
            $user = auth()->user();
            $token = $user->remember_token;
            $url = config('app.bti_api') . '/?operatorToken='. $token;
            return $url;
        }


        public static function getgamedetail(\App\Models\StatGame $stat){
        // public static function getgamedetail(){
        //     $stat = \App\Models\StatGame::where(['user_id'=>23,'game_id'=>485359016982700032])->first();

            $bet_string = [
                '싱글',	
                '더블',	
                '트레블',	
                '트릭시',
                '페이턴트', 
                '얀키',
                '럭키 15',
                '슈퍼 얀키',
                '럭키 31',
                '하인츠',
                '럭키 63',
                '슈퍼 하인츠',
                '골리앗'               
            ]; 
            $bettype_string = [
                'Single bets',
                'Doubles X 1 bet',
                'Trebles X 1 bet',
                'Trixie',
                'Patent',
                'Yankee',
                'Lucky15',
                'SuperYankee',
                'Lucky31',
                'Heinz',
                'Lucky63',
                'SuperHeinz',
                'Goliath'
            ];           
            $gametype = 'BTISports';
            $reserveId = explode('_',$stat->game_id)[0];;
            $reqId = $stat->roundid;
            $user = $stat->user;
            $gainMoney = 0;
            
            if (!$user)
            {
                return null;
            }

            $waitRecord = false;
            $btiRecords = \App\Models\BTiTransaction::where(['user_id'=>$user->id,'reserve_id'=>$reserveId])->get();
            
            $responseData = [];
            $count=0;
            $reqId = '';
            if(count($btiRecords)>0){
                foreach($btiRecords as $btiRecord){
                    if($btiRecord->status > 3){
                        //if($reqId != $btiRecord->req_id){
                            if($btiRecord->status == 4){
                                $array = json_decode($btiRecord->data,true);
                                $reqId = $btiRecord->req_id;
                                $responseData[0][$count] = $array;
                            }else if($btiRecord->status == 5){
                                $array = json_decode($btiRecord->data,true);
                                $reqId = $btiRecord->req_id;
                                $responseData[1][$count] = $array;
                                $gainMoney += $btiRecord->amount;
                            }else if($btiRecord->status == 6){
                                $array = json_decode($btiRecord->data,true);
                                $reqId = $btiRecord->req_id;
                                $responseData[2][$count] = $array;
                                $gainMoney += $btiRecord->amount;
                            }
                            $count++;
                        //}
                    }
                }
                $stat = [];
                $result = [];
                $bets = [];
                $betInfo = [];
                //if(!isset($responseData[1]) && !isset($responseData[2])){
                    //array_push($stat,'대기');
                    $tempArry = [];
                    if(count($responseData[0][0]['Bet'])>1){
                        if(isset($responseData[0][0]['Bet'][0]))
                        {
                            for($i = 0;$i<count($responseData[0][0]['Bet']);$i++){
                                $tempArry = $responseData[0][0]['Bet'][$i]['@attributes'];
                                $result[$i] = BTIController::getCommitInfo($tempArry,$bettype_string,$bet_string);
                            }
                        }else if(isset($responseData[0][0]['Bet']['Lines'])){
                            for($i = 0;$i<count($responseData[0][0]['Bet']['Lines']);$i++){
                                $tempArry = $responseData[0][0]['Bet']['Lines'][$i]['@attributes'];
                                $result[$i] = BTIController::getCommitInfo($tempArry,$bettype_string,$bet_string);
                            }
                        }
                        
                        
                    }else{
                        $tempArry = $responseData[0][0]['Bet']['@attributes'];
                        $result[0] = BTIController::getCommitInfo($tempArry,$bettype_string,$bet_string);
                    }
                    
                //}else 
                if(isset($responseData[2])){
                    for($i=1;$i<=count($responseData[2]);$i++){
                        $tempArry = [];
                        $tempArry = $responseData[2][$i]['Purchases'];
                        $betType = '';
                        if(isset($tempArry['Purchase']['Selections']['Selection'][0])){
                            for($j=0;$j<count($tempArry['Purchase']['Selections']['Selection']);$j++){
                                if(isset($tempArry['Purchase']['Selections']['Selection'][$j]['Changes']['Change']['Bets']['Bet'][0])){
                                    $result = BTIController::getCreditInfo($tempArry['Purchase']['Selections']['Selection'][$j],$bet_string,$bettype_string);
                                }else{
                                    $result[$j] = BTIController::getCreditInfo($tempArry['Purchase']['Selections']['Selection'][$j],$bet_string,$bettype_string);
                                }
                                
                            }
                            
                        }else{
                            $result[$i - 1] = BTIController::getCreditInfo($tempArry['Purchase']['Selections']['Selection'],$bet_string,$bettype_string);
                        }
                        
                    }
                }
                if(isset($responseData[1])){
                    
                    $debitStartNumber = count($responseData[2]);
                    for($i=1;$i<=count($responseData[2]);$i++){
                        $tempArry = [];
                        $tempArry = $responseData[2][$i]['Purchases'];
                        $betType = '';
                        if(isset($tempArry['Purchase']['Selections']['Selection'][0])){
                            $result[$i - 1] = BTIController::getDebitInfo($tempArry['Purchase']['Selections']['Selection'][0],$bet_string,$bettype_string);
                        }else{
                            $result[$i - 1] = BTIController::getDebitInfo($tempArry['Purchase']['Selections']['Selection'],$bet_string,$bettype_string);
                        }
                        
                    }
                    
                    $tempResult = [];
                    for($i=1;$i<=count($responseData[1]);$i++){
                        $tempArry = [];
                        $tempArry = $responseData[1][$i + $debitStartNumber]['Purchases'];
                        $betType = '';
                        if(isset($tempArry['Purchase']['Selections']['Selection'][0])){
                            $tempResult[$i - 1] = BTIController::getDebitInfo($tempArry['Purchase']['Selections']['Selection'][0],$bet_string,$bettype_string);
                        }else{
                            $tempResult[$i - 1] = BTIController::getDebitInfo($tempArry['Purchase']['Selections']['Selection'],$bet_string,$bettype_string);
                        }
                    }
    
                    $tempGame = '';
                    $tempValue = '';
                    $tempValues = [];
                    $tempGameValue = '';
                    for($i=0;$i<count($result);$i++){
                        for($j=0;$j<count($tempResult);$j++){
                            if($result[$i]['game'] == $tempResult[$j]['game']){
                                if(strpos($result[$i]['stat'],'Combo Bonus') == false){
                                    $tempValues = [];
                                    if(strpos('콤보',$result[$i]['stat'])){
                                        if(explode('(',$result[$i]['stat'])[0] > 0){
                                            $tempGameValue =  explode('(',$tempResult[$j]['stat'])[0] + explode('(',$result[$i]['stat'])[0];
                                            $result[$i]['stat'] = $tempGameValue;
                                        }
                                    }else{
                                        $tempGameValue =  explode('(',$tempResult[$j]['stat'])[0];
                                            $result[$i]['stat'] = $tempGameValue;
                                    }
    
                                    // $tempGameValue =  explode('(',$tempResult[$j]['stat'])[0] + explode('(',$result[$i]['stat'])[0];
                                    // $result[$i]['stat'] = $tempGameValue . '(LOST)';
                                    
                                    //$result[$i] = $tempResult[$j];
                                    
                                    $tempGame = $result[$i]['game']; 
                                    $tempValues = explode('(',$result[$i]['stat']);
    
                                    $tempValue = $tempValues[0];
                                }else{
                                    $tempValues = [];
                                    if($result[$i]['game'] == $tempResult[$j]['game']){
                                        $tempGameValue =  explode('(',$tempResult[$j]['stat'])[0] + explode('(',$result[$i]['stat'])[0];
                                        $tempResult[$j]['stat'] = $tempGameValue . '(Combo Bonus)';
                                    }
                                    $result[$i] = $tempResult[$j];
                                }                   
                                
                            }
                        }
                    }
                }
                $commitRecord = \App\Models\BTiTransaction::where(['user_id'=>$user->id,'reserve_id'=>$reserveId,'status'=>4])->first();
                $pur_id = $commitRecord->purchase_id;
                $bettype_temp = '';
                $statString = '';
                $betTypeName = '';
                $tempBetArray = [];
                if(count($responseData[0][0]['Bet'])>1){
                    if(isset($responseData[0][0]['Bet'][0]))
                    {
                        $tempBetArray = $responseData[0][0]['Bet'][0]['@attributes'];
                    }else{
                        $tempBetArray = $responseData[0][0]['Bet']['@attributes'];
                    }
                }else{
                    $tempBetArray = $responseData[0][0]['Bet']['@attributes'];
                }
                if($tempBetArray['BetTypeID'] == 1){
                    $betTypeName = '싱글';
                }else if($tempBetArray['BetTypeID'] == 2){
                    $betTypeName = '콤보 배팅';
                }else if($tempBetArray['BetTypeID'] == 3){
                    $betTypeName = '시스템 배팅';
                }
                if($tempBetArray['BetTypeID'] == "1" ){
                    $bettype_temp = '싱글 배팅';
                }else{
                    if(strpos($tempBetArray['BetTypeName'],'System')){                            
                        $statString = '시스템' . explode(' ',$tempBetArray['BetTypeName'])[1];
                    }else if(strpos($tempBetArray['BetTypeName'],'folds')){
                        $statString = explode(' ',$tempBetArray['BetTypeName'])[0] . '폴드';
                    }else{
                        for($j=0;$j<count($bettype_string);$j++){
                            if($bettype_string[$j] == $tempBetArray['BetTypeName']){
                                $statString = $bet_string[$j];
                            }
                        }
                    }
                    $bettype_temp = $betTypeName . ' ' . $statString;
                }
                $betDate = '';
                $newDateTime = new DateTime($tempBetArray['CreationDate'],new DateTimeZone('UTC'));
                $newDateTime->setTimezone(new DateTimeZone('Asia/Seoul'));
                $betDate = $newDateTime->format('Y-m-d H:i:s');
                
                $betInfo = [
                    'pur_id' =>$pur_id,
                    'date' => $betDate,
                    'bet_type' => $bettype_temp,
                    'bet_money' => $tempBetArray['RealAmount'],
                    'award_money' => $gainMoney,
                    'odd' => $tempBetArray['OddsDec']
                ];
    
                return [
                    'type' => $gametype,
                    'betinfo' => $betInfo,
                    'result' => $result
                ];
            }
            return [
                'type' => '',
                'betinfo' => '',
                'result' => ''
            ];

        }
        public static function getCommitInfo($tempArry,$bettype_string,$bet_string){
            $betType = '';
            $statString = '';
            if($tempArry['BetTypeID'] == 1){
                $betType = '싱글';
            }else if($tempArry['BetTypeID'] == 2){
                $betType = '콤보';
            }else if($tempArry['BetTypeID'] == 3){
                $betType = '시스템';
            }
            if(strpos($tempArry['BetTypeName'],'System')){                            
                $statString = '시스템' . explode(' ',$tempArry['BetTypeName'])[1];
            }else if(strpos($tempArry['BetTypeName'],'folds')){
                $statString = explode(' ',$tempArry['BetTypeName'])[0] . '폴드';
            }else{
                for($j=0;$j<count($bettype_string);$j++){
                    if($bettype_string[$j] == $tempArry['BetTypeName']){
                        $statString = $bet_string[$j];
                    }
                }
            }
            $betDate = '';
            $newDateTime = new DateTime($tempArry['EventDate'],new DateTimeZone('UTC'));
            $newDateTime->setTimezone(new DateTimeZone('Asia/Seoul'));
            $betDate = $newDateTime->format('Y-m-d H:i:s');
            
            $result = [
                'date' => $betDate,
                'odd'  => $tempArry['OddsDec'],
                'yourbet' => $tempArry['YourBet'],
                'market'  => $tempArry['EventTypeName'],
                'branchname' => $tempArry['BranchName'],
                'leaguename' => $tempArry['LeagueName'],
                'game'  => $tempArry['HomeTeam'] . ' vs ' . $tempArry['AwayTeam'],
                'fulltimescore' => '',
                'stat'   =>  '대기'               
            ];
            return $result;
        }

        public static function getCreditInfo($tempArry,$bet_string,$bettype_string){
            $betType = '';
            $statString = '';
            $statArray = [
                'Opened',
                'Won',
                'Lost',
                'Half Won',
                'Half Lost',
                'Canceled',
                'Cashout',
                'Draw'
            ];
            $statArray_string = [
                '대기',
                '적중',
                '미적중',
                '하프 승',
                '하프 패',
                '취소',
                '캐시아웃',
                '무승부'
            ];
            $fulltimeScore = '';
            if(isset($tempArry['@attributes']['CurrentResult'])){
                if($tempArry['@attributes']['CurrentResult'] == 'null' || $tempArry['@attributes']['CurrentResult'] == ''){
                    $fulltimeScore = '';
                }else{
                    $fulltimeScore = $tempArry['@attributes']['CurrentResult'];
                }
            }
            
            if(isset($tempArry['Changes']['Change']['Bets']['Bet'][0])){
                if($tempArry['Changes']['Change']['Bets']['Bet'][0]['@attributes']['BetTypeID'] == 1){
                    $betType = '싱글';
                }else if($tempArry['Changes']['Change']['Bets'][0]['Bet']['@attributes']['BetTypeID'] == 2){
                    $betType = '콤보';
                }else if($tempArry['Changes']['Change']['Bets'][0]['Bet']['@attributes']['BetTypeID'] == 3){
                    $betType = '시스템';
                }
                $tempStat = '';
                $result = [];
                for($i = 0;$i<count($tempArry['Changes']['Change']['Bets']['Bet']);$i++){
                    if(isset($tempArry['Changes']['Change']['Bets']['Bet'][$i]['@attributes']['ActionType'])){
                        if($tempArry['Changes']['Change']['Bets']['Bet'][$i]['@attributes']['ActionType'] == 'Combo Bonus'){
                            $tempStat = '콤보 보너스';
                        }else if($tempArry['Changes']['Change']['Bets']['Bet'][$i]['@attributes']['ActionType'] == 'Partial Cash Out'){
                            $tempStat = 'Partial Cash Out';
                        }else{
                            $tempStat = '';
                        }
                    }else{
                        $unnormal = false;
                        for($j=0;$j<count($statArray);$j++){
                            if($tempArry['Changes']['Change']['@attributes']['NewStatus'] == $statArray[$j]){
                                $tempStat = $statArray_string[$j];
                            }else{
                                $unnormal = true;
                            }
                        }
                        if($unnormal == true && $tempStat == ''){
                            $tempStat = '';
                        }
                    }
                    if(strpos($tempArry['Changes']['Change']['Bets']['Bet'][$i]['@attributes']['BetType'],'System')){                            
                        $statString = '시스템' . explode(' ',$tempArry['Changes']['Change']['Bets']['Bet'][$i]['@attributes']['BetType'])[1];
                    }else if(strpos($tempArry['Changes']['Change']['Bets']['Bet'][$i]['@attributes']['BetType'],'folds')){
                        $statString = explode(' ',$tempArry['Changes']['Change']['Bets']['Bet'][$i]['@attributes']['BetType'])[0] . '폴드';
                    }else{
                        for($j=0;$j<count($bettype_string);$j++){
                            if($bettype_string[$j] == $tempArry['Changes']['Change']['Bets']['Bet'][$i]['@attributes']['BetType']){
                                $statString = $bet_string[$j];
                            }
                        }
                    }

                    $betDate = '';
                    $newDateTime = new DateTime($tempArry['Changes']['Change']['@attributes']['DateUTC'],new DateTimeZone('UTC'));
                    $newDateTime->setTimezone(new DateTimeZone('Asia/Seoul'));
                    $betDate = $newDateTime->format('Y-m-d H:i:s');

                    $result[$i] = [
                        'date' => $betDate,
                        'odd'  => $tempArry['@attributes']['OddsInUserStyle'],
                        'yourbet' => $tempArry['@attributes']['YourBet'],
                        'market'  => $tempArry['@attributes']['EventTypeName'],
                        'branchname' => $tempArry['@attributes']['BranchName'],
                        'leaguename' => $tempArry['@attributes']['LeagueName'],
                        'game'  => $tempArry['@attributes']['HomeTeam'] . ' vs ' . $tempArry['@attributes']['AwayTeam'],
                        'fulltimescore' => $fulltimeScore,
                        'stat'  => $tempStat
                    ];
                }
                return $result;
                
            }else{
                if($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetTypeID'] == 1){
                    $betType = '싱글';
                }else if($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetTypeID'] == 2){
                    $betType = '콤보';
                }else if($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetTypeID'] == 3){
                    $betType = '시스템';
                }
                $tempStat = '';
                if(isset($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['ActionType'])){
                    if($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['ActionType'] == 'Combo Bonus'){
                        $tempStat = '콤보 보너스';
                    }else if($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['ActionType'] == 'Partial Cash Out'){
                        $tempStat = 'Partial Cash Out';
                    }else{
                        $tempStat = '';
                    }
                }else{
                    $unnormal = false;
                    for($j=0;$j<count($statArray);$j++){
                        if($tempArry['Changes']['Change']['@attributes']['NewStatus'] == $statArray[$j]){
                            $tempStat = $statArray_string[$j];
                        }else{
                            $unnormal = true;
                        }
                    }
                    if($unnormal == true && $tempStat == ''){
                        $tempStat = '';
                    }
                }
                
                if(strpos($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType'],'System')){                            
                    $statString = '시스템' . explode(' ',$tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType'])[1];
                }else if(strpos($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType'],'folds')){
                    $statString = explode(' ',$tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType'])[0] . '폴드';
                }else{
                    for($j=0;$j<count($bettype_string);$j++){
                        if($bettype_string[$j] == $tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType']){
                            $statString = $bet_string[$j];
                        }
                    }
                }

                $betDate = '';
                $newDateTime = new DateTime($tempArry['Changes']['Change']['@attributes']['DateUTC'],new DateTimeZone('UTC'));
                $newDateTime->setTimezone(new DateTimeZone('Asia/Seoul'));
                $betDate = $newDateTime->format('Y-m-d H:i:s');
                
                $result = [
                    'date' => $betDate,
                    'odd'  => $tempArry['@attributes']['OddsInUserStyle'],
                    'yourbet' => $tempArry['@attributes']['YourBet'],
                    'market'  => $tempArry['@attributes']['EventTypeName'],
                    'branchname' => $tempArry['@attributes']['BranchName'],
                    'leaguename' => $tempArry['@attributes']['LeagueName'],
                    'game'  => $tempArry['@attributes']['HomeTeam'] . ' vs ' . $tempArry['@attributes']['AwayTeam'],
                    'fulltimescore' => $fulltimeScore,
                    'stat'  => $tempStat
                ];
                return $result;
            }
        }

        public static function getDebitInfo($tempArry,$bet_string,$bettype_string){
            $betType = '';
            $statArray = [
                'Opened',
                'Won',
                'Lost',
                'Half Won',
                'Half Lost',
                'Canceled',
                'Cashout',
                'Draw'
            ];
            $statArray_string = [
                '대기',
                '적중',
                '미적중',
                'Half Won',
                'Half Lost',
                '취소',
                '캐시아웃',
                '무승부'
            ];
            
            $fulltimeScore = '';
            if(isset($tempArry['@attributes']['CurrentResult'])){
                if($tempArry['@attributes']['CurrentResult'] == 'null' || $tempArry['@attributes']['CurrentResult'] == ''){
                    $fulltimeScore = '';
                }else{
                    $fulltimeScore = $tempArry['@attributes']['CurrentResult'];
                }
            }
            
            
            if(isset($tempArry['Changes']['Change']['Bets']['Bet'][0])){
                if($tempArry['Changes']['Change']['Bets']['Bet'][0]['@attributes']['BetTypeID'] == 1){
                    $betType = '싱글';
                }else if($tempArry['Changes']['Change']['Bets']['Bet'][0]['@attributes']['BetTypeID'] == 2){
                    $betType = '콤보';
                }else if($tempArry['Changes']['Change']['Bets']['Bet'][0]['@attributes']['BetTypeID'] == 3){
                    $betType = '시스템';
                }

                $tempStat = '';
                for($i = 0;$i<count($tempArry['Changes']['Change']['Bets']['Bet']);$i++){
                    if(isset($tempArry['Changes']['Change']['Bets']['Bet'][$i]['@attributes']['ActionType'])){
                        if($tempArry['Changes']['Change']['Bets']['Bet'][$i]['@attributes']['ActionType'] == 'Combo Bonus'){
                            $tempStat = '콤보 보너스';
                        }else if($tempArry['Changes']['Change']['Bets']['Bet'][$i]['@attributes']['ActionType'] == 'Partial Cash Out'){
                            $tempStat = 'Partial Cash Out';
                        }else{
                            $tempStat = '';
                        }
                    }else{
                        $unnormal = false;
                        for($j=0;$j<count($statArray);$j++){
                            if($tempArry['Changes']['Change']['@attributes']['NewStatus'] == $statArray[$j]){
                                $tempStat = $statArray_string[$j];
                            }else{
                                $unnormal = true;
                            }
                        }
                        if($unnormal == true && $tempStat == ''){
                            $tempStat = '';
                        }
                    }
                }
                if(strpos($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType'],'System')){                            
                    $statString = '시스템' . explode(' ',$tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType'])[1];
                }else if(strpos($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType'],'folds')){
                    $statString = explode(' ',$tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType'])[0] . '폴드';
                }else{
                    for($j=0;$j<count($bettype_string);$j++){
                        if($bettype_string[$j] == $tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType']){
                            $statString = $bet_string[$j];
                        }
                    }
                }

                $betDate = '';
                $newDateTime = new DateTime($tempArry['Changes']['Change']['@attributes']['DateUTC'],new DateTimeZone('UTC'));
                $newDateTime->setTimezone(new DateTimeZone('Asia/Seoul'));
                $betDate = $newDateTime->format('Y-m-d H:i:s');

                $result = [
                    'date' => $betDate,
                    'odd'  => $tempArry['@attributes']['OddsInUserStyle'],
                    'yourbet' => $tempArry['@attributes']['YourBet'],
                    'market'  => $tempArry['@attributes']['EventTypeName'],
                    'branchname' => $tempArry['@attributes']['BranchName'],
                    'leaguename' => $tempArry['@attributes']['LeagueName'],
                    'game'  => $tempArry['@attributes']['HomeTeam'] . ' vs ' . $tempArry['@attributes']['AwayTeam'],
                    'fulltimescore' => $fulltimeScore,
                    'stat'   => $tempStat
                ];
            }else{
                if($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetTypeID'] == 1){
                    $betType = '싱글';
                }else if($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetTypeID'] == 2){
                    $betType = '콤보';
                }else if($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetTypeID'] == 3){
                    $betType = '시스템';
                }
                $tempStat = '';
                if(isset($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['ActionType'])){
                    if($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['ActionType'] == 'Combo Bonus'){
                        $tempStat = '콤보 보너스';
                    }else if($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['ActionType'] == 'Partial Cash Out'){
                        $tempStat = 'Partial Cash Out';
                    }else{
                        $tempStat = '';
                    }
                }else{
                    $unnormal = false;
                    for($j=0;$j<count($statArray);$j++){
                        if($tempArry['Changes']['Change']['@attributes']['NewStatus'] == $statArray[$j]){
                            $tempStat = $statArray_string[$j];
                        }else{
                            $unnormal = true;
                        }
                    }
                    if($unnormal == true && $tempStat == ''){
                        $tempStat = '';
                    }
                }
                if(strpos($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType'],'System')){                            
                    $statString = '시스템' . explode(' ',$tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType'])[1];
                }else if(strpos($tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType'],'folds')){
                    $statString = explode(' ',$tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType'])[0] . '폴드';
                }else{
                    for($j=0;$j<count($bettype_string);$j++){
                        if($bettype_string[$j] == $tempArry['Changes']['Change']['Bets']['Bet']['@attributes']['BetType']){
                            $statString = $bet_string[$j];
                        }
                    }
                }

                $betDate = '';
                $newDateTime = new DateTime($tempArry['Changes']['Change']['@attributes']['DateUTC'],new DateTimeZone('UTC'));
                $newDateTime->setTimezone(new DateTimeZone('Asia/Seoul'));
                $betDate = $newDateTime->format('Y-m-d H:i:s');

                $result = [
                    'date' => $betDate,
                    'odd'  => $tempArry['@attributes']['OddsInUserStyle'],
                    'yourbet' => $tempArry['@attributes']['YourBet'],
                    'market'  => $tempArry['@attributes']['EventTypeName'],
                    'branchname' => $tempArry['@attributes']['BranchName'],
                    'leaguename' => $tempArry['@attributes']['LeagueName'],
                    'game'  => $tempArry['@attributes']['HomeTeam'] . ' vs ' . $tempArry['@attributes']['AwayTeam'],
                    'fulltimescore' => $fulltimeScore,
                    'stat'   => $tempStat
                ];
            }
            
            

            return $result;
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
    }
}

