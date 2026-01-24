<?php 
namespace App\Http\Controllers\Backend
{
    class DashboardController extends \App\Http\Controllers\Controller
    {
        private $users = null;
        private $activities = null;
        public function __construct()
        {
            $this->middleware('auth');
        }
        public function index()
        {
            set_time_limit(0);
            $user = auth()->user();
            $availableShops = auth()->user()->availableShops();
            $start_date = date("Y-m-d", strtotime("-31 days"));
            $end_date = date("Y-m-d");
            $this_date = date("Y-m-1");
            $todayprofit = 0;
            $todaybetwin = 0;
            $mtbet = 0;
            $mtwin = 0;
            $mtin = 0;
            $mtout = 0;
            $monthrtp = 0;
            $monthpayout = 0;
            $monthsummary = null;
            $monthInout = null;
            $thismonthsummary = null;
            $monthcategory = null;
            $todayInOut = null;

            if (count($availableShops) > 0){
                if (auth()->user()->isInOutPartner())
                {
                    $monthsummary = \App\Models\CategorySummary::selectRaw('sum(totalbet) as totalbet, sum(totalwin) as totalwin, date')->where('user_id', auth()->user()->id)->where('date', '>=', $start_date)->where('date', '<=', $end_date)->groupby('user_id','date')->get();
                }
                else
                {
                    $monthsummary = \App\Models\CategorySummary::selectRaw('sum(totaldealbet) as totalbet, sum(totaldealwin) as totalwin, date')->where('user_id', auth()->user()->id)->where('date', '>=', $start_date)->where('date', '<=', $end_date)->groupby('user_id','date')->get();
                }
                $monthInout = \App\Models\DailySummary::where('daily_summary.user_id', auth()->user()->id)->where('daily_summary.date', '>=', $start_date)->where('daily_summary.date', '<=', $end_date)->get();
                $thismonthsummary = \App\Models\DailySummary::where('user_id', auth()->user()->id)->where('date', '>=', $this_date)->get();

                $totalQuery = 'SELECT SUM(totalbet) AS totalbet, SUM(totalwin) AS totalwin, category_id, if (w_categories.parent>0, w_categories.parent, w_categories.id) AS parent, w_categories.title as title FROM w_category_summary JOIN w_categories ON w_categories.id=w_category_summary.category_id WHERE ';
                $totalQuery = $totalQuery . "w_category_summary.date>=\"$start_date\" AND w_category_summary.date<=\"$end_date\" ";
                $totalQuery = $totalQuery . "AND w_category_summary.user_id=$user->id ";
                $totalQuery = $totalQuery . "GROUP BY w_category_summary.category_id ORDER BY totalbet desc limit 5";
                if (!auth()->user()->hasRole('admin'))
                {
                    $totalQuery = "SELECT SUM(a.totalbet) AS totalbet, SUM(a.totalwin) AS totalwin,  a.parent AS category_id, b.trans_title as title FROM ($totalQuery) a JOIN w_categories_trans_kr as b on b.category_id=a.parent  GROUP BY a.parent ORDER BY totalbet desc";
                }
                else
                {
                    $totalQuery = "SELECT a.totalbet AS totalbet, a.totalwin AS totalwin,  a.parent AS category_id, b.trans_title as title FROM ($totalQuery) a JOIN w_categories_trans_kr as b on b.category_id=a.parent  ORDER BY totalbet desc";
                }
                $monthcategory = \DB::select($totalQuery);

                // $monthcategory = \App\Models\CategorySummary::where('user_id', auth()->user()->id)->where('date', '>=', $start_date)->where('date', '<=', $end_date)->groupby('category_id')->selectRaw('category_id, sum(totalbet) as bet, sum(totalwin) as win')->orderby('bet','desc')->limit(5)->get();
                $todaysummary = \App\Models\DailySummary::where('user_id', auth()->user()->id)->where('date', $end_date)->first();
                if ($todaysummary){
                    $todayInOut = $todaysummary->calcInOut();
                    $todayprofit = $todayInOut['totalin'] - $todayInOut['totalout'];
                    $betwin = $todaysummary->betwin();
                    if (auth()->user()->isInOutPartner())
                    {
                        $todaysummary->totalbet = $betwin['total']['totalbet'];
                        $todaysummary->totalwin = $betwin['total']['totalwin'];
                    }
                    else
                    {
                        $todaysummary->totalbet = $betwin['total']['totaldealbet'];
                        $todaysummary->totalwin = $betwin['total']['totaldealwin'];
                    }
                    $todaybetwin = $todaysummary->totalbet - $todaysummary->totalwin;

                }
                if ($thismonthsummary->count() > 0)
                {
                    foreach ($thismonthsummary as $m)
                    {
                        $betwin = $m->betwin();
                        $mtbet = $mtbet + $betwin['total']['totalbet'];
                        $mtwin = $mtwin + $betwin['total']['totalwin'];
                    }
                    $mtin = $thismonthsummary->sum('totalin');
                    $mtout = $thismonthsummary->sum('totalout');
                    if ($todaysummary)
                    {
                        $mtin = $mtin - $todaysummary->totalin + $todayInOut['totalin'];
                        $mtout = $mtout - $todaysummary->totalout + $todayInOut['totalout'];
                    }
                    $monthprofit = $mtbet - $mtwin ;
                    if ($mtbet > 0){
                        $monthrtp = $mtwin / $mtbet  ; 
                    }
                    $monthbetwin = $mtin - $mtout;
                    if ($mtin > 0){
                        $monthpayout = $mtout / $mtin;
                    }
                }
            }
            
            $stats = [
                'todaydw' => $todayprofit,
                'todaybetwin' => $todaybetwin,
                'todayin' => $todayInOut?$todayInOut['totalin']:0,
                'todayout' => $todayInOut?$todayInOut['totalout']:0,

                'monthbet' => $mtbet,
                'monthwin' => $mtwin,
                'monthrtp' => $monthrtp * 100,
                'monthin' => $mtin,
                'monthout' => $mtout,
                'monthpayout' => $monthpayout * 100,
            ];
            
            return view('dashboard.admin', compact('monthsummary', 'monthInout', 'monthcategory', 'todaysummary','stats'));
        }
        public function statistics(\Illuminate\Http\Request $request)
        {
            $users = auth()->user()->hierarchyUsersOnly();
            $statistics = \App\Models\Transaction::select('transactions.*')->orderBy('transactions.created_at', 'DESC');
            $statistics = $statistics->whereIn('transactions.user_id', $users);
            //not show admin payer
            if (!auth()->user()->hasRole('admin'))
            {
                $admin = \App\Models\User::where('role_id', 8)->pluck('id')->toArray();
                $statistics = $statistics->whereNotIn('transactions.payeer_id', $admin);
            }
            if( $request->system_str != '' ) 
            {
                $system = $request->system_str;
                $statistics = $statistics->where(function($q) use ($system)
                {
                    $q->where('transactions.system', 'like', '%' . $system . '%')->orWhere('transactions.value', 'like', '%' . str_replace('-', '', $system) . '%')->orWhereHas('admin', function($query) use ($system)
                    {
                        $query->where('username', 'like', '%' . $system . '%');
                    });
                });
            }
            if( $request->type != '' ) 
            {
                $statistics = $statistics->where('transactions.type', $request->type);
            }
            if( $request->sum_from != '' ) 
            {
                $statistics = $statistics->where('transactions.summ', '>=', $request->sum_from);
            }
            if( $request->sum_to != '' ) 
            {
                $statistics = $statistics->where('transactions.summ', '<=', $request->sum_to);
            }
            if( $request->user != '' ) 
            {
                $statistics = $statistics->join('users', 'users.id', '=', 'transactions.user_id');
                $statistics = $statistics->where('users.username', 'like', '%' . $request->user . '%');
            }
            if( $request->shopname != '' ) 
            {
                $statistics = $statistics->join('shops', 'shops.id', '=', 'transactions.shop_id');
                $statistics = $statistics->where('shops.name', 'like', '%' . $request->shopname . '%');
            }
            if( $request->payeername != '' ) 
            {
                if( !$request->user) 
                {
                    $statistics = $statistics->join('users', 'users.id', '=', 'transactions.payeer_id');
                }
                $statistics = $statistics->where('users.username', 'like', '%' . $request->payeername . '%');
            }
            $start_date = date("Y-m-d") . " 00:00:00";
            $end_date = date("Y-m-d H:i:s");
            if( $request->dates != '' ) 
            {
                $dates = explode(' - ', $request->dates);
                $start_date = $dates[0];
                $end_date = $dates[1];
            }

            $statistics = $statistics->where('transactions.created_at', '>=', $start_date);
            $statistics = $statistics->where('transactions.created_at', '<=', $end_date);

            
            if( $request->shifts != '' ) 
            {
                $shift = \App\Models\OpenShift::find($request->shifts);
                if( $shift ) 
                {
                    $statistics = $statistics->where('transactions.created_at', '>=', $shift->start_date);
                    if( $shift->end_date ) 
                    {
                        $statistics = $statistics->where('transactions.created_at', '<=', $shift->end_date);
                    }
                }
            }
            $stats = $statistics->get();
            $statistics = $stats->sortByDesc('created_at')->paginate(20);
            $partner = 0;
            return view('backend.Default.stat.pay_stat', compact('stats', 'statistics','partner'));
        }

