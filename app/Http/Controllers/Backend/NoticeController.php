<?php
namespace App\Http\Controllers\Backend
{
    class NoticeController extends \App\Http\Controllers\Controller
    {
        public function __construct()
        {
            $this->middleware('auth');
            $this->middleware( function($request,$next) {
                    if (!auth()->user()->isInoutPartner())
                    {
                        return response('허용되지 않은 접근입니다.', 401);
                    }
                    return $next($request);
                }
            );
        }

        public function index(\Illuminate\Http\Request $request)
        {
            
            if (auth()->user()->hasRole('admin'))
            {
                //$notices = \App\Models\Notice::all();
                $notices = \App\Models\Notice::where('id','>',0);
            }
            else 
            {
                $notices = \App\Models\Notice::where('user_id', auth()->user()->id);
                
            }
            
            $searchName = 'id';
            $searchValue = '';
            if($request->search != ''){
                $searchValue = $request->search;
            }
            if($request->status != ''){
                if($request->status == 1){
                    $searchName = 'title';
                }else if($request->status == 2){
                    $searchName = 'user_id';
                }
            }
            $userid = [];
            if($searchName != '' && $searchValue != ''){
                if($searchName == 'title'){
                    $notices = $notices->where($searchName, 'like', '%' . $searchValue . '%');
                }else{
                    $userid = \App\Models\User::where('username','like','%' . $searchValue . '%')->pluck('id');
                    if(!$userid){
                        return redirect()->back()->withErrors(['작성자를 찾을수 없습니다']);    
                    }
                    
                    $notices = $notices->whereIn($searchName,  $userid);
                }                
            }
            if ($request->join != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->join[0]);
                $end_date = preg_replace('/T/',' ', $request->join[1]);
                $notices = $notices->where('date_time', '>', $start_date)->where('date_time', '<=', date($end_date, strtotime('+1days')));
                if(!$notices){
                    return redirect()->back()->withErrors(['공지를 찾을수 없습니다']);    
                }
            }

            $notices = $notices->get();
            
            return view('notices.list', compact('notices'));
        }

        public function store(\Illuminate\Http\Request $request)
        {
            $data = $request->only([
                'title',
                'content',
                'popup',
                'order',
                'type',
                'active',
            ]);
            if ($request->user != '')
            {
                $writer = \App\Models\User::where('username', $request->user)->first();
                if (!$writer)
                {
                    return redirect()->back()->withErrors('작성자를 찾을수 없습니다.');
                }
                $data['user_id'] = $writer->id;
            }
            else
            {
                $data['user_id'] = auth()->user()->id;
            }
            $orderid = (int)$data['order'];
            $data['order'] = $orderid;
            \App\Models\Notice::create($data);
            return redirect()->back()->withSuccess(['공지가 추가되었습니다']);
        }
        public function massadd(\Illuminate\Http\Request $request)
        {
            $shop = \App\Models\Shop::find(\Auth::user()->shop_id);
            if( !$shop ) 
            {
                return redirect()->back()->withErrors([trans('app.wrong_shop')]);
            }
            if( !$request->nominal || $request->nominal <= 0 ) 
            {
                return redirect()->back()->withErrors([trans('app.wrong_sum')]);
            }
            if( !auth()->user()->hasRole('cashier') ) 
            {
                return redirect()->back()->withErrors([trans('app.only_for_cashiers')]);
            }
            $open_shift = \App\Models\OpenShift::where([
                'shop_id' => \Auth::user()->shop_id, 
                'end_date' => null
            ])->first();
            if( !$open_shift ) 
            {
                return redirect()->back()->withErrors([trans('app.shift_not_opened')]);
            }
            if( $shop->balance < ($request->nominal * $request->count) ) 
            {
                return redirect()->back()->withErrors([trans('app.not_enough_money_in_the_shop', [
                    'name' => $shop->name, 
                    'balance' => $shop->balance
                ])]);
            }
            if( isset($request->count) && is_numeric($request->count) && isset($request->nominal) && is_numeric($request->nominal) ) 
            {
                $shop->update(['balance' => $shop->balance - ($request->nominal * $request->count)]);
                $open_shift->increment('balance_out', $request->nominal * $request->count);
                $open_shift->increment('money_in', $request->nominal * $request->count);
                for( $i = 0; $i < $request->count; $i++ ) 
                {
                    $pincode = '';
                    $nonUniq = true;
                    while( $nonUniq ) 
                    {
                        $str = md5(rand(100000, 999999));
                        $pincode = mb_strtoupper(implode('-', [
                            substr($str, 0, 4), 
                            substr($str, 4, 4), 
                            substr($str, 8, 4), 
                            substr($str, 12, 4), 
                            substr($str, 16, 4)
                        ]));
                        $nonUniq = \App\Models\Pincode::where('code', $pincode)->count();
                    }
                    $data = [
                        'code' => $pincode, 
                        'nominal' => $request->nominal, 
                        'status' => $request->status, 
                        'shop_id' => auth()->user()->shop_id
                    ];
                    \App\Models\Pincode::create($data);
                }
            }
            return redirect()->back()->withSuccess(trans('app.pincode_created'));
        }
        public function edit(\Illuminate\Http\Request $request)
        {
            $id = $request->id;
            $edit = $request->edit;   
            $notice = [];
            if($id != ''){
                $notice = \App\Models\Notice::where([
                    'id' => $id])->firstOrFail();
            }         
            
            return view('modal.notices', compact('notice','edit'));
        }
        public function update(\Illuminate\Http\Request $request, \App\Models\Notice $notice)
        {
            $data = $request->only([
                'title',
                'content',
                'type',
                'order',
                'popup',
                'active',
            ]);
            $id= $request->id;
            if ($request->user != '')
            {
                $writer = \App\Models\User::where('username', $request->user)->first();
                if (!$writer)
                {
                    return redirect()->back()->withErrors('작성자를 찾을수 없습니다.');
                }
                $data['user_id'] = $writer->id;
            }
            else
            {
                $data['user_id'] = auth()->user()->id;
            }

            \App\Models\Notice::where('id', $id)->update($data);
            return redirect()->back()->withSuccess(['공지가 업데이트되었습니다.']);
        }
        public function delete(\Illuminate\Http\Request $request)
        {
            $notice = $request->ids;
            \App\Models\Notice::whereIn('id', $notice)->delete();
            return response()->json(['error'=>false]);
        }

        public function status_update(\Illuminate\Http\Request $request)
        {
            $notices = $request->ids;
            $data = [
                'active' => $request->active,
            ];
            \App\Models\Notice::whereIn('id', $notices)->update($data);
            
            return response()->json(['error'=>false, 'msg'=> '']);
        }
    }
}