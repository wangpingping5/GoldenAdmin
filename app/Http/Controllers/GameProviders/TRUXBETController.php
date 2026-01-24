<?php
namespace App\Http\Controllers\GameProviders {

    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Redis;
    use Illuminate\Http\Request;

    class TRUXBETController extends \App\Http\Controllers\Controller
    {

        const TRUXBET_PROVIDER = 'truxbet';
        const TRUXBET_BLUEPREFIX = 'tbuser';
        const TRUXBET_PPVERIFY_PROVIDER = 'truxbetv';
        const TRUXBET_PP_HREF = 'truxbet-pp';

        const TRUXBET_GAME_IDENTITY = [
            //==== SLOT ====
            'truxbet-pp' => ['thirdname' => 'PragmaticPlay', 'type' => 'slot', 'symbol' => 'pp', 'skin' => 'SLOT'],                  // 프라그마틱
            //==== CASINO ====
            'truxbet-evo' => ['thirdname' => 'Evolution', 'type' => 'casino', 'symbol' => 'evo', 'skin' => 'CASINO'],                // 에볼루션
        ];

        // Common Api
        public static function getAgentBalance()
        {
            $httpMethod = "GET";
            $path = "IntegrationApi/AgentBalance";
            $pathname = strtolower($path);
            $agentCode = config('app.truxbet_agent');
            $token = config('app.truxbet_token');
            $authorization = self::createAuthorization($agentCode, $token, $httpMethod, $pathname);

            $apiURL = config('app.truxbet_api');
            $url = $apiURL . "/" . strtolower($path);

            try {
                $response = Http::withHeaders([
                    'Authorization' => $authorization
                ])->get($url, )->throw();

                $data = $response->json();

                return intval($data['result']['balance']);
            } catch (\Exception $ex) {
                Log::error('Truxbet Agent Balance Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Truxbet Agent Balance Request :  Excpetion. URL= ' . $url);
            }

            return -1;
        }

        public static function getgamelist($href)
        {
            $games = Redis::get($href . 'list');
            $games = $games ? json_decode($games, true) : [];

            if (count($games) > 0) {
                return $games;
            }

            $httpMethod = "GET";
            $path = "IntegrationApi/Games";
            $pathname = strtolower($path);
            $agentCode = config('app.truxbet_agent');
            $token = config('app.truxbet_token');
            $authorization = self::createAuthorization($agentCode, $token, $httpMethod, $pathname);

            $apiURL = config('app.truxbet_api');
            $url = $apiURL . "/" . strtolower($path);

            $category = self::TRUXBET_GAME_IDENTITY[$href];
            $providerCode = $category['thirdname'];
            $type = $category['type'];
            $skin = $category['skin'];

            $query = [
                'providerCode' => $providerCode
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => $authorization
                ])->get($url, $query)->throw();

                $data = $response->json();

                foreach ($data['result'] as $game) {
                    Log::info(json_encode($game));

                    array_push($games, [
                        'provider' => self::TRUXBET_PROVIDER,
                        'vendorKey' => $providerCode,
                        'href' => $href,
                        'gameid' => $game['code'],
                        'gamecode' => $game['code'],
                        'symbol' => $game['code'],
                        'name' => $game['name_en'],
                        'title' => $game['name'],
                        'type' => $type,
                        'skin' => $skin,
                        'icon' => $game['banner'],
                        'view' => 1
                    ]);
                }
            } catch (\Exception $ex) {
                Log::error('Truxbet Game List Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Truxbet Game List Request :  Excpetion. URL= ' . $url);
                Log::error('Truxbet Game List Request :  Excpetion. PARAMS= ' . json_encode($query));
            }

            Redis::set($href . 'list', json_encode($games));

