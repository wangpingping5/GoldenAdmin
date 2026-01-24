<?php
namespace App\Http\Controllers\Backend
{
    class LogController extends \App\Http\Controllers\Controller
    {
        //파트너
        public function patner_transaction(\Illuminate\Http\Request $request)
        {
            $statistics = \App\Models\Transaction::select('transactions.*')->orderBy('transactions.created_at', 'DESC');
            $user = auth()->user();
            $availablePartners = $user->availableUsers();
            $availablePartners[] = $user->id;
            $statistics = $statistics->whereIn('user_id', $availablePartners);

            $start_date = date("Y-m-1 0:0:0");
            $end_date = date("Y-m-d 23:59:59");

            if ($request->join != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->join[0]);
                $end_date = preg_replace('/T/',' ', $request->join[1]);            
            }
            $statistics = $statistics->where('transactions.created_at', '>=', $start_date);
            $statistics = $statistics->where('transactions.created_at', '<=', $end_date );

            $statistics = $statistics->join('users', 'users.id', '=', 'transactions.user_id');
            $searchName = '';
            $searchValue = '';

            if($request->search != ''){
                $searchValue = $request->search;
                if($searchName == ''){
                    $searchName = 'user';
                }
            }
            if($request->userID != ''){
                $searchName = $request->userID;
            }
            if($searchValue != ''){
                    
                if ($searchName == 'user')
                {
                    $statistics = $statistics->where('users.username', 'like', '%' . $searchValue . '%');
                }

                if ($searchName == 'admin')
                {
                    $payeerIds = \App\Models\User::where('username', 'like', '%' . $searchValue . '%')->pluck('id')->toArray();
                    $statistics = $statistics->whereIn('transactions.payeer_id', $payeerIds);
                }

                if($searchName == 'id'){
                    $statistics = $statistics->where('transactions.id', 'like', '%' . $searchValue . '%');
                }
                
            }
            if ($request->type != '')
            {
                $statistics = $statistics->where('transactions.type',  $request->type);
            }

            if ($request->mode != '')
            {
                if ($request->mode == 'manual')
                {
                    $statistics = $statistics->whereNull('transactions.request_id');
                }
                else
                {
                    $statistics = $statistics->whereNotNull('transactions.request_id');
                }
            }

            $total = [
                'add' => (clone $statistics)->where(['type'=>'add'])->sum('summ'),
                'out' => (clone $statistics)->where(['type'=>'out'])->sum('summ'),
                'deal_out' => (clone $statistics)->where(['type'=>'deal_out'])->sum('summ'),
                'ggr_out' => (clone $statistics)->where(['type'=>'ggr_out'])->sum('summ'),
            ];

            $statistics = $statistics->paginate(20);
            return view('log.list', compact('statistics', 'total'));
        }

        //파트너 롤링내역
        public function patner_deal_stat(\Illuminate\Http\Request $request)
        {
            $statistics = \App\Models\DealLog::select('deal_log.*')->orderBy('deal_log.date_time', 'DESC');
            $user = auth()->user();            
            $availablePartners = $user->availableUsers();
            

            $start_date = date("Y-m-d H:i:s", strtotime("-1 hours"));
            $end_date = date("Y-m-d H:i:s");

            if ($request->join != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->join[0]);
                $end_date = preg_replace('/T/',' ', $request->join[1]);            
            }
            if (strtotime($end_date) - strtotime($start_date) >= 90000)
            {
                return redirect()->back()->withErrors(['검색시간을 24시간 이내로 설정해주세요.']);
            }
            $statistics = $statistics->where('deal_log.date_time', '>=', $start_date);
            $statistics = $statistics->where('deal_log.date_time', '<=', $end_date );

            $statistics = $statistics->join('users', 'users.id', '=', 'deal_log.partner_id');
            $searchValue = '';
            $searchName = 'user';
            if($request->search != ''){
                $searchValue = $request->search;
            }
            $searchName = $request->userID;
            if($searchName != '' && $searchValue != ''){
                if($searchName == 'user'){
                    $statistics = $statistics->whereIn('deal_log.partner_id', $availablePartners);
                    $statistics = $statistics->where('users.username', 'like', '%' . $searchValue . '%');
                }else if($searchName == 'player'){
                    $playerIds = \App\Models\User::where('username', 'like', '%' . $searchValue . '%')->pluck('id')->toArray();
                    $statistics = $statistics->whereIn('deal_log.user_id', $playerIds);
                }else{
                    $statistics = $statistics->where('deal_log.game', 'like', '%'. $searchValue . '%');  //게임
                }
            }        

