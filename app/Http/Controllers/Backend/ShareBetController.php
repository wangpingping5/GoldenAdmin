<?php 
namespace App\Http\Controllers\Backend
{
    class ShareBetController extends \App\Http\Controllers\Controller
    {
        public function __construct(\App\Repositories\User\UserRepository $users)
        {
            $this->middleware('auth');
            $this->middleware('permission:access.admin.panel');
        }

        public function index(\Illuminate\Http\Request $request)
        {
            $comasterIds = auth()->user()->availableUsersByRole('comaster');
            $users = \App\Models\User::whereIn('id', $comasterIds)->whereIn('status', [\App\Support\Enum\UserStatus::ACTIVE, \App\Support\Enum\UserStatus::BANNED]);
            if ($request->search != '')
            {
                $users = $users->where('username', 'like', '%' . $request->search . '%');
            }
            $usersum = (clone $users)->get();
            $sum = 0;
            $count = 0;
            foreach ($usersum as $u)
            {
                $sum = $sum + $u->deal_balance;
                
            }

            $total = [
                'count' => $users->count(),
                'deal' => $sum
            ];

            $users = $users->paginate(20);
            return view('share.index', compact('users','total'));
        }
        public function setting(\Illuminate\Http\Request $request)
        {
            $partner_id = $request->id;
            if ($partner_id == 0)
            {
                $partner = auth()->user();
            }
            else
            {
                $partner = \App\Models\User::where('id', $partner_id)->first();

                $comasterIds = auth()->user()->availableUsersByRole('comaster');
                if (!$partner || !in_array($partner_id, $comasterIds))
                {
                    return redirect()->back()->withErrors(['파트너를 찾을수 없습니다']);
                }
            }
            //currently support gac
            $categories = \App\Models\Category::where(['shop_id'=>0,'site_id'=>0,'type' => 'live','view' => 1, 'href'=>'gvo'])->get();

            $sharebetinfos = \App\Models\ShareBetInfo::where(['partner_id' => $partner->id, 'share_id' => $partner->parent_id])->get();

            return view('share.setting', compact('partner', 'categories','sharebetinfos'));
        }
        public function setting_store(\Illuminate\Http\Request $request)
        {
            $partner_id = $request->user_id;
            $cat_id = $request->cat_id;

            $partner = \App\Models\User::where('id', $partner_id)->first();
            $comasterIds = auth()->user()->availableUsersByRole('comaster');
            if (!$partner || !in_array($partner_id, $comasterIds))
            {
                return redirect()->back()->withErrors(['파트너를 찾을수 없습니다']);
            }
            $sharekeys = [];
            foreach (\App\Models\ShareBetInfo::BET_TYPES as $type => $values)
            {
                foreach ($values as $k => $v)
                {
                    $sharekeys[] = $k;
                }
            }
            $data = $request->all($sharekeys);
            //filter minimum value
            $minlimit = 0;
            foreach ($data as $key => $value)
            {
                if ($value > 0 && ($minlimit==0 || $minlimit > $value))
                {
                    $minlimit = $value;
                }
            }


            $alreadydata = \App\Models\ShareBetInfo::where(['partner_id' => $partner_id, 'share_id' => $partner->parent_id, 'category_id' => $cat_id])->first();
            if ($alreadydata)
            {
                if ($minlimit == 0)
                {
                    $alreadydata->delete();
                }
                else
                {
                    $alreadydata->update(['minlimit' => $minlimit, 'limit_info' => json_encode($data)]);
                }
            }
            else
            {
                if ($minlimit > 0)
                {
                    \App\Models\ShareBetInfo::create([
                        'partner_id' => $partner_id, 
                        'share_id' => $partner->parent_id, 
                        'category_id' => $cat_id, 
                        'minlimit' => $minlimit,
                        'limit_info' => json_encode($data)
                    ]);
                }
            }
            if (auth()->user()->hasRole('comaster')){
                return redirect()->back()->withSuccess(['받치기 설정을 업데이트했습니다']);
            }else{
                return redirect()->to(argon_route('game.share'))->withSuccess(['받치기 설정을 업데이트했습니다']);
            }
        }
        public function convert_deal(\Illuminate\Http\Request $request)
        {
        }

        public function gamestat(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            
            $sharebetlogs = \App\Models\ShareBetlog::select('sharebet_log.*')->orderBy('sharebet_log.date_time', 'desc');
            if ($user->hasRole('comaster'))
            {
                $sharebetlogs = $sharebetlogs->where('partner_id', $user->id);
            }
            else if ($user->hasRole('group'))
            {
                $sharebetlogs = $sharebetlogs->where('share_id', $user->id);
            }

            $start_date = date("Y-m-d H:i:s", strtotime("-1 days"));
            $end_date = date("Y-m-d H:i:s");
            if ($request->join != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->join[0]);
                $end_date = preg_replace('/T/',' ', $request->join[1]);            
            }
            $sharebetlogs = $sharebetlogs->where('sharebet_log.date_time', '>=', $start_date);
            $sharebetlogs = $sharebetlogs->where('sharebet_log.date_time', '<=', $end_date );

