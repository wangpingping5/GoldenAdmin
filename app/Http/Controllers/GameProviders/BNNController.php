<?php 
namespace App\Http\Controllers\GameProviders
{
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Log;
    class BNNController extends \App\Http\Controllers\Controller
    {
        /*
        * UTILITY FUNCTION
        */

        const BNN_PROVIDER = 'bnn';
        public static function getGameObj($uuid)
        {
            $gamelist = BNNController::getgamelist($uuid);
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
            return null;
        }

        /*
        */

        
        /*
        * FROM CONTROLLER, API
        */

        
        public static function getuserbalance($gid, $user) {
            $url = config('app.bnn_api') . '/v1/user-money';
            $key = config('app.bnn_key');
            $gid = str_replace('-', '_', $gid);
            $params = [
                'key' => $key,
                'uid' => self::BNN_PROVIDER . $user->id,
                'gid' => $gid
            ];
    
            $response = Http::get($url, $params);
            
            $balance = -1;
            if ($response->ok()) {
                $res = $response->json();
    
                if ($res['ret'] == 1) {
                    foreach ($res['data'] as $data )
                    {
                        if ($data['uid'] == self::BNN_PROVIDER . $user->id)
                        {
                            foreach ($data['money'] as $money)
                            {
                                if ($money['gid'] == $gid)
                                {
                                    $balance = $money['money'];
                                    break;
                                }
                            }
                        }
                    }
                }
                else
                {
                    Log::error('BNNgetuserbalance : return failed. ' . $res['msg']);
                }
            }
            else
            {
                Log::error('BNNgetuserbalance : response is not okay. ' . $response->body());
            }
            return $balance;
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
            //add Lobby Game item
            array_push($gameList, [
                'provider' => self::BNN_PROVIDER,
                'href' => $href,
                'symbol' => $href,
                'gamecode' => $href,
                'enname' => $href . '_Casino',
                'name' => $href . '_Casino',
                'title' => \Illuminate\Support\Facades\Lang::has('gameprovider.'.$href)? __('gameprovider.'.$href):__('gameprovider.Lobby'),
                'icon' => '/frontend/Default/ico/bnn/bnn_Casino.jpg',
                'type' => 'table',
                'view' => 1
            ]);
            \Illuminate\Support\Facades\Redis::set($href.'list', json_encode($gameList));
            return $gameList;
            
        }

        public static function makegamelink($gamecode, $user) 
        {
            $url = config('app.bnn_api') . '/v1/game-url';
            $key = config('app.bnn_key');
            $gamecode = str_replace('-', '_', $gamecode);
            $params = [
                'key' => $key,
                'uid' => self::BNN_PROVIDER . $user->id,
                'gid' => $gamecode,
                'min' => 5000,
                'max' => 3000000
            ];

            $response = Http::get($url, $params);
            if (!$response->ok())
            {
                Log::error('BNNGetLink : Game Session request failed. ' . $response->body());
                return ['error' => true, 'data' => 'Game Session request failed'];
            }
            $data = $response->json();
            if ($data==null || ($data['ret']!=1 ))
            {
                Log::error('BNNGetLink : Game Session result failed. ' . ($data==null?'null':$data['msg']));
                return null;
            }

            $url = $data['url'];

            return $url;
        }
        public static function withdrawAll($gamecode,$user)
        {
            $url = config('app.bnn_api') . '/v1/withdrawal-all';
            $key = config('app.bnn_key');
    
            $params = [
                'key' => $key,
                'uid' => self::BNN_PROVIDER . $user->id,
            ];
            $response = Http::get($url, $params);

            if (!$response->ok())
            {
                return ['error'=>true, 'amount'=>0, 'msg'=>'response not ok'];
            }
            $data = $response->json();

            if ($data==null || ($data['ret']!=1 )) //WithdrawAll failed
            {
                Log::error('withdrawAll : WithdrawAll result failed. ' . self::BNN_PROVIDER . $user->id . ':' .($data==null?'null':$data['msg']));
                return ['error'=>true, 'amount'=>0];
            }
            return ['error'=>false, 'amount'=>$data['money']];
        }

        public static function makelink($gamecode, $userid)
        {
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                Log::error('BNNMakeLink : Does not find user ' . $userid);
                return null;
            }

            $key = config('app.bnn_key');