            // if($request->userID==''){
            //     if($request->search == ''){
            //         $statistics = $statistics->whereIn('deal_log.partner_id', $availablePartners);
            //         $statistics = $statistics->get();
            //     }else{
            //         $statistics = $statistics->whereIn('deal_log.partner_id', $availablePartners);
            //         $statistics = $statistics->where('users.username', 'like', '%' . $request->search . '%');
            //     }
            // }else{
            //     if ($request->userID=='user' && $request->search != '')
            //     {
            //         $statistics = $statistics->whereIn('deal_log.partner_id', $availablePartners);
            //         $statistics = $statistics->where('users.username', 'like', '%' . $request->search . '%');
            //     }
            //     else
            //     {
            //         $statistics = $statistics->where('deal_log.partner_id', $user->id);
            //     }
    
            //     if ($request->userID=='player' && $request->search != '')
            //     {
            //         $playerIds = \App\Models\User::where('username', 'like', '%' . $request->search . '%')->pluck('id')->toArray();
            //         $statistics = $statistics->whereIn('deal_log.user_id', $playerIds);
            //     }
    
            //     if ($request->userID=='game' && $request->search != '')
            //     {
            //         $statistics = $statistics->where('deal_log.game', 'like', '%'. $request->search . '%');
            //     }
            // }

            

            $total = [
                'bet' => (clone $statistics)->sum('bet'),
                'win' => (clone $statistics)->sum('win'),
                'deal' => (clone $statistics)->sum('deal_log.deal_profit') - (clone $statistics)->sum('deal_log.mileage'),
                'ggr' => (clone $statistics)->sum('deal_log.ggr_profit') - (clone $statistics)->sum('deal_log.ggr_mileage'),
            ];