            $sharebetlogs = $sharebetlogs->join('users', 'users.id', '=', 'sharebet_log.user_id');
            $searchName = 'username';
            $searchValue = '';
            if($request->search != ''){
                $searchValue = $request->search;
            }
            if($request->userID != ''){
                $searchName = $request->userID;
            }
            if($searchName != '' && $searchValue != ''){
                if($searchName == 'username'){
                    $sharebetlogs = $sharebetlogs->where('users.username', 'like', '%' . $searchValue . '%');    
                }else if($searchName == 'game'){
                    $sharebetlogs = $sharebetlogs->where('sharebet_log.game', 'like', '%'. $searchValue . '%');    
                }else{
                    $availableUsers = auth()->user()->availableUsers();
                    $user_ids = \App\Models\User::where('username', 'like', '%' . $searchValue . '%')->whereIn('id', $availableUsers)->pluck('id')->toArray();
                    if (count($user_ids) > 0) 
                    {
                        $sharebetlogs = $sharebetlogs->whereIn('sharebet_log.partner_id', $user_ids);
                    }
                    else
                    {
                        $sharebetlogs = $sharebetlogs->whereIn('sharebet_log.partner_id', [-1]);
                    }
                }
                
            }

            // if( $request->win_from != '' ) 
            // {
            //     $sharebetlogs = $sharebetlogs->where('sharebet_log.win', '>=', $request->win_from);
            // }
            // if( $request->win_to != '' ) 
            // {
            //     $sharebetlogs = $sharebetlogs->where('sharebet_log.win', '<=', $request->win_to);
            // }

            
            $totallogs = (clone $sharebetlogs)->get();
            $total = [
                'bet' => $totallogs->sum('bet'),
                'win' => $totallogs->sum('win'),
                'sharebet' => $totallogs->sum('bet') - $totallogs->sum('betlimit'),
                'sharewin' => $totallogs->sum('win') - $totallogs->sum('winlimit'),
                'sharedeal' => $totallogs->sum('deal_share'),
            ];

            $sharebetlogs = $sharebetlogs->paginate(20);
            
            return view('log.sharegame.list', compact('sharebetlogs','total'));

        }

        public function report_childdaily(\Illuminate\Http\Request $request)
        {
            $parent_id = $request->id;
            $param = explode('~', $parent_id);
            if(!is_array($param) || count($param) < 2){
                return redirect()->back()->withErrors('찾을수 없습니다.');
            }
            $user_id = $param[0];
            $date = $param[1];
            $user = \App\Models\User::where('id', $user_id)->get()->first();
            if (!$user)
            {
                return redirect()->back()->withErrors('찾을수 없습니다.');
            }
            $users = $user->childPartners();

            $summary = \App\Models\ShareBetSummary::where('date', '=', $date)->whereIn('user_id', $users);
            
            $summary = $summary->orderBy('user_id', 'ASC')->orderBy('date', 'ASC');
            $summary = $summary->get();
            return view('backend.argon.share.partials.childs_daily', compact('summary', 'parent_id'));
        }

        public function report_daily(\Illuminate\Http\Request $request)
        {
            $users = [auth()->user()->id];
            if (auth()->user()->hasRole('admin'))
            {
                $users = auth()->user()->childPartners();
            }
            $user = null;
            if ($request->partner != '')
            {
                $availablePartners = auth()->user()->hierarchyPartners();
                $partners = \App\Models\User::where('username', 'like', '%' . $request->partner . '%')->whereIn('id', $availablePartners)->pluck('id')->toArray();
                if (count($partners) == 0)
                {
                    $partners = [-1];
                }
                $users = $partners;
            }

            
            $start_date = date("Y-m-d", strtotime("-1 days"));
            $end_date = date("Y-m-d");
            if ($request->dates != '')
            {
                $start_date = preg_replace('/T/',' ', $request->dates[0]);
                $end_date = preg_replace('/T/',' ', $request->dates[1]);            
            }

            if (!auth()->user()->hasRole('admin'))
            {
                $d = strtotime($start_date);
                if ($d < strtotime("-30 days"))
                {
                    $start_date = date("Y-m-d",strtotime("-30 days"));
                }
            }

            $summary = \App\Models\ShareBetSummary::where('date', '>=', $start_date)->where('date', '<=', $end_date)->whereIn('user_id', $users);
            $total = [
                'daterange' => "$start_date~$end_date",
                'bet' => $summary->sum('bet'),
                'win' => $summary->sum('win'),
                'deallimit' => $summary->sum('deal_limit'),
                'betlimit' => $summary->sum('betlimit'),
                'winlimit' => $summary->sum('winlimit'),
                'sharebet' => $summary->sum('bet') - $summary->sum('betlimit'),
                'sharewin' => $summary->sum('win') - $summary->sum('winlimit'),
                'sharedeal' => $summary->sum('deal_share'),
                'dealout' => $summary->sum('deal_out'),
            ];
            $summary = $summary->orderBy('user_id', 'ASC')->orderBy('date', 'desc');
            $summary = $summary->paginate(31);
            return view('report.share_daily', compact('summary','total'));

        }

        public function report_game(\Illuminate\Http\Request $request)
        {

        }


    }

}
