<?php 
namespace App\Http\Controllers\Backend
{
    class ReportController extends \App\Http\Controllers\Controller
    {
        private $users = null;
        private $max_users = 10000;
        public function __construct(\App\Repositories\User\UserRepository $users)
        {
            $this->middleware('auth');
            $this->middleware('permission:access.admin.panel');
            $this->users = $users;
        }
        public function report_childdaily(\Illuminate\Http\Request $request, $daily_type='')
        {
            $parent_id = $request->id;
            $param = explode('~', $parent_id);
            if(!is_array($param) || count($param) < 2){
                return redirect()->back()->withErrors('찾을수 없습니다.');
            }
            $user_id = $param[0];
            $date = $param[1];
            $availableUsers = auth()->user()->availableUsers();
            $user = \App\Models\User::where('id', $user_id)->first();
            if (!$user || !in_array($user_id, $availableUsers))
            {
                return redirect()->back()->withErrors('찾을수 없습니다.');
            }
            $users = $user->childPartners();
            $sumInfo = '';
            if (count($param) > 2)
            {
                $enddate = $param[2];
                $summary = \App\Models\DailySummary::groupBy('user_id')->where('date', '>=', $date)->where('date', '<=', $enddate)->whereIn('user_id', $users)->selectRaw('sum(totalin) as totalin, sum(totalout) as totalout,sum(moneyin) as moneyin,sum(moneyout) as moneyout,sum(dealout) as dealout,sum(totalbet) as totalbet,sum(totalwin) as totalwin,sum(totaldealbet) as totaldealbet,sum(totaldealwin) as totaldealwin,sum(total_deal) as total_deal,sum(total_mileage) as total_mileage,sum(total_ggr) as total_ggr,sum(total_ggr_mileage) as total_ggr_mileage, user_id, "" as date')->get();
                $sumInfo = $date .'~' .$enddate;
            }
            else
            {
                $summary = \App\Models\DailySummary::where('date', '=', $date)->whereIn('user_id', $users);
                $summary = $summary->orderBy('user_id', 'ASC')->orderBy('date', 'ASC');
                $summary = $summary->get();
                $sumInfo = '';
            }
            
            // if(isset($daily_type) && $daily_type == 'dw'){
            //     return view('report.partials.childs_dailydw', compact('summary', 'parent_id','sumInfo'));
            // }else{
            //     return view('report.partials.childs_daily', compact('summary', 'parent_id','sumInfo'));
            // }
            $child_role_id = 0;
            if(count($summary) > 0){
                $child_role_id = $summary[0]->user->role_id;
            }
            return view('report.partials.childs_daily', compact('summary', 'parent_id','sumInfo', 'child_role_id'));
        }
        public function report_daily(\Illuminate\Http\Request $request)
        {
            $users = [auth()->user()->id];
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

            if ($request->role != '')
            {
                if ($request->partner != '')
                {
                    $partners = \App\Models\User::where('role_id',   $request->role)->whereIn('id', $users)->pluck('id')->toArray();
                    if (count($partners) == 0)
                    {
                        return redirect()->back()->withErrors('에이전트를 찾을수 없습니다.');
                    }
                    $users = $partners;
                }
                else
                {
                    $availablePartners = auth()->user()->hierarchyPartners();
                    $partners = \App\Models\User::where('role_id',   $request->role)->whereIn('id', $availablePartners)->pluck('id')->toArray();
                    if (count($partners) == 0)
                    {
                        return redirect()->back()->withErrors('에이전트를 찾을수 없습니다.');
                    }
                    $users = $partners;
                }
            }
            
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d");
            if ($request->dates != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->dates[0]);
                $end_date = preg_replace('/T/',' ', $request->dates[1]);            
            }

            if (!auth()->user()->hasRole('admin'))
            {
                $d = strtotime($start_date);
                if ($d < strtotime("-31 days"))
                {
                    $start_date = date("Y-m-d",strtotime("-31 days"));
                }
            }

