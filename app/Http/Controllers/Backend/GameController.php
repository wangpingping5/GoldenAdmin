<?php

namespace App\Http\Controllers\Backend;

use Log;

{
    class GameController extends \App\Http\Controllers\Controller
    {
        public function __construct()
        {
            $this->middleware('auth');
            $this->middleware('permission:access.admin.panel');
            $this->middleware( function($request,$next) {
                if (!auth()->user()->isInOutPartner())
                {
                    return response('허용되지 않은 접근입니다.', 401);
                }
                return $next($request);
            }
            );
        }

        public function category_update(\Illuminate\Http\Request $request)
        {
            $categoryid = $request->cat_id;
            $data = $request->only([
                'status',
                'view'
            ]);
            $userid = $request->user_id;
            $availablePartners = auth()->user()->availableUsers();
            $user = \App\Models\User::where('id',$userid)->first();
            if (!$user || !in_array($userid, $availablePartners))
            {
                return redirect()->back()->withErrors(['에이전트를 찾을수 없습니다']);
            }
            $shops = $user->shops(true);
            if ($user->hasRole('admin'))
            {
                $shops[] = 0; // include all domain settings
            }
            if (count($shops) == 0)
            {
                return redirect()->back()->withErrors(['하부매장이 없습니다']);
            }

            if ($user->hasRole('comaster')) //if user is comaster, shop_id 0 is  allowed
            {
                Log::info(json_encode($shops));
            }

            \App\Models\Category::where('original_id', $categoryid)->whereIn('shop_id', $shops)->update($data);
            return redirect()->back()->withSuccess(['게임상태를 업데이트했습니다']);
        }

        public function game_category(\Illuminate\Http\Request $request)
        {
            set_time_limit(0);
            $excat = ['hot', 'new', 'card','bingo','roulette', 'keno', 'novomatic','wazdan','skywind'];
            $categories = \App\Models\Category::where(['shop_id' => 0, 'site_id' => 0])->whereNotIn('href', $excat)->orderBy('position', 'desc');
            $users = \App\Models\User::where('id', auth()->user()->id);
            if($request->search != ''){
                if ($request->kind == '')
                {
                    $users = \App\Models\User::whereIn('status', [\App\Support\Enum\UserStatus::ACTIVE, \App\Support\Enum\UserStatus::BANNED]);
                    $users = $users->where('username', 'like', '%' . $request->search . '%')->where('role_id', '>=', 3);
                    if ($request->role != '')
                    {
                        $users = $users->where('role_id', $request->role);
                    }
                }
                if ($request->kind == 1)
                {
                    $categories = $categories->where('title', 'like', '%' . $request->search . '%');
                }
            }
            $categories = $categories->paginate(20);
            $users = $users->get();
            return view('game.patner', compact('users','categories'));
        }

        public function domain_category(\Illuminate\Http\Request $request)
        {
            set_time_limit(0);
            $availablePartners = auth()->user()->hierarchyPartners();
            $availablePartners[] = auth()->user()->id;
            $sites = \App\Models\WebSite::orderby('id')->whereIn('adminid', $availablePartners);
            $searchName = 'domain';
            $searchValue = '';
            if($request->search != ''){
                $searchValue = $request->search;
            }
            if($request->kind != ''){
                if($request->kind == 1){
                    $searchName = 'username';
                }else if($request->kind == 2){
                    $searchName = 'title';
                }else if($request->kind == 3){
                    $searchName = 'provider';
                }
            }
            if($searchName != '' && $searchValue != ''){
                if($searchName == 'domain'){
                    $sites = $sites->where($searchName, 'like', '%' . $searchValue . '%');
                }
                if($searchName == 'username'){
                    $comaster = \App\Models\User::where('username', $searchValue)->first();
                    if (!$comaster || !in_array( $comaster->id, $availablePartners))
                    {
                        return redirect()->back()->withErrors(['총본사를 찾을수 없습니다']);
                    }
                    $sites = $sites->where('adminid', $comaster->id);
                }
            }
            $sites = $sites->paginate(5);
            return view('game.domain', compact('sites'));
        }
        public function domain_provider_update(\Illuminate\Http\Request $request)
        {
            $availablePartners = auth()->user()->hierarchyPartners();
            $availablePartners[] = [auth()->user()->id];
            $siteIds = \App\Models\WebSite::orderby('id')->whereIn('adminid', $availablePartners)->get()->pluck('id')->toArray();

            $categoryid = $request->cat_id;
            $provider = $request->provider;
            $category = \App\Models\Category::where('id', $categoryid)->first();
            if (!$category || !in_array($category->site_id, $siteIds))
            {
                return redirect()->back()->withErrors(['게임을 찾을수 없습니다']);
            }

            $site_id = $category->site_id;
            $site = \App\Models\WebSite::where('id', $site_id)->first();
            if (!$site)
            {
                return redirect()->back()->withErrors(['도메인을 찾을수 없습니다']);
            }

            $newcat = null;
            if ($provider == 1)
            {
                $newcat = \App\Models\Category::where([
                    'title' => $category->title,
                    'site_id' => $site_id,
                    'shop_id' => 0
                    ]
                    )->whereNotNull('provider')->first();
            }
            else
            {
                $newcat = \App\Models\Category::where([
                    'title' => $category->title,
                    'site_id' => $site_id,
                    'shop_id' => 0
                    ]
                    )->whereNull('provider')->first();
            }
            if (!$newcat)
            {
                return redirect()->back()->withErrors(['교체할 제공사가 없습니다']);
            }

            $admin = $site->admin;
            $availableShops = $admin->shops(true);
            // $availableShops = $admin->availableShops();

            \App\Models\Category::where('original_id' , $category->original_id)->whereIn('shop_id', $availableShops)->update(['view' => 0]);
            \App\Models\Category::where('original_id' , $category->original_id)->where('site_id', $site_id)->update(['view' => 0]);

            \App\Models\Category::where('original_id' , $newcat->original_id)->whereIn('shop_id', $availableShops)->update(['view' => 1]);
            \App\Models\Category::where('original_id' , $newcat->original_id)->where('site_id', $site_id)->update(['view' => 1]);

            return redirect()->back()->withSuccess(['게임제공사를 교체했습니다']);

        }
        public function domain_update(\Illuminate\Http\Request $request)
        {
            $categoryid = $request->cat_id;
            $data = $request->only([
                'status',
                'view'
            ]);
            $category = \App\Models\Category::where('id', $categoryid)->first();
            // $status = $request->status;
            // $view = $request->view;
            if (!auth()->user()->hasRole('admin')) //if user is not admin, could not change category view
            {
                if(auth()->user()->isInoutPartner() && isset(auth()->user()->sessiondata()['swiching']) && auth()->user()->sessiondata()['swiching']==1 && $category->title=='Pragmatic Play')
                {
                    $data['status']=1;
                }
                else
                {
                    unset($data['view']);
                }
            }

            $availablePartners = auth()->user()->hierarchyPartners();
            $availablePartners[] = [auth()->user()->id];
            $siteIds = \App\Models\WebSite::orderby('id')->whereIn('adminid', $availablePartners)->get()->pluck('id')->toArray();

            if (!$category  || !in_array($category->site_id, $siteIds))
            {
                return response()->json(['error'=>true, 'msg'=> '게임을 찾을수 없습니다']);
            }
            // $category->update(['view' => $status]);

            $site_id = $category->site_id;
            $site = \App\Models\WebSite::where('id', $site_id)->first();
            if (!$site)
            {
                return response()->json(['error'=>true, 'msg'=> '도메인을 찾을수 없습니다']);
            }
            //check if admin category is enabled
            $orgCategory = \App\Models\Category::where('id' , $category->original_id)->first();
            if (!auth()->user()->hasRole('admin') && ($orgCategory && $orgCategory->status == 0))
            {
                return response()->json(['error'=>true, 'msg'=> '상위파트너에 의하여 조작이 불가능한 게임입니다.']);
            }
            $admin = $site->admin;
            $availableShops = $admin->availableShops();
            //remove 0 shop id
            if (($key = array_search(0, $availableShops)) !== false) {
                unset($availableShops[$key]);
            }
            \App\Models\Category::where('original_id' , $category->original_id)->whereIn('shop_id', $availableShops)->update($data);
            \App\Models\Category::where('original_id' , $category->original_id)->where('site_id', $site_id)->update($data);
            return response()->json(['error'=>false, 'msg'=> '게임상태를 업데이트했습니다.']);
        }

        public function game_update(\Illuminate\Http\Request $request)
        {
            $gameid = $request->game_id;
            $value = $request->value;
            $userid = $request->user_id;
            $field = $request->field;

            $user = \App\Models\User::where('id',$userid)->first();
            if (!$user)
            {
                return redirect()->back()->withErrors(['에이전트를 찾을수 없습니다']);
            }
            $shops = $user->shops(true);
            if ($user->hasRole('admin'))
            {
                $shops[] = 0;
            }
            if (count($shops) == 0)
            {
                return redirect()->back()->withErrors(['하부매장이 없습니다']);
            }
            \App\Models\Game::where('original_id', $gameid)->whereIn('shop_id', $shops)->update([$field => $value]);
            return redirect()->back()->withSuccess(['게임상태를 업데이트했습니다']);
        }



        public function game_game(\Illuminate\Http\Request $request)
        {
            set_time_limit(0);
            $user_id = $request->user_id;
            $cat_id = $request->cat_id;

            $category = \App\Models\Category::where('id', $cat_id)->first();
            if (!$category)
            {
                return redirect()->back()->withErrors(['게임사를 찾을수 없습니다']);
            }
            $gamecategory = \App\Models\GameCategory::where('game_categories.category_id', $cat_id);
            $gamecategory = $gamecategory->join('games', 'games.id', '=', 'game_categories.game_id');
            if($request->search != ''){
                $gamecategory = $gamecategory->where('games.name', 'like', '%' . $request->search . '%');
            }
            $games = $gamecategory->select('games.*')->paginate(20);
            $user = \App\Models\User::where('id', $user_id)->first();
            if (!$user)
            {
                return redirect()->back()->withErrors(['에이전트를 찾을수 없습니다']);
            }
            return view('game.game', compact('user','games','category'));
        }
        public function game_transaction(\Illuminate\Http\Request $request)
        {
            $statistics = \App\Models\BankStat::select('bank_stat.*')->orderBy('bank_stat.created_at', 'DESC');

            $start_date = date("Y-m-1 0:0:0");
            $end_date = date("Y-m-d H:i:s");

            if ($request->dates != '')
            {
                $start_date = preg_replace('/T/',' ', $request->dates[0]);
                $end_date = preg_replace('/T/',' ', $request->dates[1]);            
            }
            $statistics = $statistics->where('bank_stat.created_at', '>=', $start_date);
            $statistics = $statistics->where('bank_stat.created_at', '<=', $end_date );
            $total = [
                'add' => (clone $statistics)->where('type', 'add')->sum('sum'),
                'out' => (clone $statistics)->where('type', 'out')->sum('sum'),
            ];
            $statistics = $statistics->paginate(20);
            return view('backend.argon.game.transaction', compact('statistics','total'));
        }
        public function gamebanks_setting(\Illuminate\Http\Request $request)
        {
            \App\Models\Settings::updateOrCreate(['key' => 'minslot1'], ['value' => $request->minslot1]);
            \App\Models\Settings::updateOrCreate(['key' => 'maxslot1'], ['value' => $request->maxslot1]);
            \App\Models\Settings::updateOrCreate(['key' => 'minslot2'], ['value' => $request->minslot2]);
            \App\Models\Settings::updateOrCreate(['key' => 'maxslot2'], ['value' => $request->maxslot2]);
            \App\Models\Settings::updateOrCreate(['key' => 'minslot3'], ['value' => $request->minslot3]);
            \App\Models\Settings::updateOrCreate(['key' => 'maxslot3'], ['value' => $request->maxslot3]);
            \App\Models\Settings::updateOrCreate(['key' => 'minslot4'], ['value' => $request->minslot4]);
            \App\Models\Settings::updateOrCreate(['key' => 'maxslot4'], ['value' => $request->maxslot4]);
            \App\Models\Settings::updateOrCreate(['key' => 'minslot5'], ['value' => $request->minslot5]);
            \App\Models\Settings::updateOrCreate(['key' => 'maxslot5'], ['value' => $request->maxslot5]);
            
            
            \App\Models\Settings::updateOrCreate(['key' => 'minbonus1'], ['value' => $request->minbonus1]);
            \App\Models\Settings::updateOrCreate(['key' => 'maxbonus1'], ['value' => $request->maxbonus1]);
            \App\Models\Settings::updateOrCreate(['key' => 'minbonus2'], ['value' => $request->minbonus2]);
            \App\Models\Settings::updateOrCreate(['key' => 'maxbonus2'], ['value' => $request->maxbonus2]);
            \App\Models\Settings::updateOrCreate(['key' => 'minbonus3'], ['value' => $request->minbonus3]);
            \App\Models\Settings::updateOrCreate(['key' => 'maxbonus3'], ['value' => $request->maxbonus3]);
            \App\Models\Settings::updateOrCreate(['key' => 'minbonus4'], ['value' => $request->minbonus4]);
            \App\Models\Settings::updateOrCreate(['key' => 'maxbonus4'], ['value' => $request->maxbonus4]);
            \App\Models\Settings::updateOrCreate(['key' => 'minbonus5'], ['value' => $request->minbonus5]);
            \App\Models\Settings::updateOrCreate(['key' => 'maxbonus5'], ['value' => $request->maxbonus5]);
            
            \App\Models\Settings::updateOrCreate(['key' => 'reset_bank'], ['value' => $request->reset_bank]);
            return redirect()->back()->withSuccess(['환수금 설정이 업데이트되었습니다.']);
        }
        public function game_bankstore(\Illuminate\Http\Request $request)
        {
            $bank_type = $request->type;
            $batch = $request->batch;
            $bank_id = $request->id;
            $act = $request->balancetype;
            $amount = $request->amount;
            $master = $request->master;

            if ($request->outAll == '1') //bank clear
            {
                if ($bank_type=='bonus')
                {
                    if ($batch=='1')
                    {
                        $bonus_bank = \App\Models\BonusBank::where('master_id', $request->master)->get();
                    }
                    else
                    {
                        $bonus_bank = \App\Models\BonusBank::where('id', $bank_id)->get();
                    }

                    foreach ($bonus_bank as $bb){
                        $master = \App\Models\User::where('id', $bb->master_id)->first();
                        if ($master)
                        {
                            $name = $master->username;
                            $shop_id = 0;
                            $old = $bb->bank;
                            $bb->update(['bank' => 0]);
                            $type = 'bonus';
                            $game = ' General';
                            if ($bb->game_id!=0)
                            {
                                $game = $bb->game->title;
                            }
                            \App\Models\BankStat::create([
                                'name' => ucfirst($type) . "[$name]-$game", 
                                'user_id' => \Illuminate\Support\Facades\Auth::id(), 
                                'type' =>  ($old<0)?'add':'out', 
                                'sum' => abs($old), 
                                'old' => $old, 
                                'new' => 0, 
                                'shop_id' => $shop_id
                            ]);
                        }
                        
                    }
                }
                elseif ($bank_type=='bonusmax')
                {
                    if ($batch=='1')
                    {
                        $bonus_bank = \App\Models\BonusBank::where('master_id', $request->master)->get();
                    }
                    else
                    {
                        $bonus_bank = \App\Models\BonusBank::where('id', $bank_id)->get();
                    }

                    foreach ($bonus_bank as $bb){
                        $bb->update(['max_bank' => 0]);
                    }
                }
                else
                {

                    if ($batch=='1')
                    {
                        $shops = auth()->user()->availableShops();
                        $gamebank = \App\Models\GameBank::whereIn('shop_id', $shops)->get();
                    }
                    else
                    {
                        $gamebank = \App\Models\GameBank::where('id', $bank_id)->get();
                    }
                    foreach ($gamebank as $gb){
                        $shop = $gb->shop;
                        $name = $shop->name;
                        $old = $gb->slots;
                        $gb->update(['slots' => 0]);
                        $shop_id = $gb->shop_id;
                        $type = 'slots';
                        \App\Models\BankStat::create([
                            'name' => ucfirst($type) . "[$name]", 
                            'user_id' => \Illuminate\Support\Facades\Auth::id(), 
                            'type' => (($old<0)?'add':'out'), 
                            'sum' => abs($old), 
                            'old' => $old, 
                            'new' => 0, 
                            'shop_id' => $shop_id
                        ]);
                    }
                }
            }
            else
            {
                if ($bank_type=='bonus')
                {
                    if ($batch=='1')
                    {
                        $bonus_bank = \App\Models\BonusBank::where('master_id', $master)->get();
                    }
                    else
                    {
                        $bonus_bank = \App\Models\BonusBank::where('id', $bank_id)->get();
                    }

                    foreach ($bonus_bank as $bb){
                        $master = \App\Models\User::where('id', $bb->master_id)->first();
                        if ($master)
                        {
                            $name = $master->username;
                            $shop_id = 0;
                            $old = $bb->bank;
                            if ($act == 'add')
                            {
                                $bb->increment('bank', abs($amount));
                            }
                            else
                            {
                                $bb->decrement('bank', abs($amount));
                            }
                            $new = $bb->bank;
                            $type = 'bonus';
                            $game = ' General';
                            if ($bb->game_id!=0)
                            {
                                $game = $bb->game->title;
                            }
                            \App\Models\BankStat::create([
                                'name' => ucfirst($type) . "[$name]" . "-$game", 
                                'user_id' => \Illuminate\Support\Facades\Auth::id(), 
                                'type' => $act, 
                                'sum' => abs($amount), 
                                'old' => $old, 
                                'new' => $new, 
                                'shop_id' => $shop_id
                            ]);
                        }
                        
                    }
                }
                elseif ($bank_type=='bonusmax')
                {
                    if ($request->id==0)
                    {
                        $bonus_bank = \App\Models\BonusBank::where('master_id', $request->master)->get();
                    }
                    else
                    {
                        $bonus_bank = \App\Models\BonusBank::where('id', $request->id)->get();
                    }

                    foreach ($bonus_bank as $bb){
                        if ($act == 'add')
                        {
                            $bb->increment('max_bank', abs($request->summ));
                        }
                        else
                        {
                            $bb->decrement('max_bank', abs($request->summ));
                        }                        
                    }
                }
                else
                {
                    if ($batch=='1')
                    {
                        $shops = auth()->user()->availableShops();
                        $gamebank = \App\Models\GameBank::whereIn('shop_id', $shops)->get();
                    }
                    else
                    {
                        $gamebank = \App\Models\GameBank::where('id', $bank_id)->get();
                    }
                    foreach ($gamebank as $gb){
                        $shop = $gb->shop;
                        $name = $shop->name;
                        $old = $gb->slots;
                        if ($act == 'add')
                        {
                            $gb->increment('slots', abs($amount));
                        }
                        else
                        {
                            $gb->decrement('slots', abs($amount));
                        }
                        $new = $gb->slots;
                        $shop_id = $gb->shop_id;
                        $type = 'slots';
                        \App\Models\BankStat::create([
                            'name' => ucfirst($type) . "[$name]", 
                            'user_id' => \Illuminate\Support\Facades\Auth::id(), 
                            'type' => $act, 
                            'sum' => abs($amount), 
                            'old' => $old, 
                            'new' => $new, 
                            'shop_id' => $shop_id
                        ]);
                    }
                }
            }

            if ($bank_type == 'bonus')
            {
                return redirect()->to(argon_route('argon.game.bonusbank',['id'=>$request->master]));
            }
            else
            {
                return redirect()->to(argon_route('argon.game.bank'));
            }

        }
        public function game_bankbalance(\Illuminate\Http\Request $request)
        {
            $bank_type = $request->type;
            $batch = $request->batch;
            $bank_id = $request->id;
            $master = $request->master;
            $bankinfo = [
                'type' => $bank_type,
                'batch' => $batch,
                'id' => $bank_id,
                'name' => '일괄수정',
                'balance' => 0,
                'master' => $master,
            ];
            if ($batch == '1')
            {

            }
            else
            {
                if ($bank_type == 'bonus')
                {
                    $bank = \App\Models\BonusBank::where('id', $bank_id)->first();
                    if (!$bank)
                    {
                        return redirect()->back()->withSuccess('에이전트를 찾을수 없습니다');
                    }
                    if ($bank->game_id == 0)
                    {
                        $bankinfo['name'] = $bank->master->username . ' - 일반';
                    }
                    else
                    {
                        $bankinfo['name'] = $bank->master->username . ' - ' . $bank->game->title;
                    }
                    $bankinfo['balance'] = $bank->bank;
                    $bankinfo['master'] = $bank->master_id;
                }
                else
                {
                    $bank = \App\Models\GameBank::where('id', $bank_id)->first();
                    if (!$bank)
                    {
                        return redirect()->back()->withSuccess('에이전트를 찾을수 없습니다');
                    }
                    $bankinfo['name'] = $bank->shop->name;
                    $bankinfo['balance'] = $bank->slots;
                }
                
            }

            return view('backend.argon.game.bankbalance', compact('bankinfo'));
        }

        public function game_bonusbank(\Illuminate\Http\Request $request)
        {
            $master_id = $request->id;
            $master = \App\Models\User::where('id', $master_id)->first();
            $bonusbank = \App\Models\BonusBank::where('master_id', $master_id)->get();
            
            return view('backend.argon.game.bonusbank', compact('master','bonusbank'));
        }

        public function game_bank(\Illuminate\Http\Request $request)
        {
            // $shops = auth()->user()->availableShops();
            // $gamebank = \App\Models\GameBank::whereIn('shop_id', $shops);
            // $masters = \App\Models\User::where('role_id', 6)->pluck('id')->toArray();
            // $bonusbank = \App\Models\BonusBank::whereIn('master_id', $masters)
            //                 ->selectRaw('id, master_id, SUM(bank) as totalBank,count(master_id) as games')
            //                 ->groupby('master_id')->get();

            // $gamebank = $gamebank->paginate(20);

            $minslot1 = \App\Models\Settings::where('key', 'minslot1')->first();
            $maxslot1 = \App\Models\Settings::where('key', 'maxslot1')->first();
            $minslot2 = \App\Models\Settings::where('key', 'minslot2')->first();
            $maxslot2 = \App\Models\Settings::where('key', 'maxslot2')->first();
            $minslot3 = \App\Models\Settings::where('key', 'minslot3')->first();
            $maxslot3 = \App\Models\Settings::where('key', 'maxslot3')->first();
            $minslot4 = \App\Models\Settings::where('key', 'minslot4')->first();
            $maxslot4 = \App\Models\Settings::where('key', 'maxslot4')->first();
            $minslot5 = \App\Models\Settings::where('key', 'minslot5')->first();
            $maxslot5 = \App\Models\Settings::where('key', 'maxslot5')->first();

            $minbonus1 = \App\Models\Settings::where('key', 'minbonus1')->first();
            $maxbonus1 = \App\Models\Settings::where('key', 'maxbonus1')->first();
            $minbonus2 = \App\Models\Settings::where('key', 'minbonus2')->first();
            $maxbonus2 = \App\Models\Settings::where('key', 'maxbonus2')->first();
            $minbonus3 = \App\Models\Settings::where('key', 'minbonus3')->first();
            $maxbonus3 = \App\Models\Settings::where('key', 'maxbonus3')->first();
            $minbonus4 = \App\Models\Settings::where('key', 'minbonus4')->first();
            $maxbonus4 = \App\Models\Settings::where('key', 'maxbonus4')->first();
            $minbonus5 = \App\Models\Settings::where('key', 'minbonus5')->first();
            $maxbonus5 = \App\Models\Settings::where('key', 'maxbonus5')->first();

            $reset_bank = \App\Models\Settings::where('key', 'reset_bank')->first();
            return view('game.bankstat', compact('minslot1','maxslot1','minslot2','maxslot2','minslot3','maxslot3','minslot4','maxslot4','minslot5','maxslot5','minbonus1','maxbonus1','minbonus2','maxbonus2','minbonus3','maxbonus3','minbonus4','maxbonus4','minbonus5','maxbonus5','reset_bank'));//'gamebank','bonusbank', 
        }
        /* ----- GAC Bet Limit ------ */
        /*
        public function game_betlimit(\Illuminate\Http\Request $request)
        {
            $betConfig = \App\Models\ProviderInfo::where('user_id', 0)->where('provider', 'gac')->first();
            if (!$betConfig)
            {
                return redirect()->back()->withErrors(['기본 배팅한도 설정을 찾을수 없습니다.']);
            }

            $betLimits = json_decode($betConfig->config, true);
            $betLimits1 = null;

            if (!auth()->user()->hasRole('admin'))
            {
                $betConfig1 = \App\Models\ProviderInfo::where('user_id', auth()->user()->id)->where('provider', 'gac')->first();
                if ($betConfig1)
                {
                    $betLimits1 = json_decode($betConfig1->config, true);
                    if (!isset($betLimits1[0]['BetLimit']))
                    {
                        $betLimits1 = [
                            [
                                'tableIds' => ['default'],
                                'BetLimit' => $betLimits1
                            ]
                        ];
                    }
                }
            }
            
            foreach ($betLimits as $limit)
            {
                if ($betLimits1)
                {
                    foreach ($betLimits1 as $limit1)
                    {
                        if ($limit['tableIds'] == $limit1['tableIds'])
                        {
                            $limit = $limit1;
                        }
                    }
                }

                foreach ($limit['tableIds'] as $tblId)
                {
                    if ($tblId == 'default')
                    {
                        $limit['tableName'][] = '기본설정';
                    }
                    else
                    {
                        $gameObj = \App\Http\Controllers\GameProviders\GACController::getGameObj($tblId);
                        if (!$gameObj)
                        {
                            $limit['tableName'][] = $tblId;
                        }
                        else
                        {
                            $limit['tableName'][] = $gameObj['title'];
                        }
                    }
                    
                }
                $betUpdateTableName[] = $limit;
            }
            $betLimits = $betUpdateTableName;

            
            return view('game.gacbetlimit', compact('betLimits'));
        } 
        public function game_betlimitupdate(\Illuminate\Http\Request $request)
        {
            $data = $request->except(['tableid', '_token']);
            $tableIds = explode(',', $request->tableid);
            if (auth()->user()->hasRole('admin'))
            {
                $betConfig = \App\Models\ProviderInfo::where('user_id', 0)->where('provider', 'gac')->first();
                if (!$betConfig)
                {
                    abort(500);
                }
                else
                {
                    $betLimits = json_decode($betConfig->config, true);
                    $updateLimits = [];
                    foreach ($betLimits as $limit)
                    {
                        if ($limit['tableIds'] == $tableIds)
                        {
                            $limit['BetLimit'] = $data;
                        }
                        $updateLimits[] = $limit;
                    }

                    $betConfig->update(['config' => json_encode($updateLimits)]);
                }
            }
            else
            {
                $betConfig = \App\Models\ProviderInfo::where('user_id', auth()->user()->id)->where('provider', 'gac')->first();
                if (!$betConfig)
                {
                    $updateLimits[] = [
                        'tableIds' => $tableIds,
                        'BetLimit' => $data
                    ];
                    \App\Models\ProviderInfo::create([
                        'user_id' => auth()->user()->id,
                        'provider' => 'gac',
                        'config' => json_encode($updateLimits)
                    ]);
                }
                else
                {
                    $betLimits = json_decode($betConfig->config, true);
                    if (!isset($betLimits[0]['BetLimit']))
                    {
                        $betLimits = [
                            [
                                'tableIds' => ['default'],
                                'BetLimit' => $betLimits
                            ]
                        ];
                    }

                    $updateLimits = [];
                    $bfound = false;
                    foreach ($betLimits as $limit)
                    {
                        if ($limit['tableIds'] == $tableIds)
                        {
                            $limit['BetLimit'] = $data;
                            $bfound = true;
                        }
                        $updateLimits[] = $limit;
                    }

                    if (!$bfound)
                    {
                        $updateLimits[] = [
                            'tableIds' => $tableIds,
                            'BetLimit' => $data
                        ];
                    }

                    $betConfig->update(['config' => json_encode($updateLimits)]);
                }
            }
            
            return redirect()->back()->withSuccess(['배팅한도가 업데이트되었습니다']);
        }
        */
        /* ----- *** ------ */

        /* ----- Nexus && RG Bet Limit ------ */
        public function game_betlimit(\Illuminate\Http\Request $request)
        {
            $currentSkin = 'A';
            $betConfig1 = \App\Models\ProviderInfo::where('user_id', auth()->user()->id)->where('provider', 'evo')->first();
            if ($betConfig1)
            {
                $currentSkin = $betConfig1->config;
            }
            $betSkins = \App\Models\EvoSkins::get();
            return view('game.evobetlimit', compact('betSkins', 'currentSkin'));
        }
        public function game_betlimitupdate(\Illuminate\Http\Request $request)
        {
            $selectedSkin = $request->input('skin');
            $betConfig = \App\Models\ProviderInfo::where('user_id', auth()->user()->id)->where('provider', 'evo')->first();
            if (!$betConfig)
            {
                \App\Models\ProviderInfo::create([
                    'user_id' => auth()->user()->id,
                    'provider' => 'evo',
                    'config' => $selectedSkin
                ]);
            }
            else
            {
                $betConfig->update(['config' => $selectedSkin]);
            }
            return redirect()->back()->withSuccess(['배팅한도가 업데이트되었습니다']);
        }
        /* ----- *** ------ */

        

        public function game_gactable(\Illuminate\Http\Request $request)
        {
            $gacstatTable = \App\Http\Controllers\GameProviders\GACController::getgamelist('gvo');
            $providerinfo = \App\Models\ProviderInfo::where('provider','gacclose')->where('user_id', auth()->user()->id)->first();
            $gacclosed = [];
            if ($providerinfo)
            {
                $gacclosed = json_decode($providerinfo->config, true);
            }
            $gactables = [];
            foreach ($gacstatTable as $table )
            {
                if (in_array($table['gamecode'], $gacclosed))
                {
                    $table['view'] = 0;
                }
                else
                {
                    $table['view'] = 1;
                }
                $gactables[] = $table;
            }
            return view('game.gactable', compact('gactables'));
        }

        public function game_gactableupdate(\Illuminate\Http\Request $request)
        {
            $table = $request->table;
            $view = $request->view;

            $providerinfo = \App\Models\ProviderInfo::where('provider','gacclose')->where('user_id', auth()->user()->id)->first();
            $gacclosed = [];
            if ($providerinfo)
            {
                $gacclosed = json_decode($providerinfo->config, true);
            }
            if ($view == 1)
            {
                $gacclosed = array_diff($gacclosed, array($table));
            }
            else
            {
                $gacclosed[] = $table;
            }
            if ($providerinfo)
            {
                if (count($gacclosed) > 0)
                {
                    $providerinfo->update(['config' => json_encode($gacclosed)]);
                }
                else
                {
                    $providerinfo->delete();
                }
            }
            else
            {
                \App\Models\ProviderInfo::create([
                    'user_id' => auth()->user()->id,
                    'provider' => 'gacclose',
                    'config' => json_encode($gacclosed)
                ]);
            }
            
            return redirect()->back()->withSuccess(['테이블상태를 업데이트 했습니다']);
        }

        public function game_switching(\Illuminate\Http\Request $request)
        {
            $website = \App\Models\WebSite::where(['adminid' => auth()->user()->id])->first();

            if (!$website) {
                return redirect()->back()->withErrors(['내부 오류']);
            }

            $category = \App\Models\Category::where(['site_id' => $website->id, 'shop_id' => 0, 'code' => 34, 'href' => 'nexus-evo'])->first();
            $isProduction = $category && $category->view == 1;

            return view('game.switching', ['isProduction' => $isProduction]);
        }

        public function game_switchingupdate(\Illuminate\Http\Request $request)
        {
            $version = $request->input('version'); // 'sales' 또는 'test'
            $website = \App\Models\WebSite::where(['adminid' => auth()->user()->id])->first();

            if (!$website) {
                return redirect()->back()->withErrors(['내부 오류']);
            }

            if ($version === 'sales') {
                // 이미 가품을 이용하던 업체들에 대해서만 정품으로 스위칭한다.
                $shopIds = \App\Models\Category::where([
                    'site_id' => $website->id,
                    'code' => 34,
                    'href' => 'rg-evol',
                    'view' => 1
                ])->pluck('shop_id');

                // where in 구문에서 갯수가 많은 경우 오류 나올수 잇음
                \App\Models\Category::where('site_id', $website->id)
                    ->where('code', 34)
                    ->where('href', 'nexus-evo')
                    ->whereIn('shop_id', $shopIds)
                    ->update(['view' => 1]);

                    \App\Models\Category::where(['site_id' => $website->id, 'code' => 34])
                    ->where('href', '!=', 'nexus-evo')
                    ->update(['view' => 0]);
            } else {
                // 이미 정품을 이용하던 업체들에 대해서만 가품으로 스위칭한다.
                $shopIds = \App\Models\Category::where([
                    'site_id' => $website->id,
                    'code' => 34,
                    'href' => 'nexus-evo',
                    'view' => 1
                ])->pluck('shop_id');

                \App\Models\Category::where('site_id', $website->id)
                    ->where('code', 34)
                    ->where('href', 'rg-evol')
                    ->whereIn('shop_id', $shopIds)
                    ->update(['view' => 1]);

                \App\Models\Category::where(['site_id' => $website->id, 'code' => 34])
                    ->where('href', '!=', 'rg-evol')
                    ->update(['view' => 0]);
            }

            return redirect()->back()->withSuccess(['에볼루션스위칭이 성공적으로 수행되었습니다.']);
        }

        public function game_gackind(\Illuminate\Http\Request $request)
        {
            return view('modal.gactable');
        }
        public function game_gackindupdate(\Illuminate\Http\Request $request)
        {
            $type = $request->type;
            $view = $request->view;
            $gacstatTable = \App\Http\Controllers\GameProviders\GACController::getgamelist('gvo');
            $providerinfo = \App\Models\ProviderInfo::where('provider','gacclose')->where('user_id', auth()->user()->id)->first();
            $gacclosed = [];
            if ($providerinfo)
            {
                $gacclosed = json_decode($providerinfo->config, true);
            }
            $tables = [];
            $types = ['baccarat', 'blackjack', 'roulette'];
            foreach ($gacstatTable as $table )
            {
                if($type == 'other')
                {
                    if($table['type'] != 'lobby' && !in_array($table['type'], $types))
                    {
                        $tables[] = $table['gamecode'];
                    }
                }
                else
                {
                    if($table['type'] == $type)
                    {
                        $tables[] = $table['gamecode'];
                    }
                }
            }
            if ($view == 1)
            {
                $gacclosed = array_diff($gacclosed, $tables);
            }
            else
            {
                $gacclosed = array_merge($gacclosed, $tables) ;
            }
            if ($providerinfo)
            {
                if (count($gacclosed) > 0)
                {
                    $providerinfo->update(['config' => json_encode($gacclosed)]);
                }
                else
                {
                    $providerinfo->delete();
                }
            }
            else
            {
                \App\Models\ProviderInfo::create([
                    'user_id' => auth()->user()->id,
                    'provider' => 'gacclose',
                    'config' => json_encode($gacclosed)
                ]);
            }
            
            return redirect()->back()->withSuccess(['테이블상태를 업데이트 했습니다']);
        }
        public function game_missrole(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            $info = $user->info;
            $data = [
                'slot_total_deal' => 0,
                'slot_total_miss' => 0,
                'table_total_deal' => 0,
                'table_total_miss' => 0,

            ];
            foreach ($info as $inf)
            {
                if ($inf->roles =='slot')
                {
                    $data['slot_total_deal'] = $inf->title;
                    $data['slot_total_miss'] = $inf->link;
                }
                if ($inf->roles =='table')
                {
                    $data['table_total_deal'] = $inf->title;
                    $data['table_total_miss'] = $inf->link;
                }
            }
            $shops = [];
            $shopIds = $user->availableShops();
            if (count($shopIds)>0)
            {
                $shops = \App\Models\Shop::whereIn('id', $shopIds);
                if ($request->search != '')
                {
                    $shops = $shops->where('name', 'like', '%'.$request->search.'%');
                }
                $shops = $shops->paginate(10);

            }

            return view('game.missbet', compact('data', 'shops'));
        }
        public function game_missrolestatus(\Illuminate\Http\Request $request)
        {
            $status = $request->status;
            $type = $request->type;
            $shopIds = [];
            if($type == 'all'){
                $shopIds = auth()->user()->shops_array(true);
                $shops = \App\Models\Shop::whereIn('id', $shopIds)->update(
                    [
                        'slot_miss_deal' => $status,
                        'slot_garant_deal' => 0,
                        'table_miss_deal' => $status,
                        'table_garant_deal' => 0,
                    ]
                );
            }else{
                $shopIds = $request->ids;
            
                $availableShops = auth()->user()->shops_array(true);
                foreach($shopIds as $id){
                    if (!in_array($id, $availableShops))
                    {
                        return response()->json(['error'=>true, 'msg'=> '번호가' . $id . '인 매장을 찾을수 없습니다']);
                    }
                }

                $shops = \App\Models\Shop::whereIn('id', $shopIds)->update(
                    [
                        $type . '_miss_deal' => $status,
                        $type . '_garant_deal' => 0,
                    ]
                );
            }
            
            
            // $shops = \App\Models\Shop::whereIn('id', $shopIds)->update(
            //     [
            //         $type . '_miss_deal' => $status,
            //         $type . '_garant_deal' => 0,
            //     ]
            // );
            return response()->json(['error'=>false, 'msg'=> '선택된 매장들의 공배팅상태를 업데이트했습니다']);
        }

        public function game_missroleupdate(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            $info = $user->info;
            $data = $request->all();
            if ($data['slot_total_deal']== 0 || $data['slot_total_miss']==0)
            {
                return redirect()->back()->withErrors(['공배팅값은 0이 될수 없습니다']);
            }
            $slotinf = null;
            $tableinf = null;

            foreach ($info as $inf)
            {
                if ($inf->roles =='slot')
                {
                    $inf->title = $data['slot_total_deal'];
                    $inf->link = $data['slot_total_miss'];
                    $slotinf = $inf;
                    $inf->save();
                }
                if ($inf->roles =='table')
                {
                    $inf->title = $data['table_total_deal'];
                    $inf->link = $data['table_total_miss'];
                    $tableinf = $inf;
                    $inf->save();
                }
            }
            if ($slotinf==null)//create
            {
                \App\Models\Info::create(
                    [
                        'link' => $data['slot_total_miss'],
                        'title' => $data['slot_total_deal'],
                        'roles' => 'slot',
                        'user_id' => $user->id
                    ]
                    );
            }
            if ($tableinf==null)//create
            {
                \App\Models\Info::create(
                    [
                        'link' => $data['table_total_miss'],
                        'title' => $data['slot_total_deal'],
                        'roles' => 'table',
                        'user_id' => $user->id
                    ]
                    );
            }

            //create all info about child shops
            $shopIds = $user->availableShops();
            if (count($shopIds)>0)
            {
                $shops = \App\Models\Shop::whereIn('id', $shopIds)->get();
                foreach ($shops as $shop)
                {
                    $infoShop = \App\Models\InfoShop::where('shop_id', $shop->id)->get();
                    $slotinf = null;
                    $tableinf = null;
                    foreach ($infoShop as $info)
                    {
                        $inf = $info->info;
                        if ($inf && $inf->roles =='slot')
                        {
                            $inf->title = $data['slot_total_deal'];
                            $inf->link = $data['slot_total_miss'];
                            $inf->text = implode(',', rand_region_numbers($data['slot_total_deal'],$data['slot_total_miss']));
                            $slotinf = $inf;
                            $inf->save();
                        }
                        if ($inf && $inf->roles =='table')
                        {
                            $inf->title = $data['table_total_deal'];
                            $inf->link = $data['table_total_miss'];
                            $inf->text = implode(',', rand_region_numbers($data['table_total_deal'],$data['table_total_miss']));
                            $tableinf = $inf;
                            $inf->save();
                        }
                    }
                    if ($slotinf==null)//create
                    {
                        $slotinf = \App\Models\Info::create(
                            [
                                'link' => $data['slot_total_miss'],
                                'title' => $data['slot_total_deal'],
                                'roles' => 'slot',
                                'text' => implode(',', rand_region_numbers($data['slot_total_deal'],$data['slot_total_miss'])),
                                'user_id' => $user->id
                            ]
                            );
                        \App\Models\InfoShop::create(
                            [
                                'shop_id' => $shop->id,
                                'info_id' => $slotinf->id
                            ]
                            );
                    }
                    if ($tableinf==null)//create
                    {
                        $tableinf = \App\Models\Info::create(
                            [
                                'link' => $data['table_total_miss'],
                                'title' => $data['table_total_deal'],
                                'roles' => 'table',
                                'text' => implode(',', rand_region_numbers($data['table_total_deal'],$data['table_total_miss'])),
                                'user_id' => $user->id
                            ]
                            );
                        \App\Models\InfoShop::create(
                            [
                                'shop_id' => $shop->id,
                                'info_id' => $tableinf->id
                            ]
                            );
                    }
                    $init_table_miss = ($data['table_total_miss']==0||$data['table_total_deal']==0)?0:1;
                    $init_slot_miss = ($data['slot_total_miss']==0||$data['slot_total_deal']==0)?0:1;

                    $shop->update([
                        'slot_miss_deal' => $init_slot_miss,
                        'slot_garant_deal' => 0,
                        'table_miss_deal' => $init_table_miss,
                        'table_garant_deal' => 0,
                    ]);

                }
            }
            return redirect()->back()->withSuccess(['공배팅설정이 업데이트되었습니다']);
        }

        public function game_call(\Illuminate\Http\Request $request)
        {
            $availableUsers = auth()->user()->availableUsers();
            $availableUsers[] = auth()->user()->id;            
            if ($request->search != '' && $request->kind == 1)
            {
                $partner = \App\Models\User::where('username', $request->search)->whereIn('id', $availableUsers)->first();
                if($partner != null){
                    $availableUsers = $partner->availableUsers();
                    $availableUsers[] = $partner->id;            
                }else{
                    return redirect()->back()->withErrors('작성자를 찾을수 없습니다');
                }
            }
            $start_date = date("Y-m-d 00:00:00");
            $end_date = date("Y-m-d H:i:s");
            if ($request->join != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->join[0]);
                $end_date = preg_replace('/T/',' ', $request->join[1]);            
            }
            $happyhours = \App\Models\HappyHourUser::select('happyhour_users.*')->whereIn('admin_id', $availableUsers);
            $happyhours = $happyhours->where('happyhour_users.created_at', '>=', $start_date);
            $happyhours = $happyhours->where('happyhour_users.created_at', '<=', $end_date );
            $happyhours = $happyhours->join('users', 'users.id', '=', 'happyhour_users.user_id');

            $searchName = 'username';
            $searchValue = '';
            if($request->search != ''){
                $searchValue = $request->search;
            }
            if($request->kind != ''){
                if($request->kind == 2){
                    $searchName = 'total_bank';
                }
            }

            if ($searchValue != '' && $request->kind == '')
            {
                $happyhours = $happyhours->where('users.username', 'like', '%' . $searchValue . '%');
            }
            if ($request->status != '')
            {
                $happyhours = $happyhours->where('happyhour_users.status', $request->status);
            }
            $order = 'asc';
            if($request->order == ''){
                $order = 'desc';
            }
            if($request->kind == 2){
                $happyhours = $happyhours->orderBy('happyhour_users.total_bank', $order);
            }
            $total = [
                'totalbank' => (clone $happyhours)->sum('total_bank'),
                'currentbank' => (clone $happyhours)->sum('current_bank'),
                'overbank' => (clone $happyhours)->sum('over_bank'),
            ];
            $happyhours = $happyhours->paginate(10);
            return view('game.call', compact('happyhours', 'total'));
        }
        public function call_create()
        {
            return view('modal.call');
        }
        public function call_store(\Illuminate\Http\Request $request)
        {
            $username = $request->username;
            if (!$username)
            {
                return response()->json(['error'=>true, 'msg'=> '회원아이디를 입력하세요.']);
            }
            $availableUsers = auth()->user()->availableUsers();
            $users = \App\Models\User::where('username', $username)->get();
            $user = null;
            foreach($users as $item)
            {
                if ($item && in_array($item->id, $availableUsers))
                {
                    $user = $item;
                    break;
                }
            }
            if($user == null){                
                return response()->json(['error'=>true, 'msg'=> '[' . $username . '] 유저가 없습니다.']);
            }
            $uniq = \App\Models\HappyHourUser::where([
                // 'time' => $request->time, 
                'user_id' => $user->id,
                'status' => 1
            ])->count();
            if( $uniq ) 
            {
                return response()->json(['error'=>true, 'msg'=> '[' . $username . '] 유저 콜이 존재합니다.']);
            }
            $data = $request->only([
                'total_bank', 
                'jackpot',
                'time', 
                'status'
            ]);
            $bank = abs($data['total_bank']);
            $data['current_bank'] = $bank;
            $data['user_id'] = $user->id;
            $data['over_bank'] = 0;
            if (isset($data['jackpot']) && $data['jackpot'] > 0)
            {
                $data['progressive'] = mt_rand(2,5);
            }
            if($data['total_bank'] > 20000000){
                return response()->json(['error'=>true, 'msg'=> '콜금을 2천만으로 제한시켜주세요.']);
            }
            $data['admin_id'] = auth()->user()->id;
            if (!auth()->user()->hasRole('admin'))
            {
                if (auth()->user()->balance < $data['total_bank'])
                {
                    return response()->json(['error'=>true, 'msg'=> '보유금이 충분하지 않습니다.']);
                }

                $admin = \App\Models\User::where('role_id', 9)->first();
                if (!$admin)
                {
                    return response()->json(['error'=>true, 'msg'=> '내부오류']);
                }

                auth()->user()->addBalance('out', $bank, $admin, 0, null, '콜 생성');

            }
            $happyhour = \App\Models\HappyHourUser::create($data);
            event(new \App\Events\Jackpot\NewJackpot($happyhour));
            return response()->json(['error'=>false, 'msg'=> '[' . $username . '] 유저 콜이 생성되었습니다.']);
        }
        public function call_delete(\Illuminate\Http\Request $request)
        {
            /*if( !in_array($happyhour->shop_id, auth()->user()->availableShops()) ) 
            {
                return redirect()->back()->withErrors([trans('app.wrong_shop')]);
            } */
            $happyhour = $request->id;
            $jp = \App\Models\HappyHourUser::where('id', $happyhour)->first();
            $availableUsers = auth()->user()->hierarchyUsers();
            $availableUsers[] = auth()->user()->id;
            if (!in_array($jp->user_id, $availableUsers) || !in_array($jp->admin_id, $availableUsers))
            {
                return redirect()->back()->withErrors(trans('No Permission'));
            }

            
            if ($jp->status == 2)
            {
                event(new \App\Events\Jackpot\DeleteJackpot($jp));
                $jp->delete();
                return redirect()->back()->withSuccess('유저콜이 삭제되었습니다');
            }
            else
            {

                $admin = \App\Models\User::where('role_id', 9)->first();
                if (!$admin)
                {
                    return redirect()->back()->withErrors('내부오류');
                }

                $remainbank = $jp->current_bank - $jp->over_bank;

                if ($remainbank > 0)
                {
                    $jp->admin->addBalance('add', $remainbank, $admin, 0, null, '콜 완료');
                }
                else
                {
                    $jp->admin->addBalance('out', abs($remainbank), $admin, 0, null, '콜 완료');
                }
                $jp->update(['status' => 2, 'updated_at' => \Carbon\Carbon::now()]);
                return redirect()->back()->withSuccess('유저콜이 종료되었습니다');
            }
            
        }
    }
    

}