        public function statistics_partner(\Illuminate\Http\Request $request)
        {
            $users = auth()->user()->hierarchyPartners();
            array_push($users,auth()->user()->id);
            $statistics = \App\Models\Transaction::select('transactions.*')->orderBy('transactions.created_at', 'DESC')->orderBy('transactions.balance', 'ASC');
            $statistics = $statistics->whereIn('transactions.user_id', $users);
            //not show admin payer
            if (!auth()->user()->hasRole('admin'))
            {
                $admin = \App\Models\User::where('role_id', 8)->pluck('id')->toArray();
                $statistics = $statistics->whereNotIn('transactions.payeer_id', $admin);
            }
            if( $request->system_str != '' ) 
            {
                $system = $request->system_str;
                $statistics = $statistics->where(function($q) use ($system)
                {
                    $q->where('transactions.system', 'like', '%' . $system . '%')->orWhere('transactions.value', 'like', '%' . str_replace('-', '', $system) . '%')->orWhereHas('admin', function($query) use ($system)
                    {
                        $query->where('username', 'like', '%' . $system . '%');
                    });
                });
            }
            if( $request->type != '' ) 
            {
                $statistics = $statistics->where('transactions.type', $request->type);
            }
            if( $request->in_out != '' ) 
            {
                if ($request->in_out == 1)
                {
                    $statistics = $statistics->whereNotNull('transactions.request_id');
                }
                else
                {
                    $statistics = $statistics->whereNull('transactions.request_id');
                }
            }
            if( $request->sum_from != '' ) 
            {
                $statistics = $statistics->where('transactions.summ', '>=', $request->sum_from);
            }
            if( $request->sum_to != '' ) 
            {
                $statistics = $statistics->where('transactions.summ', '<=', $request->sum_to);
            }
            if( $request->user != '' ) 
            {
                $statistics = $statistics->join('users', 'users.id', '=', 'transactions.user_id');
                $statistics = $statistics->where('users.username', 'like', '%' . $request->user . '%');
            }
            else if( $request->payeer != '' ) 
            {
                $statistics = $statistics->join('users', 'users.id', '=', 'transactions.payeer_id');
                $statistics = $statistics->where('users.username', 'like', '%' . $request->payeer . '%');
            }
            if( $request->dates != '' ) 
            {
                $dates = explode(' - ', $request->dates);
                $statistics = $statistics->where('transactions.created_at', '>=', $dates[0]);
                $statistics = $statistics->where('transactions.created_at', '<=', $dates[1]);
            }
            if( $request->shifts != '' ) 
            {
                $shift = \App\Models\OpenShift::find($request->shifts);
                if( $shift ) 
                {
                    $statistics = $statistics->where('transactions.created_at', '>=', $shift->start_date);
                    if( $shift->end_date ) 
                    {
                        $statistics = $statistics->where('transactions.created_at', '<=', $shift->end_date);
                    }
                }
            }
            $stats = $statistics->get();            
            $statistics = $stats->sortByDesc('created_at')->paginate(20);
            $partner = 1;
            return view('backend.Default.stat.pay_stat_partner', compact('stats', 'statistics','partner'));
        }
    }

}