            $summary = \App\Models\DailySummary::where('date', '>=', $start_date)->where('date', '<=', $end_date)->whereIn('user_id', $users);
            $total = [
                'totalbet' => $summary->sum('totalbet'),
                'totalwin' => $summary->sum('totalwin'),
                'ggr' => $summary->sum('totalbet') - $summary->sum('totalwin'),
            ];
            $summary = $summary->orderBy('user_id', 'ASC')->orderBy('date', 'desc');
            $summary = $summary->paginate(31);
            $type = 'daily';
            return view('backend.argon.report.daily', compact('summary','total','type'));
        }
        public function update_dailydw(\Illuminate\Http\Request $request)
        {
            if (!auth()->user()->hasRole('admin'))
            {
                return redirect()->back()->withSuccess([trans('UnauthorizedOperating')]);    
            }
            $summaryId = $request->summaryid;
            $summary = \App\Models\DailySummary::where('id', $summaryId)->first();
            if (!$summary)
            {
                return redirect()->back()->withSuccess(['정산데이터를 찾을수 없습니다']);    
            }
            $eventString = '일별정산데이터 수정 : '. $summary->user->username . '/' . $summary->date . '/'  ;

            if ($request->has('totalbet'))
            {
                $eventString .= '베팅금 / ' . $summary->totalbet . '=>' . $request->totalbet;
                $summary->update(['totalbet' => $request->totalbet]);
            }

            if ($request->has('totalwin'))
            {
                $eventString .= '당첨금 / ' . $summary->totalwin . '=>' . $request->totalwin;
                $summary->update(['totalwin' => $request->totalwin]);
            }

            if ($request->has('totaldealbet'))
            {
                $eventString .= '공베팅금 / ' . $summary->totaldealbet . '=>' . $request->totaldealbet;
                $summary->update(['totaldealbet' => $request->totaldealbet]);
            }

            if ($request->has('totaldealwin'))
            {
                $eventString .= '공당첨금 / ' . $summary->totaldealwin . '=>' . $request->totaldealwin;
                $summary->update(['totaldealwin' => $request->totaldealwin]);
            }

            event(new \App\Events\GeneralEvent($eventString));
            return redirect()->back()->withSuccess(['정산데이터를 수정했습니다']);
        }
        public function update_game(\Illuminate\Http\Request $request)
        {
            if (!auth()->user()->hasRole('admin'))
            {
                return redirect()->back()->withSuccess([trans('UnauthorizedOperating')]);    
            }
            $summaryId = $request->summaryid;
            $summary = \App\Models\CategorySummary::where('id', $summaryId)->first();
            if (!$summary)
            {
                return redirect()->back()->withSuccess(['정산데이터를 찾을수 없습니다']);    
            }
            $eventString = '게임정산데이터 수정 : '. $summary->user->username . '/' . $summary->date . '/'  . $summary->category->title . '/' ;
            if ($request->has('totalbet'))
            {
                $eventString .= '베팅금 / ' . $summary->totalbet . '=>' . $request->totalbet;
                $summary->update(['totalbet' => $request->totalbet]);
                
            }

            if ($request->has('totalwin'))
            {
                $eventString .= '당첨금 / ' . $summary->totalwin . '=>' . $request->totalwin;
                $summary->update(['totalwin' => $request->totalwin]);
            }
            event(new \App\Events\GeneralEvent($eventString));
            return redirect()->back()->withSuccess(['정산데이터를 수정했습니다']);
        }
        public function report_dailydw(\Illuminate\Http\Request $request)
        {
            $users = [auth()->user()->id];
            $user = null;
            if ($request->partner != '')
            {
                $availablePartners = auth()->user()->hierarchyPartners();
                $partners = \App\Models\User::where('username',  $request->partner)->whereIn('id', $availablePartners);
                if($request->role != ''){
                    $partners->where('role_id',   $request->role);
                }
                $partners = $partners->pluck('id')->toArray();
                if (count($partners) == 0)
                {
                    return redirect()->back()->withErrors('에이전트를 찾을수 없습니다.');
                }
                $users = $partners;
            }
            
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d");
            if ($request->dates != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->dates[0]);
                $end_date = preg_replace('/T/',' ', $request->dates[1]);            
            }

            if (!auth()->user()->hasRole('admin'))
            {
                $d = strtotime($start_date);
                if ($d < strtotime("-31 days"))
                {
                    $start_date = date("Y-m-d",strtotime("-31 days"));
                }
            }