            return $games;
        }

        public static function getGameObj($uuid)
        {
            foreach (self::TRUXBET_GAME_IDENTITY as $ref => $value) {
                $games = self::getgamelist($ref);

                if (empty($games)) {
                    continue;
                }

                foreach ($games as $game) {
                    if ($game['gamecode'] == $uuid) {
                        return $game;
                    }
                }
            }

            return null;
        }

        public static function getgamelink($gamecode)
        {
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [self::TRUXBET_PROVIDER, $gamecode])]];
        }

        public static function makelink($gamecode, $userid)
        {
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user) {
                Log::error('Truxbet Make Link : User Not Found ' . $userid);
                return null;
            }

            $game = self::getGameObj($gamecode);
            if ($game == null) {
                Log::error('Truxber Make Link : Game Not Found ' . $gamecode);
                return null;
            }

            $userBalance = self::getUserBalance($game['href'], $user, self::TRUXBET_BLUEPREFIX);

            if ($userBalance != $user->balance) {
                self::withdrawAll($game['href'], $user, self::TRUXBET_BLUEPREFIX);
                self::deposit($game['href'], $user, self::TRUXBET_BLUEPREFIX);
            }

            return '/followgame/' . self::TRUXBET_PROVIDER . '/' . $gamecode;

        }

        public static function makegamelink($gamecode, $user = null, $prefix = self::TRUXBET_BLUEPREFIX)
        {
            $game = self::getGameObj($gamecode);
            $user = $user ?? auth()->user();

            if (!$game || !$user) {
                return null;
            }

            $userCode = $prefix . sprintf("%04d", $user->id);

            $httpMethod = "POST";
            $path = "IntegrationApi/GameLaunch";
            $pathname = strtolower($path);
            $agentCode = config('app.truxbet_agent');
            $token = config('app.truxbet_token');
            $authorization = self::createAuthorization($agentCode, $token, $httpMethod, $pathname);

            $apiURL = config('app.truxbet_api');
            $url = $apiURL . "/" . strtolower($path);

            $body = [
                "userCode" => $userCode,
                "nickname" => $userCode,
                "providerCode" => $game['vendorKey'],
                "gameCode" => $game['symbol'],
                "lang" => "ko",
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => $authorization,
                ])->post($url, $body)->throw();

                $data = $response->json();

                return $data['result']['url'];
            } catch (\Exception $ex) {
                Log::error('Truxbet Game Launch Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Truxbet Game Launch Request :  Excpetion. PARAMS= ' . json_encode($body));

                return null;
            }
        }

        // Transfer Api
        public static function userCreate($href, $user, $prefix = self::TRUXBET_BLUEPREFIX)
        {
            $httpMethod = "POST";
            $path = "TransferApi/UserCreate";
            $pathname = strtolower($path);
            $agentCode = config('app.truxbet_agent');
            $token = config('app.truxbet_token');
            $authorization = self::createAuthorization($agentCode, $token, $httpMethod, $pathname);

            $apiURL = config('app.truxbet_api');
            $url = $apiURL . "/" . strtolower($path);

            $userCode = $prefix . sprintf("%04d", $user->id);

            $data = [
                'userCode' => $userCode,
                'nickname' => $userCode,
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => $authorization
                ])->post($url, $data)->throw();

                $data = $response->json();

                if ($data['status'] == 'OK') {
                    return true;
                }
            } catch (\Exception $ex) {
                Log::error('Truxbet User Create Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Truxbet User Create Request :  Excpetion. URL= ' . $url);
                Log::error('Truxbet User Create Request :  Excpetion. PARAMS= ' . json_encode($data));
            }

            return false;
        }

        public static function getUserBalance($href, $user, $prefix = self::TRUXBET_BLUEPREFIX)
        {
            $httpMethod = "GET";
            $path = "TransferApi/UserBalance";
            $pathname = strtolower($path);
            $agentCode = config('app.truxbet_agent');
            $token = config('app.truxbet_token');
            $authorization = self::createAuthorization($agentCode, $token, $httpMethod, $pathname);

            $apiURL = config('app.truxbet_api');
            $url = $apiURL . "/" . strtolower($path);

            $userCode = $prefix . sprintf("%04d", $user->id);

            $query = [
                'userCode' => $userCode
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => $authorization
                ])->get($url, $query)->throw();

                $data = $response->json();

                return intval($data['result']['balance']);
            } catch (\Illuminate\Http\Client\RequestException $ex) {
                if ($ex->response) {
                    $statusCode = $ex->response->status();
                    $body = $ex->response->json();
                    $msg = $body['status'] ?? 'Unknown error message';

                    Log::error("Truxbet User Balance Request Failed: $msg (HTTP $statusCode)");

                    if ($msg == 'UserNotFound') {
                        $userCreated = self::userCreate($href, $user, self::TRUXBET_BLUEPREFIX);
                    }
                } else {
                    Log::error('Truxbet User Balance Request Failed: No response object in exception.');
                }
            } catch (\Exception $ex) {
                Log::error('Truxbet User Balance Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Truxbet User Balance Request :  Excpetion. URL= ' . $url);
                Log::error('Truxbet User Balance Request :  Excpetion. PARAMS= ' . json_encode($query));
            }

            return -1;
        }

        public static function deposit($href, $user, $prefix = self::TRUXBET_BLUEPREFIX)
        {
            $httpMethod = "POST";
            $path = "TransferApi/UserDeposit";
            $pathname = strtolower($path);
            $agentCode = config('app.truxbet_agent');
            $token = config('app.truxbet_token');
            $authorization = self::createAuthorization($agentCode, $token, $httpMethod, $pathname);

            $apiURL = config('app.truxbet_api');
            $url = $apiURL . "/" . strtolower($path);

            $userCode = $prefix . sprintf("%04d", $user->id);
            $reference = uniqid();

            $body = [
                'userCode' => $userCode,
                'amount' => intval($user->balance),
                'reference' => $reference
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => $authorization
                ])->post($url, $body)->throw();

                $data = $response->json();

                return ['error' => false, 'amount' => intval($data['result']['newBalance'])];
            } catch (\Exception $ex) {
                Log::error('Truxbet User Deposit Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Truxbet User Deposit Request :  Excpetion. URL= ' . $url);
                Log::error('Truxbet User Deposit Request :  Excpetion. PARAMS= ' . json_encode($body));


            }
        }

        public static function withdrawAll($href, $user, $prefix = self::TRUXBET_BLUEPREFIX)
        {
            $balance = self::getUserBalance($href, $user, self::TRUXBET_BLUEPREFIX);

            if ($balance < 0) {
                return ['error' => true, 'amount' => $balance, 'msg' => 'getuserbalance return -1'];
            }

            if ($balance == 0) {
                return ['error' => false, 'amount' => $balance];
            }

            $httpMethod = "POST";
            $path = "TransferApi/UserWithdraw";
            $pathname = strtolower($path);
            $agentCode = config('app.truxbet_agent');
            $token = config('app.truxbet_token');
            $authorization = self::createAuthorization($agentCode, $token, $httpMethod, $pathname);

            $apiURL = config('app.truxbet_api');
            $url = $apiURL . "/" . strtolower($path);

            $userCode = $prefix . sprintf("%04d", $user->id);
            $reference = uniqid();

            $body = [
                'userCode' => $userCode,
                'reference' => $reference
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => $authorization
                ])->post($url, $body)->throw();

                $data = $response->json();

                return ['error' => false, 'amount' => $balance];
            } catch (\Exception $ex) {
                Log::error('Truxbet User Balance Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Truxbet User Balance Request :  Excpetion. URL= ' . $url);
                Log::error('Truxbet User Balance Request :  Excpetion. PARAMS= ' . json_encode($body));

                return ['error' => true, 'amount' => $balance, 'msg' => 'withdraw failed'];
            }
        }

        // Seamless Callback
        public function balance(Request $request)
        {
            $userCode = $request->query('userCode');
            $userId = preg_replace('/' . self::TRUXBET_BLUEPREFIX . '(\d+)/', '$1', $userCode);
            $user = \App\Models\User::where(['id' => $userId, 'role_id' => 1])->first();

            if (!$user) {
                Log::error('Truxbet CallBack Balance : Not found user. User Code = ' . $userCode);

                return response()->json([
                    'status' => 'Error',
                    'result' => [
                        'msg' => 'User Not Found'
                    ],
                ]);
            }

            if ($user->api_token == 'playerterminate') {
                Log::error('Truxbet CallBack Balance : terminated by admin. User Code = ' . $userCode);

                return response()->json([
                    'status' => 'Error',
                    'result' => [
                        'msg' => 'Terminated By Admin'
                    ],
                ]);
            }

            $parent = $user->findAgent();

            if ($parent->callback) {
                $username = explode("#P#", $user->username)[1];
                $response = CallbackController::userBalance($parent->callback, $username);

                if ($response['status'] == 1) {
                    $user->balance = $response['balance'];
                    $user->save();

                    return response()->json([
                        'status' => 'OK',
                        'result' => [
                            'balance' => $response['balance'],
                        ]
                    ]);
                }

                return response()->json([
                    'status' => 'Error',
                    'result' => [
                        'msg' => $response['msg']
                    ],
                ]);
            }

            return response()->json([
                'status' => 'OK',
                'result' => [
                    'balance' => $user->balance,
                ]
            ]);
        }

        public function setTransaction(Request $request)
        {
            $data = json_decode($request->getContent(), true);

            Log::info('---- Truxbet Transaction CallBack  : Request PARAMS= ' . json_encode($data));

            $userCode = $data['userCode'];
            $providerCode = $data['providerCode'];
            $gameCode = $data['gameCode'];
            $transactionId = $data['transactionId'];
            $transactionType = $data['transactionType'];
            $bet = $data['bet'];
            $win = $data['win'];
            $timestamp = $data['timestamp'];

            switch ($transactionType) {
                case "debit":
                    $betType = "bet";
                    break;
                case "credit":
                    $betType = "win";
                    break;
                case "both":
                default:
                    $betType = "betwin";
                    break;
            }

            $userId = intval(preg_replace('/' . self::TRUXBET_BLUEPREFIX . '(\d+)/', '$1', $userCode));
            $game = self::getGameObj($gameCode);
            $round_id = $providerCode . '#' . $gameCode . '#' . $transactionId . '#' . $transactionType . '#' . $timestamp;

            $checkGameStat = \App\Models\StatGame::where([
                'user_id' => $userId,
                'bet_type' => $betType,
                'roundid' => $round_id,
            ])->first();

            if ($checkGameStat) {
                return response()->json([
                    'status' => 'OK',
                    'result' => [
                        'balance' => $checkGameStat->balance,
                    ]
                ]);
            }

            \DB::beginTransaction();
            $user = \App\Models\User::lockforUpdate()->where(['id' => $userId, 'role_id' => 1])->first();

            if (!$user) {
                Log::error('Truxbet Bet Callback : Not found User. PARAMS= ' . json_encode($data));
                \DB::commit();
                return response()->json([
                    'status' => 'Error',
                    'result' => [
                        'msg' => 'User Not Found',
                    ],
                ]);
            }
            if ($user->api_token == 'playerterminate') {
                Log::error('Truxbet CallBack Balance : terminated by admin. User Code = ' . $userCode);
                \DB::commit();
                return response()->json([
                    'status' => 'Error',
                    'result' => [
                        'msg' => 'Terminated By Admin'
                    ],
                ]);
            }
            $user->balance = $user->balance - intval($bet) + intval(($win));
            $user->save();

            $gamename = $game['gamecode'] . '_truxbet';
            $time = date('Y-m-d H:i:s', time());
            $shop = \App\Models\ShopUser::where('user_id', $userId)->first();
            $category = \App\Models\Category::where('href', $game['href'])->first();

            $result = \App\Models\StatGame::create([
                'user_id' => $userId,
                'balance' => $user->balance,
                'bet' => $bet,
                'win' => $win,
                'bet_type' => $betType,
                'game' => $gamename,
                'type' => $game['type'],
                'percent' => 0,
                'percent_jps' => 0,
                'percent_jpg' => 0,
                'profit' => 0,
                'denomination' => 0,
                'date_time' => $time,
                'shop_id' => $shop ? $shop->shop_id : -1,
                'category_id' => $category ? $category->original_id : 0,
                'game_id' => $game['gameid'],
                'roundid' => $round_id,
                'transactionid' => $transactionId,
                'tablekey' => $game['gamecode'],
            ]);
            \DB::commit();

            if ($result['status'] == 1) {
                return response()->json([
                    'status' => 'OK',
                    'result' => [
                        'balance' => intval($result['balance']),
                    ],
                ]);
            }

            return response()->json([
                'status' => 'Error',
                'result' => [
                    'msg' => $result['msg'],
                ],
            ]);
        }

        // Data Feed Api
        public static function processGameRound($frompoint = -1, $checkduplicate = false)
        {
            // $timestamp = 0;

            // $last = \VanguardLTE\StatGame::where('game', 'like', '%truxbet')
            //     ->orderBy('date_time', 'desc')
            //     ->first();

            // if ($last) {
            //     $roundId = $last->roundid;
            //     $timestamp = explode("#", $roundId)[4];
            // }
            $timepoint = 0;
            if ($frompoint == -1) {
                $tpoint = \VanguardLTE\Settings::where('key', self::TRUXBET_PROVIDER . 'timepoint')->first();
                if ($tpoint) {
                    $timepoint = $tpoint->value;
                }
            } else {
                $timepoint = $frompoint;
            }

            $httpMethod = "GET";
            $path = "DataFeedApi/Transactions";
            $pathname = strtolower($path);
            $agentCode = config('app.truxbet_agent');
            $token = config('app.truxbet_token');
            $authorization = self::createAuthorization($agentCode, $token, $httpMethod, $pathname);

            $apiURL = config('app.truxbet_api');
            $url = $apiURL . "/" . strtolower($path);

            $query = [
                'timestamp' => $timepoint
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => $authorization
                ])->get($url, $query)->throw();

                $data = $response->json();
                $transactions = $data['result']['transactions'];

                foreach ($transactions as $item) {
                    $userCode = $item['userCode'];
                    $providerCode = $item['providerCode'];
                    $gameCode = $item['gameCode'];
                    $transactionId = $item['transactionId'];
                    $transactionType = $item['transactionType'];
                    $bet = $item['bet'];
                    $win = $item['win'];
                    $timestamp = $item['timestamp'];

                    switch ($transactionType) {
                        case "debit":
                            $betType = "bet";
                            break;
                        case "credit":
                            $betType = "win";
                            break;
                        case "both":
                        default:
                            $betType = "betwin";
                            break;
                    }

                    $userId = intval(preg_replace('/' . self::TRUXBET_BLUEPREFIX . '(\d+)/', '$1', $userCode));
                    $game = self::getGameObj($gameCode);
                    $round_id = $providerCode . '#' . $gameCode . '#' . $transactionId . '#' . $transactionType . '#' . $timestamp;

                    $checkGameStat = \VanguardLTE\StatGame::where([
                        'user_id' => $userId,
                        'bet_type' => $betType,
                        'roundid' => $round_id,
                    ])->first();

                    if ($checkGameStat) {
                        continue;
                    }

                    $gamename = $game['name'] . '_truxbet';
                    $time = date('Y-m-d H:i:s', intval($timestamp / 1000));
                    $shop = \VanguardLTE\ShopUser::where('user_id', $userId)->first();
                    $category = \VanguardLTE\Category::where('href', $game['href'])->first();

                    \VanguardLTE\StatGame::create([
                        'user_id' => $userId,
                        'balance' => $item['userAfter'],
                        'bet' => $bet,
                        'win' => $win,
                        'bet_type' => $betType,
                        'game' => $gamename,
                        'type' => $game['type'],
                        'percent' => 0,
                        'percent_jps' => 0,
                        'percent_jpg' => 0,
                        'profit' => 0,
                        'denomination' => 0,
                        'date_time' => $time,
                        'shop_id' => $shop ? $shop->shop_id : -1,
                        'category_id' => $category ? $category->original_id : 0,
                        'game_id' => $game['gameid'],
                        'roundid' => $round_id,
                        'transactionid' => $transactionId,
                        'tablekey' => $game['gamecode'],
                    ]);

                    if ($timepoint < $timestamp) {
                        $timestamp = $timepoint;
                    }
                }
            } catch (\Exception $ex) {
                Log::error('Truxbet Game Transaction List Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Truxbet Game Transaction List Request :  Excpetion. URL= ' . $url);
                Log::error('Truxbet Game Transaction List Request :  Excpetion. PARAMS= ' . json_encode($query));
            }

            if ($frompoint == -1) {

                if ($tpoint) {
                    $tpoint->update(['value' => $timepoint]);
                } else {
                    \VanguardLTE\Settings::create(['key' => self::TRUXBET_PROVIDER . 'timepoint', 'value' => $timepoint]);
                }
            }
        }

        // Create Auth Header
        public static function createAuthorization($agentCode, $secretKey, $httpMethod, $pathname)
        {
            $timestamp = round(microtime(true) * 1000);
            $uuid = uniqid();

            $dataToSign = $httpMethod . " " . $pathname . " " . $timestamp . " " . $uuid;
            $signature = hash_hmac('sha256', $dataToSign, $secretKey);

            $authorization = "HMAC-SHA256 $agentCode,$timestamp,$uuid,$signature";

            return $authorization;
        }
    }
}
