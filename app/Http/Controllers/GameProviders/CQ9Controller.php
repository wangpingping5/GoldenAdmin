<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class CQ9Controller extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */
        const LIVECODE = 'CA01';
        function validRFC3339Date($date) {
            if (preg_match('/^([0-9]+)-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])[Tt]([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]|60)(\.[0-9]+)?(([Zz])|([\+|\-]([01][0-9]|2[0-3]):[0-5][0-9]))$/', $date)) {
                return true;
            } else {
                return false;
            }
        }

        public function checkmtcode($mtcode)
        {
            $record = \App\Models\CQ9Transaction::where('mtcode',$mtcode)->get()->first();
            return $record;
        }

        public function generateCode($limit){
            $code = 0;
            for($i = 0; $i < $limit; $i++) { $code .= mt_rand(0, 9); }
            return $code;
        }

        public static function gamecodetoname($code)
        {
            $gamelist = CQ9Controller::getgamelist('cq9');
            $gamelive = CQ9Controller::getgamelist('cqlive');
            if ($gamelive){
                $gamelist = array_merge_recursive($gamelist, $gamelive);
            }
            $gamename = $code;
            if ($gamelist)
            {
                foreach($gamelist as $game)
                {
                    if ($game['gamecode'] == $code)
                    {
                        $gamename = $game['name'];
                        break;
                    }
                }
            }
            return $gamename;
        }

        public static function gamenametocode($name)
        {
            $gamelist = CQ9Controller::getgamelist('cq9');
            $gamelive = CQ9Controller::getgamelist('cqlive');
            if ($gamelive){
                $gamelist = array_merge_recursive($gamelist, $gamelive);
            }

            $gamecode = $name = preg_replace('/\s+/', '', $name);
            if ($gamelist)
            {
                foreach($gamelist as $game)
                {
                    if ($game['name'] == $name)
                    {
                        $gamecode = $game['gamecode'];
                        break;
                    }
                }
            }
            return $gamecode;
        }
        public function microtime_string()
        {
            $microstr = sprintf('%.4f', microtime(TRUE));
            $microstr = str_replace('.', '', $microstr);
            return $microstr;
        }

        /*
        * FROM CQ9, BACK API
        */
        public function bet(\Illuminate\Http\Request $request)
        {
            $account = $request->account;
            $eventTime = $request->eventTime;
            $gamehall = $request->gamehall;
            $gamecode = $request->gamecode;
            $roundid = $request->roundid;
            $amount = $request->amount;
            $mtcode = $request->mtcode;
            $session = $request->session;
            $platform = $request->platform;

            if (!$account || !$eventTime || !$gamehall || !$gamecode || !$roundid || !$amount || !$mtcode || $amount<0){
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1003',
                        'message' => 'incorrect parameter',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }

            if (!$this->validRFC3339Date($eventTime))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1004',
                        'message' => 'wrong time format',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            \DB::beginTransaction();

            if ($this->checkmtcode($mtcode))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '2009',
                        'message' => 'Duplicate MTCode.',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }


            $transaction = [
                '_id' => $this->generateCode(24),
                'action' => 'bet',
                'target' => [
                    'account' => $account,
                ],
                'status' => [
                    'createtime' => date(DATE_RFC3339_EXTENDED),
                    'endtime' => date(DATE_RFC3339_EXTENDED),
                    'status' => 'success',
                    'message' => 'success'
                ],
                'before' => 0,
                'balance' => 0,
                'currency' => 'KRW',

                'event' => [
                    [
                        'mtcode' => $mtcode,
                        'amount' => floatval($amount),
                        'eventtime' => $eventTime,
                    ]
                ]
            ];


            $user = \App\Models\User::lockForUpdate()->where('id',$account)->get()->first();
            if (!$user || !$user->hasRole('user') || $user->remember_token != $user->api_token){
                $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
                $transaction['status']['status'] = 'failed';
                $transaction['status']['message'] = 'failed';
                \App\Models\CQ9Transaction::create([
                    'mtcode' => $mtcode, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($transaction)
                ]);
                \DB::commit();

                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1006',
                        'message' => 'player not found',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            if ($user->balance < $amount || $user->playing_game != null)
            {
                $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
                $transaction['status']['status'] = 'failed';
                $transaction['status']['message'] = 'failed';
                \App\Models\CQ9Transaction::create([
                    'mtcode' => $mtcode, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($transaction)
                ]);
                \DB::commit();

                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1005',
                        'message' => 'insufficient balance',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            $transaction['before'] = floatval($user->balance);
            
            $user->balance = floatval(sprintf('%.4f', $user->balance - floatval($amount)));
            $user->save();

            $transaction['balance'] = floatval($user->balance);
            $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
            \App\Models\CQ9Transaction::create([
                'mtcode' => $mtcode, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($transaction)
            ]);
            $category = \App\Models\Category::where(['provider' => 'cq9', 'shop_id' => 0, 'href' => 'cq9'])->first();

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => floatval($amount), 
                'win' => 0, 
                'game' => CQ9Controller::gamecodetoname($gamecode) . '_' . $gamehall, 
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => $gamecode,
                'roundid' => 0,
            ]);

            \DB::commit();
            return response()->json([
                'data' => ['balance' => floatval($user->balance), 'currency' => 'KRW'],
                'status' => [
                    'code' => '0',
                    'message' => 'Success',
                    'datetime' => date(DATE_RFC3339_EXTENDED)
                ]
            ]);
        }

        public function takeall(\Illuminate\Http\Request $request)
        {
            $account = $request->account;
            $eventTime = $request->eventTime;
            $gamehall = $request->gamehall;
            $gamecode = $request->gamecode;
            $roundid = $request->roundid;
            $mtcode = $request->mtcode;
            $session = $request->session;

            if (!$account || !$eventTime || !$gamehall || !$gamecode || !$roundid || !$mtcode){
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1003',
                        'message' => 'incorrect parameter',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }

            if (!$this->validRFC3339Date($eventTime))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1004',
                        'message' => 'wrong time format',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            \DB::beginTransaction();

            if ($this->checkmtcode($mtcode))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '2009',
                        'message' => 'Duplicate MTCode.',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }

            $user = \App\Models\User::lockForUpdate()->where('id',$account)->get()->first();

            $transaction = [
                '_id' => $this->generateCode(24),
                'action' => 'takeall',
                'target' => [
                    'account' => $account,
                ],
                'status' => [
                    'createtime' => date(DATE_RFC3339_EXTENDED),
                    'endtime' => date(DATE_RFC3339_EXTENDED),
                    'status' => 'success',
                    'message' => 'success'
                ],
                'before' => 0,
                'balance' => 0,
                'currency' => 'KRW',

                'event' => [
                    [
                        'mtcode' => $mtcode,
                        'amount' => 0,
                        'eventtime' => $eventTime,
                    ]
                ]
            ];


            if (!$user || !$user->hasRole('user')){
                $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
                $transaction['status']['status'] = 'failed';
                $transaction['status']['message'] = 'failed';
                \App\Models\CQ9Transaction::create([
                    'mtcode' => $mtcode, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($transaction)
                ]);
                \DB::commit();

                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1006',
                        'message' => 'player not found',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            $amount = $user->balance;
            if ($amount == 0)
            {
                $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
                $transaction['status']['status'] = 'failed';
                $transaction['status']['message'] = 'failed';
                \App\Models\CQ9Transaction::create([
                    'mtcode' => $mtcode, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($transaction)
                ]);
                \DB::commit();

                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1005',
                        'message' => 'insufficient balance',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            $transaction['event'][0]['amount'] = floatval($amount);
            $transaction['before'] = floatval($user->balance);
            
            $user->balance = floatval(sprintf('%.4f', $user->balance - floatval($amount)));
            $user->save();

            $transaction['balance'] = floatval($user->balance);
            $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
            \App\Models\CQ9Transaction::create([
                'mtcode' => $mtcode, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($transaction)
            ]);
            $category = \App\Models\Category::where(['provider' => 'cq9', 'shop_id' => 0, 'href' => 'cq9'])->first();

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => floatval($amount), 
                'win' => 0, 
                'game' => CQ9Controller::gamecodetoname($gamecode) . '_' . $gamehall, 
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => $gamecode,
                'roundid' => 0,
            ]);

            \DB::commit();
            return response()->json([
                'data' => ['amount'=>floatval($amount), 'balance' => floatval($user->balance), 'currency' => 'KRW'],
                'status' => [
                    'code' => '0',
                    'message' => 'Success',
                    'datetime' => date(DATE_RFC3339_EXTENDED)
                ]
            ]);
        }

        public function rollout(\Illuminate\Http\Request $request)
        {
            $account = $request->account;
            $eventTime = $request->eventTime;
            $gamehall = $request->gamehall;
            $gamecode = $request->gamecode;
            $roundid = $request->roundid;
            $amount = $request->amount;
            $mtcode = $request->mtcode;
            $session = $request->session;

            if (!$account || !$eventTime || !$gamehall || !$gamecode || !$roundid || !$amount || !$mtcode || $amount<0){
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1003',
                        'message' => 'incorrect parameter',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }

            if (!$this->validRFC3339Date($eventTime))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1004',
                        'message' => 'wrong time format',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            \DB::beginTransaction();

            if ($this->checkmtcode($mtcode))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '2009',
                        'message' => 'Duplicate MTCode.',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }


            $transaction = [
                '_id' => $this->generateCode(24),
                'action' => 'rollout',
                'target' => [
                    'account' => $account,
                ],
                'status' => [
                    'createtime' => date(DATE_RFC3339_EXTENDED),
                    'endtime' => date(DATE_RFC3339_EXTENDED),
                    'status' => 'success',
                    'message' => 'success'
                ],
                'before' => 0,
                'balance' => 0,
                'currency' => 'KRW',

                'event' => [
                    [
                        'mtcode' => $mtcode,
                        'amount' => floatval($amount),
                        'eventtime' => $eventTime,
                    ]
                ]
            ];


            $user = \App\Models\User::lockForUpdate()->where('id',$account)->get()->first();
            if (!$user || !$user->hasRole('user')){
                $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
                $transaction['status']['status'] = 'failed';
                $transaction['status']['message'] = 'failed';
                \App\Models\CQ9Transaction::create([
                    'mtcode' => $mtcode, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($transaction)
                ]);
                \DB::commit();

                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1006',
                        'message' => 'player not found',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            if ($user->balance < $amount)
            {
                $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
                $transaction['status']['status'] = 'failed';
                $transaction['status']['message'] = 'failed';
                \App\Models\CQ9Transaction::create([
                    'mtcode' => $mtcode, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($transaction)
                ]);
                \DB::commit();

                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1005',
                        'message' => 'insufficient balance',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            $transaction['before'] = floatval($user->balance);
            
            $user->balance = floatval(sprintf('%.4f', $user->balance - floatval($amount)));
            $user->save();

            $transaction['balance'] = floatval($user->balance);
            $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
            \App\Models\CQ9Transaction::create([
                'mtcode' => $mtcode, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($transaction)
            ]);
            $category = \App\Models\Category::where(['provider' => 'cq9', 'shop_id' => 0, 'href' => 'cqlive'])->first();

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => floatval($amount), 
                'win' => 0, 
                'game' => CQ9Controller::gamecodetoname($gamecode) . '_' . $gamehall, 
                'type' => 'table',
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => $gamecode,
                'roundid' => 0,
            ]);

            \DB::commit();
            $resjson = json_encode([
                'data' => ['balance' => number_format($user->balance,4,'.',''), 'currency' => 'KRW'],
                'status' => [
                    'code' => '0',
                    'message' => 'Success',
                    'datetime' => date(DATE_RFC3339_EXTENDED)
                ]
            ]);
            $pattern = '/("balance":)"([.\d]+)"/x';
            $replacement = '${1}${2}';
            return response(preg_replace($pattern, $replacement, $resjson), 200)->header('Content-Type', 'application/json');
        }
        public function rollin(\Illuminate\Http\Request $request)
        {
            $account = $request->account;
            $eventTime = $request->eventTime;
            $gamehall = $request->gamehall;
            $gamecode = $request->gamecode;
            $roundid = $request->roundid;
            $amount = $request->amount;
            $mtcode = $request->mtcode;
            $bet = $request->bet;
            $win = $request->win;
            $createTime = $request->createTime;  
            $gametype = $request->gametype;
            $rake = $request->rake;

            if (!$account || !$eventTime || !$gamehall || !$gamecode || !$roundid || !$amount || !$mtcode || !$bet || !$win || !$createTime || !$gametype || !$rake ||$amount<0){
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1003',
                        'message' => 'incorrect parameter',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }

            if (!$this->validRFC3339Date($eventTime) || !$this->validRFC3339Date($createTime))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1004',
                        'message' => 'wrong time format',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            \DB::beginTransaction();

            if ($this->checkmtcode($mtcode))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '2009',
                        'message' => 'Duplicate MTCode.',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }


            $transaction = [
                '_id' => $this->generateCode(24),
                'action' => 'rollin',
                'target' => [
                    'account' => $account,
                ],
                'status' => [
                    'createtime' => date(DATE_RFC3339_EXTENDED),
                    'endtime' => date(DATE_RFC3339_EXTENDED),
                    'status' => 'success',
                    'message' => 'success'
                ],
                'before' => 0,
                'balance' => 0,
                'currency' => 'KRW',

                'event' => [
                    [
                        'mtcode' => $mtcode,
                        'amount' => floatval($amount),
                        'eventtime' => $eventTime,
                    ]
                ]
            ];


            $user = \App\Models\User::lockForUpdate()->where('id',$account)->get()->first();
            if (!$user || !$user->hasRole('user')){
                $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
                $transaction['status']['status'] = 'failed';
                $transaction['status']['message'] = 'failed';
                \App\Models\CQ9Transaction::create([
                    'mtcode' => $mtcode, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($transaction)
                ]);
                \DB::commit();

                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1006',
                        'message' => 'player not found',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            
            $transaction['before'] = floatval($user->balance);
            
            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
            $user->save();

            $transaction['balance'] = floatval($user->balance);
            $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
            \App\Models\CQ9Transaction::create([
                'mtcode' => $mtcode, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($transaction)
            ]);
            $category = \App\Models\Category::where(['provider' => 'cq9', 'shop_id' => 0, 'href' => 'cqlive'])->first();

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => floatval($amount), 
                'game' => CQ9Controller::gamecodetoname($gamecode) . '_' . $gamehall, 
                'type' => 'table',
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => $gamecode,
                'roundid' => 0,
            ]);

            \DB::commit();
            return response()->json([
                'data' => ['balance' => floatval($user->balance), 'currency' => 'KRW'],
                'status' => [
                    'code' => '0',
                    'message' => 'Success',
                    'datetime' => date(DATE_RFC3339_EXTENDED)
                ]
            ]);
        }
        public function endround(\Illuminate\Http\Request $request)
        {
            $account = $request->account;
            $gamehall = $request->gamehall;
            $gamecode = $request->gamecode;
            $roundid = $request->roundid;
            $data = $request->data;
            $createTime = $request->createTime;
            $freegame = $request->freegame;
            $jackpot = $request->jackpot;
            $jackpotcontribution = $request->jackpotcontribution;

            if (!$account ||  !$gamehall || !$gamecode || !$roundid || !$data || !$createTime){
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1003',
                        'message' => 'incorrect parameter',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            \DB::beginTransaction();


            if (!$this->validRFC3339Date($createTime))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1004',
                        'message' => 'wrong time format',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            $transaction = [
                '_id' => $this->generateCode(24),
                'action' => 'endround',
                'target' => [
                    'account' => $account,
                ],
                'status' => [
                    'createtime' => date(DATE_RFC3339_EXTENDED),
                    'endtime' => date(DATE_RFC3339_EXTENDED),
                    'status' => 'success',
                    'message' => 'success'
                ],
                'before' => 0,
                'balance' => 0,
                'currency' => 'KRW',
            ];


            $user = \App\Models\User::lockForUpdate()->where('id',$account)->get()->first();
            if (!$user || !$user->hasRole('user')){
                \DB::commit();
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1006',
                        'message' => 'player not found',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }

            $endrounddata = json_decode($data, true);
            $totalamount = 0;
            $mtcodes_record = [];
            if ($endrounddata){
                foreach ($endrounddata as $round)
                {
                    if ($round['amount'] < 0)
                    {
                        \DB::commit();
                        return response()->json([
                            'data' => null,
                            'status' => [
                                'code' => '1003',
                                'message' => 'amount is negative',
                                'datetime' => date(DATE_RFC3339_EXTENDED)
                            ]
                        ]);
                    }
                    if (!$this->validRFC3339Date($round['eventtime']))
                    {
                        \DB::commit();
                        return response()->json([
                            'data' => null,
                            'status' => [
                                'code' => '1004',
                                'message' => 'wrong time format',
                                'datetime' => date(DATE_RFC3339_EXTENDED)
                            ]
                        ]);
                    }
                    $mtcodes_record[] = $round['mtcode'];
                    $totalamount += $round['amount'];
                }
            }
            $transaction['before'] = floatval($user->balance);

            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($totalamount)));
            $user->save();

            $transaction['balance'] = floatval($user->balance);
            $transaction['event'] = $endrounddata;
            $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
            foreach ($mtcodes_record as $record)
            {
                if ($this->checkmtcode($record))
                {
                    $user->balance = floatval(sprintf('%.4f', $user->balance - floatval($totalamount)));
                    $user->save();
                    \DB::commit();
                    return response()->json([
                        'data' => null,
                        'status' => [
                            'code' => '2009',
                            'message' => 'Duplicate MTCode.',
                            'datetime' => date(DATE_RFC3339_EXTENDED)
                        ]
                    ]);
                }
                \App\Models\CQ9Transaction::create([
                    'mtcode' => $record, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($transaction)
                ]);
            }

            if (floatval($totalamount) > 0) {
                $category = \App\Models\Category::where(['provider' => 'cq9', 'shop_id' => 0, 'href' => 'cq9'])->first();
                \App\Models\StatGame::create([
                    'user_id' => $user->id, 
                    'balance' => floatval($user->balance), 
                    'bet' => 0, 
                    'win' => floatval($totalamount), 
                    'game' => CQ9Controller::gamecodetoname($gamecode) .'_' . $gamehall, 
                    'percent' => 0, 
                    'percent_jps' => 0, 
                    'percent_jpg' => 0, 
                    'profit' => 0, 
                    'denomination' => 0, 
                    'shop_id' => $user->shop_id,
                    'category_id' => isset($category)?$category->id:0,
                    'game_id' => $gamecode,
                    'roundid' => 0,
                ]);
            }

            \DB::commit();

            return response()->json([
                'data' => ['balance' => floatval($user->balance), 'currency' => 'KRW'],
                'status' => [
                    'code' => '0',
                    'message' => 'Success',
                    'datetime' => date(DATE_RFC3339_EXTENDED)
                ]
            ]);
        }

        public function debit(\Illuminate\Http\Request $request)
        {
            $account = $request->account;
            $eventTime = $request->eventTime;
            $gamehall = $request->gamehall;
            $gamecode = $request->gamecode;
            $roundid = $request->roundid;
            $amount = $request->amount;
            $mtcode = $request->mtcode;

            if (!$account || !$eventTime || !$gamehall || !$gamecode || !$roundid || !$amount || !$mtcode || $amount<0){
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1003',
                        'message' => 'incorrect parameter',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            if (!$this->validRFC3339Date($eventTime))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1004',
                        'message' => 'wrong time format',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }

            \DB::beginTransaction();


            if ($this->checkmtcode($mtcode))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '2009',
                        'message' => 'Duplicate MTCode.',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }


            $transaction = [
                '_id' => $this->generateCode(24),
                'action' => 'debit',
                'target' => [
                    'account' => $account,
                ],
                'status' => [
                    'createtime' => date(DATE_RFC3339_EXTENDED),
                    'endtime' => date(DATE_RFC3339_EXTENDED),
                    'status' => 'success',
                    'message' => 'success'
                ],
                'before' => 0,
                'balance' => 0,
                'currency' => 'KRW',

                'event' => [
                    [
                        'mtcode' => $mtcode,
                        'amount' => floatval($amount),
                        'eventtime' => $eventTime,
                    ]
                ]
            ];


            $user = \App\Models\User::lockForUpdate()->where('id',$account)->get()->first();
            if (!$user || !$user->hasRole('user')){
                $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
                $transaction['status']['status'] = 'failed';
                $transaction['status']['message'] = 'failed';
                \App\Models\CQ9Transaction::create([
                    'mtcode' => $mtcode, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($transaction)
                ]);
                \DB::commit();

                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1006',
                        'message' => 'player not found',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            if ($user->balance < $amount)
            {
                $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
                $transaction['status']['status'] = 'failed';
                $transaction['status']['message'] = 'failed';
                \App\Models\CQ9Transaction::create([
                    'mtcode' => $mtcode, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($transaction)
                ]);
                \DB::commit();

                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1005',
                        'message' => 'insufficient balance',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            $transaction['before'] = floatval($user->balance);
            
            $user->balance = floatval(sprintf('%.4f', $user->balance - floatval($amount)));
            $user->save();

            $transaction['balance'] = floatval($user->balance);
            $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
            \App\Models\CQ9Transaction::create([
                'mtcode' => $mtcode, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($transaction)
            ]);
            $category = \App\Models\Category::where(['provider' => 'cq9', 'shop_id' => 0, 'href' => 'cq9'])->first();
            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => floatval($amount), 
                'win' => 0, 
                'game' => CQ9Controller::gamecodetoname($gamecode) . '_' . $gamehall . ' debit', 
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => $gamecode,
                'roundid' => 0,
            ]);

            \DB::commit();

            
            return response()->json([
                'data' => ['balance' => floatval($user->balance), 'currency' => 'KRW'],
                'status' => [
                    'code' => '0',
                    'message' => 'Success',
                    'datetime' => date(DATE_RFC3339_EXTENDED)
                ]
            ]);

        }

        public function credit(\Illuminate\Http\Request $request)
        {
            $account = $request->account;
            $eventTime = $request->eventTime;
            $gamehall = $request->gamehall;
            $gamecode = $request->gamecode;
            $roundid = $request->roundid;
            $amount = $request->amount;
            $mtcode = $request->mtcode;

            if (!$account || !$eventTime || !$gamehall || !$gamecode || !$roundid || !$amount || !$mtcode || $amount<0){
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1003',
                        'message' => 'incorrect parameter',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            if (!$this->validRFC3339Date($eventTime))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1004',
                        'message' => 'wrong time format',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }

            \DB::beginTransaction();

            if ($this->checkmtcode($mtcode))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '2009',
                        'message' => 'Duplicate MTCode.',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }


            $transaction = [
                '_id' => $this->generateCode(24),
                'action' => 'credit',
                'target' => [
                    'account' => $account,
                ],
                'status' => [
                    'createtime' => date(DATE_RFC3339_EXTENDED),
                    'endtime' => date(DATE_RFC3339_EXTENDED),
                    'status' => 'success',
                    'message' => 'success'
                ],
                'before' => 0,
                'balance' => 0,
                'currency' => 'KRW',

                'event' => [
                    [
                        'mtcode' => $mtcode,
                        'amount' => floatval($amount),
                        'eventtime' => $eventTime,
                    ]
                ]
            ];


            $user = \App\Models\User::lockForUpdate()->where('id',$account)->get()->first();
            if (!$user || !$user->hasRole('user')){
                $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
                $transaction['status']['status'] = 'failed';
                $transaction['status']['message'] = 'failed';
                \App\Models\CQ9Transaction::create([
                    'mtcode' => $mtcode, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($transaction)
                ]);
                \DB::commit();

                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1006',
                        'message' => 'player not found',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            
            $transaction['before'] = floatval($user->balance);
            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
            $user->save();

            $transaction['balance'] = floatval($user->balance);
            $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
            \App\Models\CQ9Transaction::create([
                'mtcode' => $mtcode, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($transaction)
            ]);
            $category = \App\Models\Category::where(['provider' => 'cq9', 'shop_id' => 0, 'href' => 'cq9'])->first();
            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => floatval($amount), 
                'game' => CQ9Controller::gamecodetoname($gamecode) . '_' . $gamehall . ' credit', 
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => $gamecode,
                'roundid' => 0,
            ]);

            \DB::commit();

            
            return response()->json([
                'data' => ['balance' => floatval($user->balance), 'currency' => 'KRW'],
                'status' => [
                    'code' => '0',
                    'message' => 'Success',
                    'datetime' => date(DATE_RFC3339_EXTENDED)
                ]
            ]);

        }

        public function refund(\Illuminate\Http\Request $request)
        {
            $mtcode = $request->mtcode;
            if (!$mtcode)
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1003',
                        'message' => 'incorrect parameter',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            \DB::beginTransaction();

            $record = $this->checkmtcode($mtcode);
            
            if (!$record)
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1014',
                        'message' => 'record not found',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            if ($record->refund > 0)
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1015',
                        'message' => 'already refunded',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }

            $data = json_decode($record->data, true);

            
            $user = \App\Models\User::lockForUpdate()->where('id',$data['target']['account'])->get()->first();
            if (!$user || !$user->hasRole('user')){
                \DB::commit();
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1006',
                        'message' => 'player not found',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            $totalamount = 0;
            foreach ($data['event'] as $event) {
                $totalamount += $event['amount'];
            }
            $data['status']['status'] ='refund';
            $data['before'] = floatval($user->balance);
            
            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($totalamount)));
            $user->save();

            $data['balance'] = floatval($user->balance);
            
            $record->refund = 1;
            $record->data = json_encode($data);
            $record->save();
            $category = \App\Models\Category::where(['provider' => 'cq9', 'shop_id' => 0, 'href' => 'cq9'])->first();
            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => floatval($totalamount), 
                'game' => 'CQ9_cq9 refund', 
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => 'CQ9_cq9',
                'roundid' => 0,
            ]);

            \DB::commit();


            $resjson = json_encode([
                'data' => ['balance' => number_format ($user->balance,4,'.',''), 'currency' => 'KRW'],
                'status' => [
                    'code' => '0',
                    'message' => 'Success',
                    'datetime' => date(DATE_RFC3339_EXTENDED)
                ]
            ]);
            $pattern = '/("balance":)"([.\d]+)"/x';
            $replacement = '${1}${2}';
            
            return response(preg_replace($pattern, $replacement, $resjson), 200)->header('Content-Type', 'application/json');
        }

        public function payoff(\Illuminate\Http\Request $request)
        {
            $account = $request->account;
            $eventTime = $request->eventTime;
            $amount = $request->amount;
            $mtcode = $request->mtcode;
            $remark = $request->remark;

            if (!$account || !$eventTime || !$amount || !$mtcode || $amount<0){
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1003',
                        'message' => 'incorrect parameter',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }

            if (!$this->validRFC3339Date($eventTime))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1004',
                        'message' => 'wrong time format',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }

            \DB::beginTransaction();


            if ($this->checkmtcode($mtcode))
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '2009',
                        'message' => 'Duplicate MTCode.',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }


            $transaction = [
                '_id' => $this->generateCode(24),
                'action' => 'payoff',
                'target' => [
                    'account' => $account,
                ],
                'status' => [
                    'createtime' => date(DATE_RFC3339_EXTENDED),
                    'endtime' => date(DATE_RFC3339_EXTENDED),
                    'status' => 'success',
                    'message' => 'success'
                ],
                'before' => 0,
                'balance' => 0,
                'currency' => 'KRW',

                'event' => [
                    [
                        'mtcode' => $mtcode,
                        'amount' => floatval($amount),
                        'eventtime' => $eventTime,
                    ]
                ]
            ];


            $user = \App\Models\User::lockForUpdate()->where('id',$account)->get()->first();
            if (!$user || !$user->hasRole('user')){
                $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
                $transaction['status']['status'] = 'failed';
                $transaction['status']['message'] = 'failed';
                \App\Models\CQ9Transaction::create([
                    'mtcode' => $mtcode, 
                    'timestamp' => $this->microtime_string(),
                    'data' => json_encode($transaction)
                ]);
                \DB::commit();

                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1006',
                        'message' => 'player not found',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            
            $transaction['before'] = floatval($user->balance);
            
            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
            $user->save();

            $transaction['balance'] = floatval($user->balance);
            $transaction['status']['endtime'] = date(DATE_RFC3339_EXTENDED);
            \App\Models\CQ9Transaction::create([
                'mtcode' => $mtcode, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($transaction)
            ]);
            $category = \App\Models\Category::where(['provider' => 'cq9', 'shop_id' => 0, 'href' => 'cq9'])->first();
            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => floatval($amount), 
                'game' => 'CQ9_cq9 payoff', 
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => isset($category)?$category->id:0,
                'game_id' => 'CQ9_cq9',
                'roundid' => 0,
            ]);

            \DB::commit();

            
            return response()->json([
                'data' => ['balance' => floatval($user->balance), 'currency' => 'KRW'],
                'status' => [
                    'code' => '0',
                    'message' => 'Success',
                    'datetime' => date(DATE_RFC3339_EXTENDED)
                ]
            ]);
        }

        public function record($mtcode, \Illuminate\Http\Request $request)
        {
            $record = $this->checkmtcode($mtcode);
            if (!$record)
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1014',
                        'message' => 'record not found',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
            $data = json_decode($record->data, true);

            return response()->json([
                'data' => $data,
                'status' => [
                    'code' => '0',
                    'message' => 'Success',
                    'datetime' => date(DATE_RFC3339_EXTENDED)
                ]
            ]);
        }
        public function balance($account, \Illuminate\Http\Request $request)
        {
            $user = \App\Models\User::lockForUpdate()->where('id',$account)->get()->first();
            if ($user && $user->hasRole('user'))
            {
                $resjson = json_encode([
                    'data' => ['balance' => number_format ($user->balance,4,'.',''), 'currency' => 'KRW'],
                    'status' => [
                        'code' => '0',
                        'message' => 'Success',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
                $pattern = '/("balance":)"([.\d]+)"/x';
                $replacement = '${1}${2}';
                
                return response(preg_replace($pattern, $replacement, $resjson), 200)->header('Content-Type', 'application/json');
            }
            else
            {
                return response()->json([
                    'data' => null,
                    'status' => [
                        'code' => '1006',
                        'message' => 'player not found',
                        'datetime' => date(DATE_RFC3339_EXTENDED)
                    ]
                ]);
            }
        }

        public function checkplayer($account, \Illuminate\Http\Request $request)
        {
            $user = \App\Models\User::where('id',$account)->get()->first();
            $data = false;
            if ($user && $user->hasRole('user')) {
                $data = true;
            }
            return response()->json([
                'data' => $data,
                'status' => [
                    'code' => '0',
                    'message' => 'Success',
                    'datetime' => date(DATE_RFC3339_EXTENDED)
                ]
            ]);

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
            $response = null;
            try {
                $response = Http::withHeaders([
                    'Authorization' => config('app.cq9token'),
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ])->get(config('app.cq9api') . '/gameboy/game/list/cq9');

            } catch (\Exception $e) {
                return null;
            }
           
            if ($response==null && !$response->ok())
            {
                return null;
            }
            $detect = new \Detection\MobileDetect();
            $plat = ($detect->isMobile() || $detect->isTablet())?'mobile':'web';
            $data = $response->json();
            if ($data!=null && $data['status']['code'] == 0){
                $gameList = [];
                foreach ($data['data'] as $game)
                {
                    if ($href=='cq9') {
                        if ($game['gametype'] == "slot" && $game['status'] && str_contains($game['gameplat'], $plat))
                        {
                            $selLan = 'ko';
                            if (!in_array($selLan, $game['lang']))
                            {
                                $selLan = 'en';
                                if (!in_array($selLan, $game['lang']))
                                {
                                    continue;
                                }
                            }
                            foreach ($game['nameset'] as $title)
                            {
                                if ($title['lang'] == $selLan)
                                {
                                    $gameList[] = [
                                        'provider' => 'cq9',
                                        'gamecode' => $game['gamecode'],
                                        'name' => preg_replace('/\s+/', '', $game['gamename']),
                                        'title' => $selLan=='en'?__('gameprovider.'.$title['name']) : $title['name'],
                                    ];
                                }
                            }
                        }
                    }
                    else if ($href=='cqlive')
                    {
                        if ($game['gamecode'] == CQ9Controller::LIVECODE)
                        {
                            $gameList[] = [
                                'provider' => 'cq9',
                                'gamecode' => $game['gamecode'],
                                'name' => preg_replace('/\s+/', '', $game['gamename']),
                                'title' =>__('gameprovider.cq9live'),
                            ];
                        }
                    }
                    else
                    {
                        return null;
                    }
                }
                \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
                return $gameList;
            }
            return null;

        }
        public static function getgamelink($gamecode)
        {
            if (str_contains(\Illuminate\Support\Facades\Auth::user()->username, 'testfor') )
            {
                $url = CQ9Controller::makegamelink($gamecode);
                return ['error' => false, 'data' => ['url' => $url]];
            }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.cq9.render', $gamecode)]];
        }
        public static function makegamelink($gamecode)
        {
            $user = auth()->user();
            if ($user == null)
            {
                return null;
            }
            $detect = new \Detection\MobileDetect();
            try{
                $response = Http::withHeaders([
                    'Authorization' => config('app.cq9token'),
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ])->asForm()->post(config('app.cq9api') . '/gameboy/player/sw/gamelink', [
                    'account' => $user->id,
                    'gamehall' => 'cq9',
                    'gamecode' => $gamecode,
                    'gameplat' => ($detect->isMobile() || $detect->isTablet())?'MOBILE':'WEB',
                    'lang' => $gamecode==CQ9Controller::LIVECODE?'en':'ko'
                ]);
                if (!$response->ok())
                {
                    return null;
                }
                $data = $response->json();
                if ($data['status']['code'] == 0){
                    return $data['data']['url'];
                }
                else{
                    return null;
                }
            }
            catch (\Exception $ex)
            {
                Log::error($ex->getMessage());
                return null;
            }
        }

        public function clientInfo(\Illuminate\Http\Request $request)
        {
            $ip = $request->server('HTTP_CF_CONNECTING_IP')??$request->server('REMOTE_ADDR');
            $data = [
                "data" => [
                    "ip" => $ip,
                    "code" =>"",
                    "datatime" => date(DATE_RFC3339_EXTENDED)
                ]
            ];
            return response()->json($data);

        }
        public function toggle(\Illuminate\Http\Request $request)
        {
            $data = '{"code": 0,"message": "Success","data": {"help": ["1","2","3","4","5","7","8","9","10","12","13","15","16","17","19","20","21","22","23","24","26","27","29","31","32","33","34","35","38","39","42","44","46","47","49","50","51","52","54","55","57","58","59","64","66","67","68","69","70","72","74","76","77","78","79","80","81","83","92","95","96","98","171","1067","5009","GB2","GB3","GB5","GB6","GB8","GB9","GB12","GB13","GB15","GB16","89","99","105","108","109","111","112","113","115","116","117","118","121","122","123","125","127","128","129","130","131","132","133","135","136","137","138","139","140","142","144","146","150","153","155","156","161","171","179","180","182","183","184","185","186","187","188","189","190","191","192","193","194","195","197","199","201","202","203","204","205","223","225","226","227","231","241","242","243","1074"],"memberSystem": ["7","9","10","15","24","26","31","50","52","64","67","69","79","83","89","99","105","115","117","138","140","152","153","160","179","182","183","186","188"],"ptdLink": {"bag": "utm_source=ingame\u0026utm_medium=bag","mission": "utm_source=ingame\u0026utm_medium=mission","setting": "utm_source=ingame\u0026utm_medium=setting"},"xda": false}}';
            return  response($data, 200)->header('Content-Type', 'application/json');

        }
        public function cq9History(\Illuminate\Http\Request $request)
        {
            return view('frontend.Default.games.cq9.history');
        }

        public function cq9Feedback(\Illuminate\Http\Request $request)
        {
            return view('frontend.Default.games.cq9.feedback');
        }

        public function cq9FeedbackInit(\Illuminate\Http\Request $request)
        {
            $data = '{"error_code":1,"error_msg":"SUCCESS","log_id":"","result":{"white":false,"rate":[{"id":1,"name":"HowtoPlay"},{"id":2,"name":"Visualperformance"},{"id":3,"name":"Experienceandfeeling"}],"tag":[{"id":1,"name":"Gameplay"},{"id":2,"name":"Visual"},{"id":3,"name":"Experience"},{"id":4,"name":"Bug"},{"id":5,"name":"Others"},{"id":6,"name":""}]}}';
            return  response($data, 200)->header('Content-Type', 'application/json');
        }

        public function cq9FeedbackCreate(\Illuminate\Http\Request $request)
        {
            $data = '{"error_code":1,"error_msg":"SUCCESS","log_id":"","result":[]}';
            return  response($data, 200)->header('Content-Type', 'application/json');
        }

        public function cq9PlayerOrder(\Illuminate\Http\Request $request)
        {
            return view('frontend.Default.games.cq9.playerorder');
        }

        public function searchTime(\Illuminate\Http\Request $request)
        {
            $token = $request->token;
            $begin = date('Y-m-d H:i:s', strtotime($request->begin));
            $end = date('Y-m-d H:i:s', strtotime($request->end));
            $offset = $request->offset;
            $count = $request->count;
            $user = \App\Models\User::where('api_token', $token)->first();
            if (!$user)
            {
                $data = [
                    "error_code" => 4096,
                    "error_msg" => "GET CYPRESS AUTHORIZATION INVALID(4006)",
                    "log_id" => $this->microtime_string(),
                    "result" => [
                    ]
                ];
                return response()->json($data);
            }

            $categories = \App\Models\Category::whereIn('href', ['cq9play'])->where(['shop_id' => 0,'site_id' => 0])->pluck('id')->toArray();
            $stat_game = \App\Models\StatGame::where('user_id', $user->id)->where('date_time', '>=', $begin)->where('date_time', '<=', $end)->whereIn('category_id', $categories)->orderby('date_time', 'desc')->skip($offset * $count)->take($count)->get();
            $list = [];
            foreach ($stat_game as $game)
            {
                $date = new \DateTime($game->date_time);
                $timezoneName = timezone_name_from_abbr("", -4*3600, false);
                $date->setTimezone(new \DateTimeZone($timezoneName));
                $time= $date->format(DATE_RFC3339_EXTENDED);
                $code = $game->game_item->label;
                $detail = [
                    ['freegame' => 0],
                    ['luckydraw' => 0],
                    ['bonus' => 0],
                ];
                if (strpos($game->game, ' FG') == true)
                {
                    $detail[0]['freegame'] = 1;
                }
                else if (strpos($game->game, ' BG') == true)
                {
                    $detail[2]['bonus'] = 1;
                }
                $record = [
                    'bets' => $game->bet,
                    'createtime' => $time,
                    'detail' => $detail,
                    'gamecode' => $code,
                    'gamename' => $game->game_item->title,
                    'nameset' => [
                        [
                            'lang' => 'en',
                            'name' => $game->game_item->title
                        ]
                    ],
                    'roundid' => $game->roundid,
                    'singlerowbet' => false,
                    'tabletype' => "",
                    'wins' => $game->win,
                ];
                $list[] = $record;
            }
            $data = [
                "error_code" => 1,
                "error_msg" => "SUCCESS",
                "log_id" => "",
                "result" => [
                    "data" => [ 
                        "count" => count($stat_game),
                        "list" => $list
                    ],
                    "status" => [
                        "code" => "0",
                        "message" => "Success",
                        "datetime" => date(DATE_RFC3339_EXTENDED),
                        "traceCode" => $this->microtime_string()
                    ]
                ]
            ];
            return response()->json($data);
        }

        public function detailLink(\Illuminate\Http\Request $request)
        {
            $token = $request->token;
            $roundid = $request->id;
            $gameid = $request->game_id;
            $user = \App\Models\User::where('api_token', $token)->first();
            if (!$user)
            {
                $data = [
                    "error_code" => 4096,
                    "error_msg" => "GET CYPRESS AUTHORIZATION INVALID(4006)",
                    "log_id" => $this->microtime_string(),
                    "result" => [
                    ]
                ];
                return response()->json($data);
            }
            $data = [
                "error_code" => 1,
                "error_msg" => "SUCCESS",
                "log_id" => "",
                "result" => [
                    "data" => [
                        "link" => config('app.cq9history') . '/playerodh5/?token=' . base64_encode($user->id .'-' .$roundid),
                    ],
                    "status" => [
                        "code" => "0",
                        "message" => "Success",
                        "datetime" => date(DATE_RFC3339_EXTENDED),
                        "traceCode" => $this->microtime_string()
                    ]
                ]
            ];
            return response()->json($data);
        }

        public function wager(\Illuminate\Http\Request $request)
        {
            $param = $request->token;
            if (!$param)
            {
                $data = '{"data":{},"status":{"code":"1","message":"NoGame","datetime":"'.date(DATE_RFC3339_EXTENDED).'"}}';
                return response($data, 200)->header('Content-Type', 'application/json');
            }
            $param = explode('-',base64_decode($param));
            if (count($param) < 2)
            {
                $data = '{"data":{},"status":{"code":"1","message":"NoGame","datetime":"'.date(DATE_RFC3339_EXTENDED).'"}}';
                return response($data, 200)->header('Content-Type', 'application/json');
            }
            $gamelog = \App\Models\GameLog::where([
                'user_id' => $param[0],
                'roundid' => $param[1]])->first();
            if ($gamelog)
            {
                $data = '{"data":' . $gamelog->str . ',"status":{"code":"0","message":"Success","datetime":"'.date(DATE_RFC3339_EXTENDED).'"}}';
            }
            else
            {
                $data = '{"data":{},"status":{"code":"1","message":"NoGame","datetime":"'.date(DATE_RFC3339_EXTENDED).'"}}';
            }
            return response($data, 200)->header('Content-Type', 'application/json');
        }
        public static function getRecommendList($shop_id, $game_id)
        {
            $games = \App\Models\Game::where(['shop_id' => $shop_id, 'view' => 1])->whereNotIn('id', [$game_id])->whereRaw('name like "%CQ9" and label regexp "^[0-9]+"')->inRandomOrder()->take(20)->get();
            $recommendGameList = [];
            $hotRankingGameList = [];
            $bets = [100, 200, 600, 1000, 2000];
            $count = 0;
            for($k = 0; $k < count($games); $k++){
                $mul = mt_rand(200, 500);
                $bet = $bets[mt_rand(0, 3)];
                $recommendGameList[] = [
                    'type' => 1,
                    'gameCode' => $games[$k]->label,
                    'maxScore' => $bet * $mul,
                    'maxMultiplier' => $mul,
                    'iconURL' => "/frontend/Default/ico/cq9/lobby/". $games[$k]->label .".png",
                    'backgroundURL' => "https://images.cq9web.com/cherry/background/sdfFHq1qiM5.jpg"
                ];
                $count++;
                if($count == 10){
                    break;
                }
            }
            if($count == 10 && count($games) > 10){
                for($k = 10; $k < count($games); $k++){
                    $mul = mt_rand(300, 500);
                    $bet = $bets[mt_rand(0, 3)];
                    $hotRankingGameList[] = [
                        'type' => 1,
                        'gameCode' => $games[$k]->label,
                        'maxScore' => $bet * $mul,
                        'maxMultiplier' => $mul,
                        'iconURL' => "/frontend/Default/ico/cq9/lobby/". $games[$k]->label .".png",
                        'backgroundURL' => "https://images.cq9web.com/cherry/background/sdfFHq1qiM5.jpg"
                    ];
                    $count++;
                    if($count == 20){
                        break;
                    }
                }   
            }
            return ['recommendGameList' => $recommendGameList, 'hotRankingGameList' => $hotRankingGameList];
        }
    }

}