            $summary = \App\Models\DailySummary::where('date', '>=', $start_date)->where('date', '<=', $end_date)->whereIn('user_id', $users);

            $betwin = [
                'live' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                    'totaldealbet' => 0,
                    'totaldealwin' => 0,
                    'total_deal' => 0,
                    'total_mileage' => 0,
                    'total_ggr' => 0,
                    'total_ggr_mileage' => 0,
                ],
                'slot' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                    'totaldealbet' => 0,
                    'totaldealwin' => 0,
                    'total_deal' => 0,
                    'total_mileage' => 0,
                    'total_ggr' => 0,
                    'total_ggr_mileage' => 0,
                ],
                'sports' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                    'totaldealbet' => 0,
                    'totaldealwin' => 0,
                    'total_deal' => 0,
                    'total_mileage' => 0,
                    'total_ggr' => 0,
                    'total_ggr_mileage' => 0,
                ],
                'card' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                    'totaldealbet' => 0,
                    'totaldealwin' => 0,
                    'total_deal' => 0,
                    'total_mileage' => 0,
                    'total_ggr' => 0,
                    'total_ggr_mileage' => 0,
                ],
                'pball' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                    'totaldealbet' => 0,
                    'totaldealwin' => 0,
                    'total_deal' => 0,
                    'total_mileage' => 0,
                    'total_ggr' => 0,
                    'total_ggr_mileage' => 0,
                ],
                'total' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                    'totaldealbet' => 0,
                    'totaldealwin' => 0,
                    'total_deal' => 0,
                    'total_mileage' => 0,
                    'total_ggr' => 0,
                    'total_ggr_mileage' => 0,
                ],
            ];

            $total = [
                'id' => '',
                'user_id' => 0,
                'role_id' => 0,
                'daterange' => "$start_date~$end_date",
                'totalin' => $summary->sum('totalin'),
                'totalout' => $summary->sum('totalout'),
                'moneyin' => $summary->sum('moneyin'),
                'moneyout' => $summary->sum('moneyout'),
                'dealout' => $summary->sum('dealout'),
                'betwin' => $betwin,
                // 'totalbet' => $summary->sum('totalbet'),
                // 'totalwin' => $summary->sum('totalwin'),
                // 'totaldealbet' => $summary->sum('totaldealbet'),
                // 'totaldealwin' => $summary->sum('totaldealwin'),
                // 'total_deal' => $summary->sum('total_deal'),
                // 'total_mileage' => $summary->sum('total_mileage'),
                // 'total_ggr' => $summary->sum('total_ggr'),
                // 'total_ggr_mileage' => $summary->sum('total_ggr_mileage'),
                'balance' => $summary->sum('balance'),
                'childsum' => $summary->sum('childsum'),
            ];

            
            $sumuser = null;
            $user_id = -1;
            $role_id = $request->role != ''?$request->role:auth()->user()->role_id;
            
            $todaySumm = (clone $summary)->get();
            foreach ($todaySumm as $su)
            {
                $sumuser = $su->user;
                $user_id = $su->user->id;
                $role_id = $su->user->role_id;
                if ($su->date == date('Y-m-d'))
                {
                    $inout = $su->calcInOut();
                    $total['totalin'] = $total['totalin'] - $su->totalin + $inout['totalin'];
                    $total['totalout'] = $total['totalout'] - $su->totalout + $inout['totalout'];
                    $total['moneyin'] = $total['moneyin'] - $su->moneyin + $inout['moneyin'];
                    $total['moneyout'] =$total['moneyout'] - $su->moneyout + $inout['moneyout'];
                }
                foreach ($betwin as $type => $tdata)
                {
                    if ($type != 'total')
                    {
                        $categories = \App\Models\Category::where(['shop_id' => 0, 'site_id' => 0, 'type' => $type])->pluck('id')->toArray();
                        $catsums = $su->categorySummary->whereIn('category_id', $categories);
                        foreach ($catsums as $csum)
                        {
                            foreach ($tdata as $field => $tvalue)
                            {
                                $betwin[$type][$field] = $betwin[$type][$field] + $csum->{$field};
                                $betwin['total'][$field] = $betwin['total'][$field] + $csum->{$field};
                            }
                        }
                    }
                }
            }
            $total['betwin'] = $betwin;
            $total['id'] = (count($users)==1 && $sumuser)?$sumuser->username:'';
            $total['user_id'] = $user_id;
            $total['role_id'] = $role_id;

            
            
            $summary = $summary->orderBy('user_id', 'ASC')->orderBy('date', 'desc');
            $summary = $summary->paginate(31);
            return view('report.daily', compact('summary','total'));
        }
        public function report_childmonthly(\Illuminate\Http\Request $request)
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

            $summary = \App\Models\DailySummary::where('date', '=', $date)->where('type','monthly')->whereIn('user_id', $users);
            $summary = $summary->orderBy('user_id', 'ASC')->orderBy('date', 'ASC');
            $summary = $summary->get();
            return view('backend.argon.report.partials.childs_daily', compact('summary', 'parent_id'));
        }
        public function report_monthly(\Illuminate\Http\Request $request)
        {
            $user_id = $request->input('parent');
            $users = [];
            $shops = [];
            $user = null;
            if ($request->search != '')
            {
                $availablePartners = auth()->user()->hierarchyPartners();
                $partner = \App\Models\User::where('username', 'like', '%' . $request->search . '%')->whereIn('id', $availablePartners)->first();
                if (!$partner)
                {
                    $partners = [-1];
                }
                $user_id = $partner->id;
                $dates = $request->dates;
                $users = [$user_id];
            }
            else
            {
                if($user_id == null || $user_id == 0)
                {
                    $user_id = auth()->user()->id;
                    $users = [$user_id];
                    $request->session()->put('dates', null);
                    $dates = $request->dates;
                }
                else {
                    if (auth()->user()->id!=$user_id && !in_array($user_id, auth()->user()->hierarchyPartners()))
                    {
                        return redirect()->back()->withErrors([trans('No Permission')]);
                    }
                    $user = \App\Models\User::where('id', $user_id)->get()->first();
                    if (!$user)
                    {
                        return redirect()->back()->withErrors('찾을수 없습니다.');
                    }
                    $users = $user->childPartners();
                    $dates = ($request->session()->exists('dates') ? $request->session()->get('dates') : '');
                }
            }
            
            $start_date = date("Y-m-01",strtotime("-1 months"));
            $end_date = date("Y-m-01");
            if($dates != null && $dates != ''){
                $start_date = $dates[0] . '-01';
                $end_date = $dates[1] . '-01';
                $request->session()->put('dates', $dates);
            }

            $summary = \App\Models\DailySummary::where('date', '>=', $start_date)->where('date', '<=', $end_date)->where('type','monthly')->whereIn('user_id', $users);
            $summary = $summary->orderBy('user_id', 'ASC')->orderBy('date', 'ASC');
            $summary = $summary->paginate(31);
            $type = 'monthly';
        
            return view('backend.argon.report.daily', compact('start_date', 'end_date', 'user', 'summary','type'));
        }
        public function report_game(\Illuminate\Http\Request $request)
        {
            $statistics = \App\Models\CategorySummary::orderBy('category_summary.date', 'DESC');
        
            $totalQuery = 'SELECT w_category_summary.id as id, SUM(totalbet) AS totalbet, SUM(totalwin) AS totalwin, SUM(totaldealbet) AS totaldealbet, SUM(totaldealwin) AS totaldealwin, SUM(total_deal) as total_deal, SUM(total_mileage) as total_mileage, SUM(total_ggr) as total_ggr, SUM(total_ggr_mileage) as total_ggr_mileage, category_id, if (w_categories.parent>0, w_categories.parent, w_categories.id) AS parent, w_categories.title as title, w_categories.provider as provider, w_categories.type FROM w_category_summary JOIN w_categories ON w_categories.id=w_category_summary.category_id WHERE ';

            $dateQuery = 'SELECT w_category_summary.id as id, totalbet, totalwin, totaldealbet, totaldealwin,total_deal,total_mileage, total_ggr, total_ggr_mileage, category_id, date, if (w_categories.parent>0, w_categories.parent, w_categories.id) AS parent, w_categories.title AS title, w_categories.provider as provider FROM w_category_summary JOIN w_categories ON w_categories.id=w_category_summary.category_id WHERE ';

            $start_date = date("Y-m-1");
            $end_date = date("Y-m-d");

            if ($request->dates != '')
            {
                $start_date = preg_replace('/T/',' ', $request->dates[0]);
                $end_date = preg_replace('/T/',' ', $request->dates[1]);            
            }
            $statistics = $statistics->where('category_summary.date', '>=', $start_date);
            $statistics = $statistics->where('category_summary.date', '<=', $end_date);
            $totalQuery = $totalQuery . "w_category_summary.date>=\"$start_date\" AND w_category_summary.date<=\"$end_date\" ";
            $dateQuery = $dateQuery . "w_category_summary.date>=\"$start_date\" AND w_category_summary.date<=\"$end_date\" ";

            if ($request->partner != '')
            {
                $availablePartners = auth()->user()->hierarchyPartners();
                if ($request->role != '')
                {
                    $user = \App\Models\User::where('username', $request->partner)->whereIn('id', $availablePartners)->where('role_id',$request->role)->first();
                }
                else
                {
                    $user = \App\Models\User::where('username', $request->partner )->whereIn('id', $availablePartners)->first();
                }
                if (!$user || !in_array($user->id, $availablePartners))
                {
                    return redirect()->back()->withErrors(['에이전트를 찾을수 없습니다']);
                }
            }
            else
            {
                $user = auth()->user();
            }

            

            $statistics = $statistics->where('user_id', $user->id);
            $totalQuery = $totalQuery . "AND w_category_summary.user_id=$user->id ";
            $dateQuery = $dateQuery . "AND w_category_summary.user_id=$user->id ";

            if ($request->category_id != '')
            {
                // $category = \App\Models\Category::where('title', $request->game);
                // if (!auth()->user()->hasRole('admin'))
                // {
                //     $category = $category->where('parent', 0);
                // }
                // $category = $category->first();
                // if (!$category)
                // {
                //     return redirect()->back()->withErrors(['게임사를 찾을수 없습니다']);
                // }
                $statistics = $statistics->where('category_id', $request->category_id);
                $totalQuery = $totalQuery . "AND w_category_summary.category_id=$request->category_id ";
                $dateQuery = $dateQuery . "AND w_category_summary.category_id=$request->category_id ";
            }

            if ($request->gametype != '')
            {
                $category = \App\Models\Category::where('type', $request->gametype);
                // if (!auth()->user()->hasRole('admin'))
                // {
                //     $category = $category->where('parent', 0);
                // }
                $category = $category->pluck('original_id')->toArray();
                $uniqueCat = array_unique($category);
                if (count($uniqueCat) == 0)
                {
                    return redirect()->back()->withErrors(['게임사를 찾을수 없습니다']);
                }
                $statistics = $statistics->whereIn('category_id', $uniqueCat);
                $totalQuery = $totalQuery . "AND w_category_summary.category_id in (". implode(',',$uniqueCat). ") ";
                $dateQuery = $dateQuery . "AND w_category_summary.category_id in (". implode(',',$uniqueCat). ") ";
            }
            
            $totalQuery = $totalQuery . "GROUP BY w_category_summary.category_id ORDER BY totalbet desc";
            $dateQuery = $dateQuery . "ORDER BY w_category_summary.date desc";

            if (!auth()->user()->hasRole('admin'))
            {
                $totalQuery = "SELECT SUM(a.totalbet) AS totalbet, SUM(a.totalwin) AS totalwin, SUM(a.totaldealbet) AS totaldealbet, SUM(a.totaldealwin) AS totaldealwin,SUM(a.total_deal) as total_deal,SUM(a.total_mileage) as total_mileage, SUM(a.total_ggr) as total_ggr, SUM(a.total_ggr_mileage) as total_ggr_mileage, a.parent AS category_id, b.title, b.provider as provider, b.type  FROM ($totalQuery) a JOIN w_categories as b on b.id=a.parent GROUP BY a.parent ORDER BY totalbet desc";

                $dateQuery = "SELECT a.id as id, SUM(a.totalbet) AS totalbet, SUM(a.totalwin) AS totalwin, SUM(a.totaldealbet) AS totaldealbet, SUM(a.totaldealwin) AS totaldealwin, SUM(a.total_deal) as total_deal,SUM(a.total_mileage) as total_mileage, SUM(a.total_ggr) as total_ggr,SUM(a.total_ggr_mileage) as total_ggr_mileage, date, a.parent AS category_id, b.title, b.provider as provider FROM ($dateQuery) a JOIN w_categories as b on b.id=a.parent GROUP BY a.parent, a.date ORDER BY a.date desc";
            }
        
            $sumQuery = "SELECT SUM(c.totalbet) AS totalbet, SUM(c.totalwin) AS totalwin, SUM(c.totaldealbet) AS totaldealbet, SUM(c.totaldealwin) AS totaldealwin,  SUM(c.total_deal) as total_deal, SUM(c.total_mileage) as total_mileage ,SUM(c.total_ggr) as total_ggr, SUM(c.total_ggr_mileage) as total_ggr_mileage FROM ($totalQuery) c";

            $totalstatics = \DB::select($totalQuery);
            $totalsummary = \DB::select($sumQuery);
            $statistics = \DB::select($dateQuery);

            $categories = [];
            if ($statistics)
            {
                $last_date = null;
                $date_cat = null;
                foreach ($statistics as $cat)
                {
                    if ($cat->date != $last_date)
                    {
                        if ($date_cat)
                        {
                            //sort games per totalbet
                            usort($date_cat['cat'], function($element1, $element2)
                            {
                                return $element2['totalbet'] - $element1['totalbet'];
                            });
                            $categories[] = $date_cat;
                        }
                        $last_date = $cat->date;
                        $date_cat = [
                            'date' => $cat->date,
                        ];
                    }
                    $info['id'] = $cat->id;
                    $info['totalbet'] = $cat->totalbet;
                    $info['totalwin'] = $cat->totalwin;
                    $info['totaldealbet'] = $cat->totaldealbet;
                    $info['totaldealwin'] = $cat->totaldealwin;
                    $info['total_deal'] = $cat->total_deal;
                    $info['total_mileage'] = $cat->total_mileage;
                    $info['total_ggr'] = $cat->total_ggr;
                    $info['total_ggr_mileage'] = $cat->total_ggr_mileage;
                    $info['title'] = $cat->title;
                    $info['category_id'] = $cat->category_id;
                    $info['provider'] = $cat->provider;
                    $date_cat['cat'][] = $info;
                }
                if ($date_cat)
                {
                    //sort games per totalbet
                    usort($date_cat['cat'], function($element1, $element2)
                    {
                        return $element2['totalbet'] - $element1['totalbet'];
                    });
                    $categories[] = $date_cat;
                }
            }

            //merge evolution&gac per config
            $master = auth()->user();
            while ($master !=null && !$master->isInoutPartner())
            {
                $master = $master->referral;
            }
            $gacmerge = \App\Http\Controllers\GameProviders\GACController::mergeGAC_EVO($master->id);
            
            $totalbyType = [
                'live' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                    'totaldealbet' => 0,
                    'totaldealwin' => 0,
                    'total_deal' => 0,
                    'total_mileage' => 0,
                    'total_ggr' => 0,
                    'total_ggr_mileage' => 0,
                ],
                'slot' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                    'totaldealbet' => 0,
                    'totaldealwin' => 0,
                    'total_deal' => 0,
                    'total_mileage' => 0,
                    'total_ggr' => 0,
                    'total_ggr_mileage' => 0,
                ],                
                'sports' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                    'totaldealbet' => 0,
                    'totaldealwin' => 0,
                    'total_deal' => 0,
                    'total_mileage' => 0,
                    'total_ggr' => 0,
                    'total_ggr_mileage' => 0,
                ],
                'pball' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                    'totaldealbet' => 0,
                    'totaldealwin' => 0,
                    'total_deal' => 0,
                    'total_mileage' => 0,
                    'total_ggr' => 0,
                    'total_ggr_mileage' => 0,
                ]
            ];

            foreach ($totalstatics as $total)
            {
                if (isset($totalbyType[$total->type]))
                {
                    foreach ($totalbyType[$total->type] as $key => $value)
                    {
                        $totalbyType[$total->type][$key] = $totalbyType[$total->type][$key] + $total->{$key};
                    }
                }
            }
            $game_cats = null;
            $website = \App\Models\WebSite::where('adminid', $master->id)->first();
            if ($website)
            {
                $game_cats = $website->categories->where('parent', 0)->where('view', 1);
            }
            else
            {
                $game_cats = \App\Models\Category::where(['site_id' => 0, 'shop_id' => 0,'view' => 1])->orderby('position', 'desc')->get();
            }
            return view('report.game', compact('totalsummary', 'categories', 'totalbyType', 'totalstatics','user','gacmerge', 'game_cats'));
        }

        public function report_game_details(\Illuminate\Http\Request $request)
        {
            $category_id = $request->cat_id;
            $user_id = $request->user_id;
            $user = \App\Models\User::where('id', $user_id)->first();
            $availablePartners = auth()->user()->hierarchyPartners();
            $availablePartners[] = auth()->user()->id;
            if (!$user || !in_array($user_id, $availablePartners))
            {
                return redirect()->back()->withErrors(['파트너를 찾을수 없습니다']);
            }

            $statistics = \App\Models\GameSummary::orderBy('game_summary.date', 'DESC')->where('category_id', $category_id);
        
            $totalQuery = "SELECT SUM(totalbet) AS totalbet, SUM(totalwin) AS totalwin, SUM(total_deal-total_mileage) as totaldeal, name as title FROM w_game_summary WHERE w_game_summary.category_id=$category_id";

            $start_date = date("Y-m-1");
            $end_date = date("Y-m-d");

            if ($request->dates != '')
            {
                $start_date = preg_replace('/T/',' ', $request->dates[0]);
                $end_date = preg_replace('/T/',' ', $request->dates[1]);            
            }
            $statistics = $statistics->where('game_summary.date', '>=', $start_date);
            $statistics = $statistics->where('game_summary.date', '<=', $end_date);
            $totalQuery = $totalQuery . " AND w_game_summary.date>=\"$start_date\" AND w_game_summary.date<=\"$end_date\" ";

            $statistics = $statistics->where('user_id', $user->id);
            $totalQuery = $totalQuery . " AND w_game_summary.user_id=$user->id ";

            if ($request->game != '')
            {
                $statistics = $statistics->where('name', 'like', '%'. $request->game .'%');
                $totalQuery = $totalQuery . "AND w_game_summary.name like '%$request->game%' ";
            }
            
            $totalQuery = $totalQuery . "GROUP BY w_game_summary.game_id ORDER BY totalbet desc";
            $statistics = $statistics->orderBy('totalbet', 'desc')->paginate(100);
        
            $sumQuery = "SELECT SUM(c.totalbet) AS totalbet, SUM(c.totalwin) AS totalwin, SUM(c.totaldeal) as totaldeal FROM ($totalQuery) c";

            $totalstatics = \DB::select($totalQuery);
            $totalsummary = \DB::select($sumQuery);

            $categories = [];
            if ($statistics)
            {
                $last_date = null;
                $date_cat = null;
                foreach ($statistics as $cat)
                {
                    if ($cat->date != $last_date)
                    {
                        if ($date_cat)
                        {
                            //sort games per totalbet
                            usort($date_cat['cat'], function($element1, $element2)
                            {
                                return $element2['totalbet'] - $element1['totalbet'];
                            });
                            $categories[] = $date_cat;
                        }
                        $last_date = $cat->date;
                        $date_cat = [
                            'date' => $cat->date,
                        ];
                    }
                    $info['totalbet'] = $cat->totalbet;
                    $info['totalwin'] = $cat->totalwin;
                    $info['totaldeal'] = $cat->total_deal - $cat->total_mileage;
                    $info['title'] = $cat->name;
                    $date_cat['cat'][] = $info;
                }
                if ($date_cat)
                {
                    //sort games per totalbet
                    usort($date_cat['cat'], function($element1, $element2)
                    {
                        return $element2['totalbet'] - $element1['totalbet'];
                    });
                    $categories[] = $date_cat;
                }
            }

            return view('report.game_details', compact('totalsummary', 'categories', 'totalstatics','statistics','user'));
        }
        public function report_user(\Illuminate\Http\Request $request)
        {
            if (auth()->user()->role_id < 3)
            {
                return redirect()->back()->withErrors([trans('No Permission')]);
            }
            $users = [auth()->user()->id];
            $user = null;
            if ($request->user != '')
            {
                $availableUsers = auth()->user()->availableUsers();
                $subUsers = \App\Models\User::where('username', 'like', '%' . $request->user . '%')->whereIn('id', $availableUsers)->pluck('id')->toArray();
                if (count($subUsers) == 0)
                {
                    return redirect()->back()->withErrors(trans('Could not find user'));
                }
                $users = $subUsers;
            }else{
                $users = auth()->user()->availableUsers();
            }
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d");
            if ($request->dates != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->dates[0]);
                $end_date = preg_replace('/T/',' ', $request->dates[1]);            
            }
            // $statistics = \App\Models\UserDailySummary::where('date', '>=', $start_date)->where('date', '<=', $end_date);
            $fieldtype = 'totalbet';
            if($request->fieldtype != ''){
                if($request->fieldtype == 'bet'){
                    $fieldtype = 'totalbet';
                }else if($request->fieldtype == 'win'){
                    $fieldtype = 'totalwin';
                }else{
                    $fieldtype = 'totalbetwin';
                }
            }
            $fieldsort = 'desc';
            if($request->fieldsort != ''){
                $fieldsort = $request->fieldsort;
            }

            $totalstatics = \App\Models\UserDailySummary::where('date', '>=', $start_date)->whereIn('user_id', $users)->where('date', '<=', $end_date)->selectRaw('user_id, SUM(totalbet) as totalbet, SUM(totalwin) as totalwin, SUM(totalbet-totalwin) as totalbetwin')->groupBy('user_id')->orderBy(\DB::raw($fieldtype), $fieldsort)->paginate(50);
            $daterange = $start_date . '~' . $end_date;
            return view('report.user_daily', compact('totalstatics', 'daterange'));
        }
        public function report_user_details(\Illuminate\Http\Request $request)
        {
            $user_id = $request->user_id;
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d");
            if ($request->dates != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->dates[0]);
                $end_date = preg_replace('/T/',' ', $request->dates[1]);            
            }
            $statistics = \App\Models\UserDailySummary::where('user_id', $user_id)->where('date', '>=', $start_date)->where('date', '<=', $end_date)->orderBy('date', 'desc')->get();
            $dailyinfos = [];
            $totalsummary = [
                'totalbet' => 0,
                'totalwin' => 0,
            ];
            if ($statistics)
            {
                $last_date = null;
                $date_info = null;
                foreach ($statistics as $daily)
                {
                    if ($daily->date != $last_date)
                    {
                        if ($date_info)
                        {
                            $dailyinfos[] = $date_info;
                        }
                        $last_date = $daily->date;
                        $date_info = [
                            'date' => $daily->date,
                            'user' => $daily->user,
                            'tablebet' => 0,
                            'tablewin' => 0,
                            'slotbet' => 0,
                            'slotwin' => 0,
                            'pballbet' => 0,
                            'pballwin' => 0,
                            'allbet' => 0,
                            'allwin' => 0,
                        ];
                    }
                    if($daily->gametype == 'table'){
                        $date_info['tablebet'] += $daily->totalbet;
                        $date_info['tablewin'] += $daily->totalwin;
                    }else if($daily->gametype == 'slot'){
                        $date_info['slotbet'] += $daily->totalbet;
                        $date_info['slotwin'] += $daily->totalwin;
                    }else if($daily->gametype == 'pball'){
                        $date_info['pballbet'] += $daily->totalbet;
                        $date_info['pballwin'] += $daily->totalwin;
                    }
                    $date_info['allbet'] += $daily->totalbet;
                    $date_info['allwin'] += $daily->totalwin;
                    $totalsummary['totalbet'] += $daily->totalbet;
                    $totalsummary['totalwin'] += $daily->totalwin;
                }
                if ($date_info)
                {
                    $dailyinfos[] = $date_info;
                }
            }
            return view('backend.argon.report.user_details', compact('dailyinfos', 'totalsummary'));
        }
    }
}