            $balance = BNNController::getuserbalance($gamecode, $user);
            if ($balance == -1)
            {
                return null;
            }
            if ($balance != $user->balance)
            {
                //withdraw all balance
                $data = BNNController::withdrawAll($gamecode,$user);
                if ($data['error'])
                {
                    return null;
                }
                //Add balance

                if ($user->balance > 0)
                {
                    $url = config('app.bnn_api') . '/v1/deposit';
                    $gamecode1 = str_replace('-', '_', $gamecode);
                    $params = [
                        'key' => $key,
                        'gid' => $gamecode1,
                        'uid' => self::BNN_PROVIDER . $user->id,
                        'money' => (int)$user->balance
                    ];

                    $response = Http::get($url, $params);
                    if (!$response->ok())
                    {
                        Log::error('BNNMakeLink : Deposit request failed. ' . $response->body());
                        return null;
                    }
                    $data = $response->json();
                    if ($data==null || ($data['ret']!=1 )) //Deposit failed
                    {
                        Log::error('BNNMakeLink : Deposit result failed. ' . ($data==null?'null':$data['msg']) . ' for user id ' . $user->id . ', balance=' . $user->balance);
                        return null;
                    }
                }
            }
            
            return '/followgame/bnn/'.$gamecode;

        }

        public static function getgamelink($gamecode)
        {
            $user = auth()->user();
            // if ($user->playing_game != null) //already playing game.
            // {
            //     return ['error' => true, 'data' => '이미 실행중인 게임을 종료해주세요. 이미 종료했음에도 불구하고 이 메시지가 계속 나타난다면 매장에 문의해주세요.'];
            // }
            return ['error' => false, 'data' => ['url' => route('frontend.providers.waiting', [self::BNN_PROVIDER, $gamecode])]];
        }

        public static function gamerounds($timepoint)
        {
            $url = config('app.bnn_api') . '/v2/history/idx';
            $key = config('app.bnn_key');
            $pageSize = 1000;
            $params = [
                'key' => $key,
                'page_size' => $pageSize,
                'game_idx' => $timepoint
            ];
            $response = null;

            try {
                $response = Http::get($url, $params);
            } catch (\Exception $e) {
                Log::error('BNNGameRounds : GameRounds request failed. ' . $e->getMessage());
                return null;
            }

            if (!$response->ok())
            {
                Log::error('BNNGameRounds : GameRounds request failed. ' . $response->body());
                return null;
            }

            $data = $response->json();
            return $data;
        }

        public static function processGameRound()
        {
            $tpoint = \App\Models\Settings::where('key', 'BNNtimepoint')->first();
            if ($tpoint)
            {
                $timepoint = $tpoint->value;
            }
            else
            {
                $timepoint = 0;
            }

            //get category id
            
            $data = BNNController::gamerounds($timepoint);
            $count = 0;
            if ($data && $data['ret'] == 1 && $data['total'] > 0)
            {
                
                foreach ($data['data'] as $round)
                {
                    $bet = $round['bet_money'];
                    $win = $round['result_money'];
                    $href = $round['gid'];
                    $href = str_replace('_', '-', $round['gid']);
                    $category = \App\Models\Category::where(['provider' => self::BNN_PROVIDER, 'href' =>$href])->first();
                    if ($round['extra'] != null)
                    {
                        $balance = $round['extra']['after_money'];
                    }
                    else
                    {
                        $balance = -1;
                    }
                    $time = $round['game_date'];

                    $userid = preg_replace('/'. self::BNN_PROVIDER .'(\d+)/', '$1', $round['uid']) ;
                    $shop = \App\Models\ShopUser::where('user_id', $userid)->first();
                    $gameName = $href;
                    \App\Models\StatGame::create([
                        'user_id' => $userid, 
                        'balance' => $balance, 
                        'bet' => $bet, 
                        'win' => $win, 
                        'game' =>$gameName . '_bnn', 
                        'type' => 'table',
                        'percent' => 0, 
                        'percent_jps' => 0, 
                        'percent_jpg' => 0, 
                        'profit' => 0, 
                        'denomination' => 0, 
                        'date_time' => $time,
                        'shop_id' => $shop?$shop->shop_id:0,
                        'category_id' => isset($category)?$category->id:0,
                        'game_id' => $gameName,
                        'roundid' => $round['gubun'] . '_' . $round['game_idx'],
                    ]);
                    $timepoint =  $round['game_idx'];
                    $count = $count + 1;
                }

                $timepoint = $timepoint + 1;
                if ($tpoint)
                {
                    $tpoint->update(['value' => $timepoint]);
                    $tpoint->save();
                }
                else
                {
                    \App\Models\Settings::create(['key' => 'BNNtimepoint', 'value' => $timepoint]);
                }
            }
            return [$count, $timepoint];
        }

        public static function getAgentBalance()
        {
            $url = config('app.bnn_api') . '/v1/agent-money';
            $key = config('app.bnn_key');
            $params = [
                'key' => $key,
            ];
            $response = null;

            try {
                $response = Http::get($url, $params);
                $data = $response->json();
                return $data['money'];
            } catch (\Exception $e) {
                Log::error('BNNAgentMoney : request failed. ' . $e->getMessage());
                return -1;
            }
        }
    }

}
