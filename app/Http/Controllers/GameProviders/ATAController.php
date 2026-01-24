<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class ATAController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        public function checkreference($reference)
        {
            $record = \App\Models\ATATransaction::Where('reference',$reference)->get()->first();
            return $record;
        }

        public function generateCode($limit){
            $code = 0;
            for($i = 0; $i < $limit; $i++) { $code .= mt_rand(0, 9); }
            return $code;
        }

        public function getGameObj($cat4)
        {
            $categories = \App\Models\Category::where(['provider' => 'ata', 'shop_id' => 0, 'site_id' => 0])->get();
            $gamelist = [];
            foreach ($categories as $category)
            {
                $gamelist = ATAController::getgamelist($category->href);
                if (count($gamelist) > 0 )
                {
                    foreach($gamelist as $game)
                    {
                        if ($game['name'] == $cat4)
                        {
                            if (isset($game['game']))
                            {
                                $game['name'] = $game['game'];
                            }
                            $game['cat_id'] = $category->original_id;
                            return $game;
                            break;
                        }
                    }
                }
            }
            return null;
        }


        public function microtime_string()
        {
            $microstr = sprintf('%.4f', microtime(TRUE));
            $microstr = str_replace('.', '', $microstr);
            return $microstr;
        }

        public static function sign()
        {
            return md5(config('app.ata_toporg') . config('app.ata_org') . config('app.ata_key'));
        }
        /*
        * FROM ATA, BACK API
        */

        public function endpoint($service, \Illuminate\Http\Request $request)
        {
            $response = [];
            switch ($service)
            {
                case 'playerinfo.json':
                    $response = $this->playerinfo($request);
                    break;
                case 'wager.json':
                    $response = $this->wager($request);
                    break;
                case 'cancelwager.json':
                    $response = $this->cancelwager($request);
                    break;
                case 'appendwagerresult.json':
                    $response = $this->appendwagerresult($request);
                    break;
                case 'endwager.json':
                    $response = $this->endwager($request);
                    break;
                case 'campaignpayout.json':
                    $response = $this->campaignpayout($request);
                    break;
                case 'getbalance.json':
                    $response = $this->getbalance($request);
                    break;
                default:
                    $response = ['code' => 1,];
            }

            return response()->json($response, 200);
        }

        public function playerinfo($request)
        {
            $org = $request->org;
            $sessiontoken = $request->sessiontoken;

            $user = \App\Models\User::lockForUpdate()->where('api_token',$sessiontoken)->first();
            if (!$user || !$user->hasRole('user')){
                return [
                    'code' => 1000,
                    'msg' => 'Not found user',
                ];
            }

            return [
                'code' => 0,
                'data' => [
                    'gender' => 'M',
                    'playerId' => strval($user->id),
                    'organization' => config('app.ata_org'),
                    'balance' => floatval($user->balance),
                    'currency' => 'KRW',
                    'applicableBonus' => 0,
                    'homeCurrency' => 'KRW',
                    'country' => 'KR',
                ],
            ];
        }

        public function getbalance($request)
        {
            $org = $request->org;
            $sessiontoken = $request->sessiontoken;
            $playerid = $request->playerid;

            $user = \App\Models\User::lockForUpdate()->where('api_token',$sessiontoken)->first();
            if (!$user || !$user->hasRole('user')){
                return [
                    'code' => 1000,
                    'msg' => 'Not found user',
                ];
            }

            return [
                'code' => 0,
                'data' => [
                    'playerId' => strval($user->id),
                    'organization' => config('app.ata_org'),
                    'currency' => 'KRW',
                    'applicableBonus' => 0,
                    'homeCurrency' => 'KRW',
                    'country' => 'KR',
                    'balance' => floatval($user->balance),
                    'bonus' => 0,
                ],
            ];
        }

        public function wager($request)
        {
            $org = $request->org;
            $sessiontoken = $request->sessiontoken;
            $playerid = $request->playerid;
            $amount = $request->amount;
            $reference = $request->reference;
            $cat1 = $request->cat1;
            $cat2 = $request->cat2;
            $cat3 = $request->cat3;
            $cat4 = $request->cat4;
            $cat5 = $request->cat5;
            $user = \App\Models\User::lockForUpdate()->find($playerid);
            if (!$user || !$user->hasRole('user')){
                return [
                    'code' => 1000,
                ];
            }
            if ($user->playing_game != null || $user->remember_token != $user->api_token)
            {
                return [
                    'code' => 1007,
                ];
            }
            $record = $this->checkreference($reference);
            if ($record)
            {
                return [
                    'code' => 0,
                    'data' => [
                        'gender' => 'M',
                        'playerId' => strval($user->id),
                        'organization' => config('app.ata_org'),
                        'balance' => floatval($user->balance),
                        'currency' => 'KRW',
                        'applicableBonus' => 0,
                        'homeCurrency' => 'KRW',
                        'country' => 'KR',
                    ],
                ];
            }
            
            if ($user->balance < $amount)
            {
                return [
                    'code' => 1006,
                ];
            }

            $user->balance = floatval(sprintf('%.4f', $user->balance - floatval($amount)));
            $user->save();

            $gameObj = $this->getGameObj($cat4);

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => floatval($amount), 
                'win' => 0, 
                'game' => $gameObj['name'] . '_' . $gameObj['href'], 
                'type' => 'slot',
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => $gameObj['cat_id'],
                'game_id' => $gameObj['gamecode'],
                'roundid' => 0,

            ]);

            $req = $request->all();

            $transaction = \App\Models\ATATransaction::create([
                'reference' => $reference, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($req),
                'refund' => 0
            ]);

            return [
                'code' => 0,
                'data' => [
                    'gender' => 'M',
                    'playerId' => strval($user->id),
                    'organization' => config('app.ata_org'),
                    'balance' => floatval($user->balance),
                    'currency' => 'KRW',
                    'applicableBonus' => 0,
                    'homeCurrency' => 'KRW',
                    'country' => 'KR',
                ],
            ];
        }

        public function cancelwager($request)
        {
            $org = $request->org;
            $playerid = $request->playerid;
            $reference = $request->reference;
            $user = \App\Models\User::lockForUpdate()->find($playerid);
            if (!$user || !$user->hasRole('user')){
                return [
                    'code' => 1000,
                ];
            }

            $record = $this->checkreference($reference);
            if ($record == null || $record->refund == 1)
            {
                return [
                    'code' => 0,
                    'data' => [
                        'playerId' => strval($user->id),
                        'organization' => config('app.ata_org'),
                        'balance' => floatval($user->balance),
                        'currency' => 'KRW',
                        'bonus' => 0,
                    ],
                ];
            }
            $data = json_decode($record->data);
            $amount = $data->amount;

            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
            $user->save();

            $gameObj = $this->getGameObj($data->cat4);

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => floatval($amount), 
                'game' => $gameObj['name'] . '_' . $gameObj['href'] . ' refund', 
                'type' => 'slot',
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => $gameObj['cat_id'],
                'game_id' => $gameObj['gamecode'],
                'roundid' => 0,
            ]);

            $record->update(['refund' => 1]);

            return [
                'code' => 0,
                'data' => [
                    'playerId' => strval($user->id),
                    'organization' => config('app.ata_org'),
                    'balance' => floatval($user->balance),
                    'currency' => 'KRW',
                    'bonus' => 0,
                ],
            ];
        }

        public function appendwagerresult($request)
        {
            $org = $request->org;
            $playerid = $request->playerid;
            $amount = $request->amount;
            $reference = $request->reference . '/' . $request->subreference;
            $cat1 = $request->cat1;
            $cat2 = $request->cat2;
            $cat3 = $request->cat3;
            $cat4 = $request->cat4;
            $cat5 = $request->cat5;
            
            $user = \App\Models\User::lockForUpdate()->find($playerid);
            if (!$user || !$user->hasRole('user')){
                return [
                    'code' => 1000,
                ];
            }
            $record = $this->checkreference($reference);
            if ($record)
            {
                return [
                    'code' => 0,
                    'data' => [
                        'playerId' => strval($user->id),
                        'organization' => config('app.ata_org'),
                        'balance' => floatval($user->balance),
                        'currency' => 'KRW',
                        'applicableBonus' => 0,
                        'bonus' => 0,
                        'homeCurrency' => 'KRW',
                    ],
                ];
            }

            if ($amount > 0)
            {
                $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
                $user->save();

                $gameObj = $this->getGameObj($cat4);

                \App\Models\StatGame::create([
                    'user_id' => $user->id, 
                    'balance' => floatval($user->balance), 
                    'bet' => 0, 
                    'win' => floatval($amount), 
                    'game' => $gameObj['name'] . '_' . $gameObj['href'], 
                    'type' => 'slot',
                    'percent' => 0, 
                    'percent_jps' => 0, 
                    'percent_jpg' => 0, 
                    'profit' => 0, 
                    'denomination' => 0, 
                    'shop_id' => $user->shop_id,
                    'category_id' => $gameObj['cat_id'],
                    'game_id' => $gameObj['gamecode'],
                    'roundid' => 0,
                ]);
            }

            $req = $request->all();

            $transaction = \App\Models\ATATransaction::create([
                'reference' => $reference, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($req),
                'refund' => 0
            ]);

            return [
                'code' => 0,
                'data' => [
                    'organization' => config('app.ata_org'),
                    'playerId' => strval($user->id),
                    'currency' => 'KRW',
                    'applicableBonus' => 0,
                    'homeCurrency' => 'KRW',
                    'balance' => floatval($user->balance),
                    'bonus' => 0,
                ],
            ];
        }

        public function endwager($request)
        {
            $org = $request->org;
            $playerid = $request->playerid;
            $amount = $request->amount;
            $reference = $request->reference . '/' . $request->subreference;
            $cat1 = $request->cat1;
            $cat2 = $request->cat2;
            $cat3 = $request->cat3;
            $cat4 = $request->cat4;
            $cat5 = $request->cat5;
            
            $user = \App\Models\User::lockForUpdate()->find($playerid);
            if (!$user || !$user->hasRole('user')){
                return [
                    'code' => 1000,
                ];
            }

            $record = $this->checkreference($reference);
            if ($record)
            {
                return [
                    'code' => 0,
                    'data' => [
                        'playerId' => strval($user->id),
                        'organization' => config('app.ata_org'),
                        'balance' => floatval($user->balance),
                        'currency' => 'KRW',
                        'applicableBonus' => 0,
                        'homeCurrency' => 'KRW',
                        'bonus' => 0,
                    ],
                ];
            }
            if ($amount > 0)
            {
                $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
                $user->save();

                $gameObj = $this->getGameObj($cat4);

                \App\Models\StatGame::create([
                    'user_id' => $user->id, 
                    'balance' => floatval($user->balance), 
                    'bet' => 0, 
                    'win' => floatval($amount), 
                    'game' => $gameObj['name'] . '_' . $gameObj['href'], 
                    'type' => 'slot',
                    'percent' => 0, 
                    'percent_jps' => 0, 
                    'percent_jpg' => 0, 
                    'profit' => 0, 
                    'denomination' => 0, 
                    'shop_id' => $user->shop_id,
                    'category_id' => $gameObj['cat_id'],
                    'game_id' => $gameObj['gamecode'],
                    'roundid' => 0,
                ]);
            }
            $req = $request->all();

            $transaction = \App\Models\ATATransaction::create([
                'reference' => $reference, 
                'timestamp' => $this->microtime_string(),
                'data' => json_encode($req),
                'refund' => 0
            ]);
            
            return [
                'code' => 0,
                'data' => [
                    'organization' => config('app.ata_org'),
                    'playerId' => strval($user->id),
                    'currency' => 'KRW',
                    'applicableBonus' => 0,
                    'homeCurrency' => 'KRW',
                    'balance' => floatval($user->balance),
                    'bonus' => 0,
                ],
            ];
        }

        public function campaignpayout($request)
        {
            $org = $request->org;
            $playerid = $request->playerid;
            $amount = $request->cash;
            $reference = $request->reference;
            $cat1 = $request->cat1;
            $cat2 = $request->cat2;
            $cat3 = $request->cat3;
            $cat4 = $request->cat4;
            $cat5 = $request->cat5;
            
            $user = \App\Models\User::lockForUpdate()->find($playerid);
            if (!$user || !$user->hasRole('user')){
                return [
                    'code' => 1000,
                ];
            }
            

            $user->balance = floatval(sprintf('%.4f', $user->balance + floatval($amount)));
            $user->save();

            $gameObj = $this->getGameObj($cat4);

            \App\Models\StatGame::create([
                'user_id' => $user->id, 
                'balance' => floatval($user->balance), 
                'bet' => 0, 
                'win' => floatval($amount), 
                'game' => $gameObj['name'] . '_' . $gameObj['href'] . ' bonus', 
                'type' => 'slot',
                'percent' => 0, 
                'percent_jps' => 0, 
                'percent_jpg' => 0, 
                'profit' => 0, 
                'denomination' => 0, 
                'shop_id' => $user->shop_id,
                'category_id' => $gameObj['cat_id'],
                'game_id' => $gameObj['gamecode'],
                'roundid' => 0,
            ]);

            return [
                'code' => 0,
                'data' => [
                    'organization' => config('app.ata_org'),
                    'playerId' => strval($user->id),
                    'currency' => 'KRW',
                    'applicableBonus' => 0,
                    'homeCurrency' => 'KRW',
                    'balance' => floatval($user->balance),
                    'bonus' => 0,
                ],
            ];
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
                'topOrg' => config('app.ata_toporg'),
                'org' => config('app.ata_org'),
                'sign' => ATAController::sign(),
                'type' => $href
            ];
            $resultdata = null;
            try {
                $response = Http::asForm()->post(config('app.ata_api') . '/getGameList', $data);
                if (!$response->ok())
                {
                    return [];
                }
                $resultdata = $response->json();
            }
            catch (Exception $ex)
            {
                return [];
            }

            $gameList = [];
            $slotgameString = ['Slot game', 'Video Slot','3-Reel Slot Machine', '5-Reel Slot Machine', 'SLOT','5x5 Grid Slot Machine','7x7 Grid Slot Machine','5x7 Grid Slot Machine','8x8 Grid Slot Machine','3x3 Grid Slot Machine','6x6 Grid Slot Machine'];
            $exceptGames = [ 150328,150331,150332,150325,150319,150320,150316,150312,150298,150295,150294,150268,150267,150266,150265,150264,150251,150250,150249,150244,150237,150236,150235,150232,150233,150212,150207,150196,150174,150209,150206,150200,150181,150202,150215,150211,150010,150205,150012];
            if ($resultdata['code'] == 0){

                foreach ($resultdata['data'] as $game)
                {
                    if (in_array($game['DCGameID'], $exceptGames))
                    {
                        continue;
                    }

                    if (in_array($game['GameType'] , $slotgameString) && $game['GameStatus'] == 1){ // need to check the string
                        if ($href=='nlc' && preg_match('/DX1$/',  $game['LogPara']))
                        {
                            continue;
                        }
                        if ($href=='png')
                        {
                            $icon_name = str_replace(' ', '_', $game['GameName']);
                            $icon_name = str_replace(':', '_', $icon_name);
                            // $icon_name = str_replace('\'', '_', $icon_name);
                            $icon_name = strtolower(preg_replace('/\s+/', '', $icon_name));
                            $gameList[] = [
                                'provider' => 'ata',
                                'href' => $href,
                                'gamecode' => $game['DCGameID'],
                                'name' => $game['LogPara'],
                                'game' => preg_replace('/\s+/', '', $game['GameName']),
                                'title' => \Illuminate\Support\Facades\Lang::has('gameprovider.'.$game['GameName'], 'ko')?__('gameprovider.'.$game['GameName']):$game['GameName'],
                                'icon' => '/frontend/Default/ico/png/'. $icon_name . '.jpg',
                            ];
                        }
                        else
                        {
                            $gameList[] = [
                                'provider' => 'ata',
                                'href' => $href,
                                'gamecode' => $game['DCGameID'],
                                'name' => $game['LogPara'],
                                'game' => preg_replace('/\s+/', '', $game['GameName']),
                                'title' => \Illuminate\Support\Facades\Lang::has('gameprovider.'.$game['GameName'], 'ko')?__('gameprovider.'.$game['GameName']):$game['GameName'],
                                'icon' => $href=='aux'?('/frontend/Default/ico/ata/avatarux/'. $game['DCGameID'] . '.png'):('/frontend/Default/ico/ata/'.$href.'/'. $game['DCGameID'] . '.png'),
                            ];
                        }
                    }
                }
                \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            }
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
            $data = [
                'loginname' => strval($user->id),
                'key' => $user->api_token,
                'currency' => 'KRW',
                'lang' => 'ko',
                'gameid' => $gamecode,
                'org' => config('app.ata_org'),
                'home' => url('/'),
                'fullscreen' => 'no',
                'channel' => ($detect->isMobile() || $detect->isTablet())?'mobile':'pc',
            ];

            $resultdata = null;
            try {
                $response = Http::post(config('app.ata_api') . '/launchClient.html', $data);
                if (!$response->ok())
                {
                    Log::error('ATA : makegamelink request failed. ' . json_encode($data));
                    return null;
                }
                $resultdata = $response->json();
                if (!$resultdata || $resultdata['code'] != 0)
                {
                    Log::error('ATA : makegamelink response failed. ' . json_encode($data) . '||' . $response->body());

                }
            }
            catch (Exception $ex)
            {
                Log::error('ATA : makegamelink request failed. ' . json_encode($data) . $e->getMessage());
                return null;
            }
            if ($resultdata)
            {
                if ($resultdata['code'] == 0)
                {
                    return $resultdata['data']['launchurl'];
                }
            }
            
            return null;
        }

        public static function getgamelink($gamecode)
        {
            $url = ATAController::makegamelink($gamecode);
            if ($url)
            {
                return ['error' => false, 'data' => ['url' => $url]];
            }
            return ['error' => true, 'msg' => '로그인하세요'];            
        }

    }

}
