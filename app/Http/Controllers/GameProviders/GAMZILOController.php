<?php
namespace App\Http\Controllers\GameProviders {

    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Redis;
    use Illuminate\Http\Request;

    class GAMZILOController extends \App\Http\Controllers\Controller
    {

        const GAMZILO_PROVIDER = 'gamzilo';
        const GAMZILO_BLUEPREFIX = 'blue';
        const GAMZILO_PPVERIFY_PROVIDER = 'gamzilov';
        const GAMZILO_PP_HREF = 'gamzilo-pp';

        const GAMZILO_GAME_IDENTITY = [
            //==== SLOT ====
            'gamzilo-evo' => ['thirdname' => 'Evolution', 'type' => 'casino', 'symbol' => 'evo', 'skin' => 'CASINO'],                // 에볼루션
            'gamzilo-pp' => ['thirdname' => 'PragmaticPlay', 'type' => 'slot', 'symbol' => 'pp', 'skin' => 'SLOT'],                  // 프라그마틱
        ];

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
            $agentCode = config('app.gamzilo_agent');
            $token = config('app.gamzilo_token');
            $authorization = self::createAuthorization($agentCode, $token, $httpMethod, $pathname);

            $apiURL = config('app.gamzilo_api');
            $url = $apiURL . "/" . strtolower($path);

            $category = self::GAMZILO_GAME_IDENTITY[$href];
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
                        'provider' => self::GAMZILO_PROVIDER,
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
                Log::error('Gamzilo Game List Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Gamzilo Game List Request :  Excpetion. URL= ' . $url);
                Log::error('Gamzilo Game List Request :  Excpetion. PARAMS= ' . json_encode($query));
            }

            Redis::set($href . 'list', json_encode($games));

            return $games;
        }

        public static function getGameObj($uuid)
        {
            foreach (self::GAMZILO_GAME_IDENTITY as $ref => $value) {
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
            if (isset(self::GAMZILO_GAME_IDENTITY[$gamecode])) {
                $gamelist = self::getgamelist($gamecode);

                if (count($gamelist) > 0) {
                    foreach ($gamelist as $g) {
                        if ($g['view'] == 1) {
                            $gamecode = $g['gamecode'];
                            break;
                        }
                    }

                }
            }

            $url = self::makegamelink($gamecode);

            if ($url) {
                return ['error' => false, 'data' => ['url' => $url]];
            }

            return ['error' => true, 'msg' => '게임실행 오류입니다'];
        }

        public static function makegamelink($gamecode, $user = null, $prefix = self::GAMZILO_BLUEPREFIX)
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
            $agentCode = config('app.gamzilo_agent');
            $token = config('app.gamzilo_token');
            $authorization = self::createAuthorization($agentCode, $token, $httpMethod, $pathname);

            $apiURL = config('app.gamzilo_api');
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
                Log::error('Gamzilo Game Launch Request :  Excpetion. exception= ' . $ex->getMessage());
                Log::error('Gamzilo Game Launch Request :  Excpetion. PARAMS= ' . json_encode($body));

                return null;
            }
        }

        public function balance(Request $request)
        {
            $userCode = $request->query('userCode');
            $userId = preg_replace('/' . self::GAMZILO_BLUEPREFIX . '(\d+)/', '$1', $userCode);
            $user = \App\Models\User::where(['id' => $userId, 'role_id' => 1])->first();

            if (!$user) {
                Log::error('Gamzilo CallBack Balance : Not found user. User Code = ' . $userCode);

                return response()->json([
                    'status' => 'Error',
                    'result' => [
                        'msg' => 'User Not Found'
                    ],
                ]);
            }

            if ($user->api_token == 'playerterminate') {
                Log::error('Gamzilo CallBack Balance : terminated by admin. User Code = ' . $userCode);

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

            Log::info('---- Gamzilo Transaction CallBack  : Request PARAMS= ' . json_encode($data));

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

            $userId = intval(preg_replace('/' . self::GAMZILO_BLUEPREFIX . '(\d+)/', '$1', $userCode));
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
                Log::error('Gamzilo Bet Callback : Not found User. PARAMS= ' . json_encode($data));
                \DB::commit();
                return response()->json([
                    'status' => 'Error',
                    'result' => [
                        'msg' => 'User Not Found',
                    ],
                ]);
            }

            $user->balance = $user->balance - intval($bet) + intval(($win));
            $user->save();

            $gamename = $game['gamecode'] . '_gamzilo';
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