            $statistics = $statistics->paginate(20);
            //check if evolution+gameartcasino
            $master = $user;
            while ($master !=null && !$master->isInoutPartner())
            {
                $master = $master->referral;
            }
            $gacmerge = \App\Http\Controllers\GameProviders\GACController::mergeGAC_EVO($master->id);
            return view('log.partnerdeal.list', compact('statistics', 'total','gacmerge'));
        }

        //유저
        public function player_transaction(\Illuminate\Http\Request $request)
        {
            $statistics = \App\Models\Transaction::select('transactions.*')->orderBy('transactions.created_at', 'DESC');
            $user = auth()->user();
            $availableUsers = $user->hierarchyUsersOnly();
            $statistics = $statistics->whereIn('user_id', $availableUsers);

            $start_date = date("Y-m-1 0:0:0");
            $end_date = date("Y-m-d 23:59:59");

            if ($request->join != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->join[0]);
                $end_date = preg_replace('/T/',' ', $request->join[1]);            
            }
            $statistics = $statistics->where('transactions.created_at', '>=', $start_date);
            $statistics = $statistics->where('transactions.created_at', '<=', $end_date );

            $statistics = $statistics->join('users', 'users.id', '=', 'transactions.user_id');
            $searchName = '';
            $searchValue = '';

            if($request->search != ''){
                $searchValue = $request->search;
                if($searchName == ''){
                    $searchName = 'user';
                }
            }
            if($request->userID != ''){
                $searchName = $request->userID;
            }
            if($searchValue != ''){
                    
                if ($searchName == 'user')
                {
                    $statistics = $statistics->where('users.username', 'like', '%' . $searchValue . '%');
                }

                if ($searchName == 'admin')
                {
                    $payeerIds = \App\Models\User::where('username', 'like', '%' . $searchValue . '%')->pluck('id')->toArray();
                    $statistics = $statistics->whereIn('transactions.payeer_id', $payeerIds);
                }

                if($searchName == 'id'){
                    $statistics = $statistics->where('transactions.id', 'like', '%' . $searchValue . '%');
                }
                
            }

            if ($request->type != '')
            {
                $statistics = $statistics->where('transactions.type',  $request->type);
            }

            $total = [
                'add' => (clone $statistics)->where(['type'=>'add'])->sum('summ'),
                'out' => (clone $statistics)->where(['type'=>'out'])->sum('summ'),
            ];

            $statistics = $statistics->paginate(20);
            return view('log.playertransaction.list', compact('statistics', 'total'));
        }


        public function player_game_stat(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            // $availableUsers = $user->hierarchyUsersOnly();
            $availableShops = $user->availableShops();
            // $statistics = \App\Models\StatGame::select('stat_game.*')->orderBy('stat_game.date_time', 'DESC')->whereIn('user_id', $availableUsers);

            $statistics = \App\Models\StatGame::select('stat_game.*')->orderBy('stat_game.date_time', 'DESC')->orderBy('stat_game.id', 'DESC');

            $start_date = date("Y-m-d 0:0:0");
            $end_date = date("Y-m-d 23:59:59");

            if ($request->join != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->join[0]);
                $end_date = preg_replace('/T/',' ', $request->join[1]);            
            }
            if (strtotime($end_date) - strtotime($start_date) >= 630000)
            {
                return redirect()->back()->withErrors(['검색시간을 7일 이내로 설정해주세요.']);
            }
            $statistics = $statistics->where('stat_game.date_time', '>=', $start_date);
            $statistics = $statistics->where('stat_game.date_time', '<=', $end_date );

            $statistics = $statistics->join('users', 'users.id', '=', 'stat_game.user_id');

            $searchName = '';
            $searchValue = '';
            if($request->search != ''){
                $searchValue = $request->search;
            }

            $searchName = $request->userID;

            if($searchValue != ''){
                if($searchName == 'player'){
                    $statistics = $statistics->where('users.username', 'like', '%' . $searchValue . '%');
                }

                if ($searchName == 'shop')
                {
                    $shop_ids = \App\Models\Shop::where('name', 'like', '%' . $searchValue . '%')->whereIn('id', $availableShops)->pluck('id')->toArray();
                    if (count($shop_ids) > 0) 
                    {
                        $availableShops = $shop_ids;
                    }
                    else
                    {
                        $availableShops = [-1];
                    }
                }

                if ($searchName == 'game')
                {
                    $statistics = $statistics->where('stat_game.game', 'like', '%'. $searchValue . '%');
                }

                if( $searchName == 'gametype' ) 
                {
                    $statistics = $statistics->where('stat_game.type', $searchValue);
                }
            }
            
            $statistics = $statistics->whereIn('stat_game.shop_id', $availableShops);


            if( $request->has('categories') && $request->categories > 0 ) 
            {

                $statistics = $statistics->where('stat_game.category_id', $request->categories);
            }

            $total = [
                'bet' => (clone $statistics)->sum('bet'),
                'win' => (clone $statistics)->sum('win'),
            ];

            $statistics = $statistics->paginate(50);

            //check if evolution+gameartcasino
            $master = $user;
            while ($master !=null && !$master->isInoutPartner())
            {
                $master = $master->referral;
            }
            $gacmerge = \App\Http\Controllers\GameProviders\GACController::mergeGAC_EVO($master->id);
            $categories = null;
            $website = \App\Models\WebSite::where('adminid', $master->id)->first();
            if ($website)
            {
                $categories = $website->categories->where('parent', 0)->where('view', 1);
            }
            else
            {
                $categories = \App\Models\Category::where(['site_id' => 0, 'shop_id' => 0,'view' => 1])->orderby('position', 'desc')->get();
            }
            return view('log.playergame.list', compact('statistics', 'total', 'gacmerge', 'categories'));
        }

        //입출금내역
        public function history(\Illuminate\Http\Request $request)
        {
            if (auth()->user()->hasRole('admin'))
            {
                $in_out_logs = \App\Models\WithdrawDeposit::whereIn('status', [\App\Models\WithdrawDeposit::DONE, \App\Models\WithdrawDeposit::CANCEL])->orderBy('created_at','desc');
            }
            else if (auth()->user()->hasRole('group'))
            {
                $comasters = auth()->user()->childPartners();
                $in_out_logs = \App\Models\WithdrawDeposit::whereIn('payeer_id', $comasters)->whereIn('status', [\App\Models\WithdrawDeposit::DONE, \App\Models\WithdrawDeposit::CANCEL])->orderBy('created_at','desc');
            }
            else if (auth()->user()->isInoutPartner())
            {
                $in_out_logs = \App\Models\WithdrawDeposit::where('payeer_id', auth()->user()->id)->whereIn('status', [\App\Models\WithdrawDeposit::DONE, \App\Models\WithdrawDeposit::CANCEL])->orderBy('created_at','desc');
            }
            else
            {
                $in_out_logs = \App\Models\WithdrawDeposit::where(['user_id' => auth()->user()->id])->whereIn('status', [\App\Models\WithdrawDeposit::DONE, \App\Models\WithdrawDeposit::CANCEL])->orderBy('created_at','desc');
            }

            if( $request->type != '' ) 
            {
                $in_out_logs = $in_out_logs->where('type', $request->type);
            }

            $start_date = date("Y-m-d 0:0:0");
            $end_date = date("Y-m-d 23:59:59");
            
            if( $request->dates != '' ) 
            {
                $start_date = preg_replace('/T/',' ', $request->dates[0]);
                $end_date = preg_replace('/T/',' ', $request->dates[1]);        
                if (!auth()->user()->hasRole('admin'))
                {
                    $d = strtotime($start_date);
                    if ($d < strtotime("-31 days"))
                    {
                        $start_date = date("Y-m-d H:i",strtotime("-31 days"));
                    }
                }
            }
            
            $in_out_logs = $in_out_logs->where('updated_at', '>=', $start_date);
            $in_out_logs = $in_out_logs->where('updated_at', '<=', $end_date);
            
            if( $request->user != '' ) 
            {
                $availableUsers = auth()->user()->availableUsers();
                $user_ids = \App\Models\User::whereIn('id', $availableUsers)->where('username','like', '%' . $request->user . '%')->pluck('id')->toArray();
                if (count($user_ids) > 0){
                    $in_out_logs = $in_out_logs->whereIn('user_id', $user_ids);
                }
                else
                {
                    $in_out_logs = $in_out_logs->where('user_id', -1);
                }
            }

            if ($request->partner != '')
            {
                $availablePartners = auth()->user()->hierarchyPartners();
                $partners = \App\Models\User::whereIn('id', $availablePartners)->orderBy('role_id','desc')->where('username','like', '%' . $request->partner . '%');
                if ($request->role != '')
                {
                    $partners = $partners->where('role_id', $request->role);
                }
                $partners = $partners->first();
                if ($partners) {
                    $childsPartners = $partners->hierarchyPartners();
                    $childsPartners[] = $partners->id;
                    $in_out_logs = $in_out_logs->whereIn('user_id', $childsPartners);
                }
                else
                {
                    $in_out_logs = $in_out_logs->where('user_id', -1);
                }
            }
            $total = [
                'add' => (clone $in_out_logs)->where(['type'=>'add', 'status'=>\App\Models\WithdrawDeposit::DONE])->sum('sum'),
                'out' => (clone $in_out_logs)->where(['type'=>'out', 'status'=>\App\Models\WithdrawDeposit::DONE])->sum('sum'),
            ];

            $in_out_logs = $in_out_logs->paginate(20);

            return view('backend.argon.dw.history', compact('in_out_logs','total'));
        }

        //슬롯환수금내역

        public function game_transaction(\Illuminate\Http\Request $request)
        {
            $statistics = \App\Models\BankStat::select('bank_stat.*')->orderBy('bank_stat.created_at', 'DESC');

            $start_date = date("Y-m-1 0:0:0");
            $end_date = date("Y-m-d H:i:s");

            if ($request->join != '')
            {
                $start_date = preg_replace('/T/',' ', $request->join[0]);
                $end_date = preg_replace('/T/',' ', $request->join[1]);            
            }
            $statistics = $statistics->where('bank_stat.created_at', '>=', $start_date);
            $statistics = $statistics->where('bank_stat.created_at', '<=', $end_date );

            if($request->search != ''){
                $statistics = $statistics->where('name', 'like', '%' . $request->search . '%');
            }

            $total = [
                'add' => (clone $statistics)->where('type', 'add')->sum('sum'),
                'out' => (clone $statistics)->where('type', 'out')->sum('sum'),
            ];
            $statistics = $statistics->paginate(20);
            return view('log.gametransaction.list', compact('statistics','total'));
        }

        //접속로그
        public function activity_index(\Illuminate\Http\Request $request)
        {
            $perPage = 20;
            $adminView = true;
            $shops = auth()->user()->availableShops();
            $ids = auth()->user()->availableUsers();
            $activities = \App\Models\UserActivity::select('user_activity.*')->orderBy('created_at', 'DESC');
            $start_date = date("Y-m-1 0:0:0");
            $end_date = date("Y-m-d H:i:s");

            if ($request->join != '')
            {
                $start_date = preg_replace('/T/',' ', $request->join[0]);
                $end_date = preg_replace('/T/',' ', $request->join[1]);            
            }
            $activities = $activities->where('created_at', '>=', $start_date);
            $activities = $activities->where('created_at', '<=', $end_date );
            
            $searchName = $request->userID;
            $searchValue = '';            

            if( $request->search != '' ) 
            {
                $searchValue = $request->search;
                
            }
            if($searchName != '' && $searchValue != ''){
                if($searchName == 'username'){
                    $userIds = \App\Models\User::where('username', 'like', '%' . $searchValue . '%')->pluck('id')->toArray();
                    $statistics = $activities->whereIn('user_id', $userIds);
                }else{
                    $activities = $activities->where($searchName, 'like', '%' . $searchValue . '%');
                }
            }
            $activities = $activities->whereIn('shop_id', $shops);
            
            $activities = $activities->paginate($perPage);
            return view('log.accesslog.list', compact('activities', 'adminView'));
        }
    }
}