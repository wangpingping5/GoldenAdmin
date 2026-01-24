<?php 
namespace App\Http\Controllers\Backend
{
    class DWController extends \App\Http\Controllers\Controller
    {
        public function __construct(\App\Repositories\User\UserRepository $users)
        {
            $this->middleware('auth');
            $this->middleware('permission:access.admin.panel');
        }

        public function addrequest(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            $in_out_logs = \App\Models\WithdrawDeposit::where('user_id', $user->id)->where('type', 'add')->orderby('created_at', 'desc')->take(10)->get();
            $master = $user->referral;
            while ($master!=null && !$master->isInoutPartner())
            {
                $master = $master->referral;
            }
            $needRequestAccount = true;
            if (!$master && $master->bank_name == 'MESSAGE')
            {
                $needRequestAccount = false;
            }
            return view('dw.addrequest', compact('in_out_logs', 'needRequestAccount'));
        }

        public function outrequest(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            $in_out_logs = \App\Models\WithdrawDeposit::where('user_id', $user->id)->where('type', 'out')->orderby('created_at', 'desc')->take(10)->get();
            return view('dw.outrequest', compact('in_out_logs'));
        }

        public function dealconvert(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            if(isset($request->user))
            {
                if($user->isInoutPartner())
                {
                    $user = \App\Models\User::where('id', $request->user)->first();
                    if(!isset($user))
                    {
                        return redirect()->back()->withErrors(['유저가 존재하지 않습니다.']);    
                    }
                    if($user->isInoutPartner())
                    {
                        return redirect()->back()->withErrors([trans('No Permission')]);
                    }
                }
                else
                {
                    return redirect()->back()->withErrors([trans('No Permission')]);
                }
            }
            return view('dw.dealconvert', compact('user'));
        }
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
            
            if( $request->partner != '') 
            {
                $availableUsers = auth()->user()->availableUsers();
                $user_ids = \App\Models\User::whereIn('id', $availableUsers)->where('username','like', '%' . $request->partner . '%');
                if($request->role != ''){
                    $user_ids = $user_ids->where('role_id', $request->role);
                }
                $user_ids = $user_ids->pluck('id')->toArray();
                if (count($user_ids) > 0){
                    $in_out_logs = $in_out_logs->whereIn('user_id', $user_ids);
                }
                else
                {
                    $in_out_logs = $in_out_logs->where('user_id', -1);
                }
            }

            // if ($request->partner != '' && $request->role != 1 )
            // {
            //     $availablePartners = auth()->user()->hierarchyPartners();
            //     $partners = \App\Models\User::whereIn('id', $availablePartners)->orderBy('role_id','desc')->where('username','like', '%' . $request->partner . '%');
            //     if ($request->role != '' && $request->role >= 3 )
            //     {
            //         $partners = $partners->where('role_id', $request->role);
            //     }
            //     $partners = $partners->first();
            //     if ($partners) {
            //         $childsPartners = $partners->hierarchyPartners();
            //         $childsPartners[] = $partners->id;
            //         $in_out_logs = $in_out_logs->whereIn('user_id', $childsPartners);
            //     }
            //     else
            //     {
            //         $in_out_logs = $in_out_logs->where('user_id', -1);
            //     }
            // }
            $total = [
                'add' => (clone $in_out_logs)->where(['type'=>'add', 'status'=>\App\Models\WithdrawDeposit::DONE])->sum('sum'),
                'out' => (clone $in_out_logs)->where(['type'=>'out', 'status'=>\App\Models\WithdrawDeposit::DONE])->sum('sum'),
            ];

            $in_out_logs = $in_out_logs->paginate(20);

            return view('dw.history', compact('in_out_logs','total'));
        }
        public function process(\Illuminate\Http\Request $request)
        {
            $requestid = $request->id;
            $in_out = \App\Models\WithdrawDeposit::where('id', $requestid)->where('payeer_id', auth()->user()->id)->whereIn('status', [\App\Models\WithdrawDeposit::REQUEST, \App\Models\WithdrawDeposit::WAIT ])->first();
            if (!$in_out)
            {
                return redirect()->back()->withErrors([trans('No Permission')]);
            }
            return view('backend.argon.dw.dw', compact('in_out'));
        }

        public function rejectDW(\Illuminate\Http\Request $request){
            
            $in_out_id = $request->id;
            $transaction = \App\Models\WithdrawDeposit::where('id', $in_out_id)->first();
            if($transaction == null){
                return redirect()->back()->withErrors(['유효하지 않은 조작입니다.']);
            }
            if ($transaction->status!=\App\Models\WithdrawDeposit::REQUEST && $transaction->status!=\App\Models\WithdrawDeposit::WAIT )
            {
                return redirect()->back()->withErrors(['이미 처리된 신청내역입니다.']);
            }
            
            $amount = $transaction->sum;
            $type = $transaction->type;
            $requestuser = \App\Models\User::where('id', $transaction->user_id)->first();
            if ($requestuser){
                if ($requestuser->hasRole('manager')) // for shops
                {
                    $shop = \App\Models\Shop::where('id', $transaction->shop_id)->first();
                    if($type == 'out'){
                        $shop->update([
                            'balance' => $shop->balance + $amount
                        ]);
                        $open_shift = \App\Models\OpenShift::where([
                            'shop_id' => $shop->id, 
                            'end_date' => null,
                            'type' => 'shop'
                        ])->first();
                        if( $open_shift ) 
                        {
                            $open_shift->decrement('balance_out', $amount);
                        }
                    }
                    else if($type == 'deal_out'){
                        $shop->update([
                            'deal_balance' => $shop->deal_balance + $amount
                        ]);
                        $open_shift = \App\Models\OpenShift::where([
                            'shop_id' => $shop->id, 
                            'end_date' => null,
                            'type' => 'shop'
                        ])->first();
                        if( $open_shift ) 
                        {
                            $open_shift->decrement('balance_out', $amount);
                        }
                    }

                }
                else
                {
                    if($type == 'out'){
                        if ($requestuser->hasRole('user') )
                        {
                            $b = $requestuser->withdrawAll('rejectDW');
                            if (!$b)
                            {
                                return redirect()->back()->withErrors([trans('OccurErrorInProvierWithdraing')]);
                            }
                        }
                        $requestuser->update([
                            'balance' => $requestuser->balance + $amount,
                            'total_out' => $requestuser->total_out - $amount,
                        ]);
                        $open_shift = \App\Models\OpenShift::where([
                            'user_id' => $requestuser->id, 
                            'end_date' => null,
                            'type' => 'partner'
                        ])->first();
                        if( $open_shift ) 
                        {
                            $open_shift->decrement('balance_out', $amount);
                        }
                    }

                }
            }
            $transaction->update([
                'status' => 2
            ]);
           return redirect()->back()->withSuccess(['조작이 성공적으로 진행되었습니다.']);
        }
        public function processDW(\Illuminate\Http\Request $request){
            $in_out_id = $request->id;
            $transaction = \App\Models\WithdrawDeposit::where('id', $in_out_id)->where('payeer_id', auth()->user()->id)->first();
            if (!$transaction)
            {
                return response()->json(['error'=>true, 'code'=>0, 'msg'=> trans('No Permission')]);
            }
            $amount = $transaction->sum;
            $type = $transaction->type;
            $requestuser = \App\Models\User::where('id', $transaction->user_id)->first();
            $user = auth()->user();

            if (!$user)
            {
                return response()->json(['error'=>true, 'code'=>0, 'msg'=> '본사를 찾을수 없습니다.']);
            }
            if (!$requestuser)
            {
                return response()->json(['error'=>true, 'code'=>0, 'msg'=> trans('Could not find user')]);
            }
            if ($transaction->status!=\App\Models\WithdrawDeposit::REQUEST && $transaction->status!=\App\Models\WithdrawDeposit::WAIT )
            {
                return response()->json(['error'=>true, 'code'=>0, 'msg'=> '이미 처리된 신청내역입니다.']);
            }

            if ($requestuser->hasRole('user') )
            {
                $b = $requestuser->withdrawAll('processDW');
                if (!$b)
                {
                    return response()->json(['error'=>true, 'code'=>0, 'msg'=> trans('OccurErrorInProvierWithdraing')]);
                }
            }

            // if ($requestuser->hasRole('user') && $requestuser->playing_game != null)
            // {
            //     return redirect()->back()->withErrors(['해당 유저가 게임중이므로 충환전처리를 할수 없습니다.']);
            // }
            if ($requestuser->hasRole('manager')) // for shops
            {
                $shop = \App\Models\Shop::lockforUpdate()->where('id', $transaction->shop_id)->get()->first();
                if($type == 'add'){
                    if($user->balance < $amount) {
                        if (auth()->user()->hasRole('comaster'))
                        {
                            return response()->json(['error'=>true, 'code'=>0, 'msg'=> '본사의 보유금액이 충분하지 않습니다.']);
                        }
                        else
                        {
                            return response()->json(['error'=>true, 'code'=>0, 'msg'=> '보유금액이 충분하지 않습니다.']);
                        }
                    }

                    $user->update(
                        ['balance' => $user->balance - $amount]
                    );
                    $old = $shop->balance;
                    $shop->update([
                        'balance' => $shop->balance + $amount
                    ]);

                    $open_shift = \App\Models\OpenShift::where([
                        'user_id' => $user->id, 
                        'end_date' => null,
                        'type' => 'partner'
                    ])->first();
                    if( $open_shift ) 
                    {
                        $open_shift->increment('money_in', $amount);
                    }
                    $open_shift = \App\Models\OpenShift::where([
                        'shop_id' => $shop->id, 
                        'end_date' => null,
                        'type' => 'shop'
                    ])->first();
                    if( $open_shift ) 
                    {
                        $open_shift->increment('balance_in', $amount);
                    }

                }
                else if($type == 'out'){
                    $user->update(
                        ['balance' => $user->balance + $amount]
                    );
                    $open_shift = \App\Models\OpenShift::where([
                        'user_id' => $user->id, 
                        'end_date' => null,
                        'type' => 'partner'
                    ])->first();
                    if( $open_shift ) 
                    {
                        $open_shift->increment('money_out', $amount);
                    }
                    $old = $shop->balance + $amount;

                }
                else if($type == 'deal_out'){
                    //out balance from master
                    $user->update(
                        ['balance' => $user->balance - $amount]
                    ); 
                    $open_shift = \App\Models\OpenShift::where([
                        'user_id' => $user->id, 
                        'end_date' => null,
                        'type' => 'partner'
                    ])->first();
                    if( $open_shift ) 
                    {
                        $open_shift->increment('convert_deal', $amount);
                    }
                    $old = $shop->balance;
                }

                $transaction->update([
                    'status' => 1
                ]);
                $shop = $shop->fresh();
                $user = $user->fresh();

                \App\Models\ShopStat::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'sum' => $amount,
                    'old' => $old,
                    'new' => $shop->balance,
                    'balance' => $user->balance,
                    'request_id' => $transaction->id,
                    'shop_id' => $transaction->shop_id,
                    'date_time' => \Carbon\Carbon::now()
                ]);

                //create another transaction for mananger account
                \App\Models\Transaction::create([
                    'user_id' => $requestuser->id, 
                    'payeer_id' => $user->id, 
                    'type' => $type, 
                    'summ' => abs($amount), 
                    'old' => $old,
                    'new' => $shop->balance,
                    'balance' => $user->balance,
                    'request_id' => $transaction->id,
                    'shop_id' => $transaction->shop_id,
                ]);
            }
            else // for partners
            {
                if($type == 'add'){
                    if($user->bank_name == 'OSSCOIN') //OSS코인 가상계좌만 가능
                    {
                        if ($amount % 10000 != 0)
                        {
                            return response()->json(['error'=>true, 'code'=>0, 'msg'=> '만원단위만 가능합니다.']);
                        }
                        if($user->balance < $amount ) 
                        {
                            return response()->json(['error'=>true, 'code'=>0, 'msg'=> '사용자 잔액이 부족합니다.']);
                            // return redirect()->back()->withErrors(trans('app.not_enough_money_in_the_user_balance', [
                            //     'name' => $user->name, 
                            //     'balance' => $user->balance
                            // ]));
                        }
                        $data = [
                            'agent' => $user->recommender,
                            'userAccount' => $requestuser->username
                        ];
                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'PublicKey' => $user->account_no
                            ])->post(config('app.osscoin_url') . '/api_coin', $data);
                        if (!$response->ok())
                        {
                            return response()->json(['error'=>true, 'code'=>0, 'msg'=> 'OSS코인조회가 실패하였습니다. 다시 시도해주세요.']);
                        }
                        $data = $response->json();
                        if($data['code'] == 0)
                        {
                            $data = [
                                'agent' => $user->recommender,
                                'userAccount' => $requestuser->username,
                                'coinAmount' => '' . floor($amount / 10000)
                            ];
                            $response = \Illuminate\Support\Facades\Http::withHeaders([
                                'PublicKey' => $user->account_no
                                ])->post(config('app.osscoin_url') . '/api_charge', $data);
                            if (!$response->ok())
                            {
                                return response()->json(['error'=>true, 'code'=>0, 'msg'=> 'OSS코인전환이 실패하였습니다. 다시 시도해주세요.']);
                            }
                            $data = $response->json();
                            if($data['code'] != 0){
                                return redirect()->back()->withErrors($data['result']);    
                            }
                        }
                        else
                        {
                            return redirect()->back()->withErrors($data['result']);
                        }
                    }
                    $result = $requestuser->addBalance('add', $amount, $user, 0, $transaction->id);
                    $result = json_decode($result, true);
                    if ($result['status'] == 'error')
                    {
                        return response()->json(['error'=>true, 'code'=>0, 'msg'=> $result['message']]);
                    }
                }
                else if($type == 'out'){
                    $user->update(
                        ['balance' => $user->balance + $amount]
                    );
                    $open_shift = \App\Models\OpenShift::where([
                        'user_id' => $user->id, 
                        'end_date' => null,
                        'type' => 'partner'
                    ])->first();
                    if( $open_shift ) 
                    {
                        $open_shift->increment('money_out', $amount);
                    }
                    $old = $requestuser->balance + $amount;
                    $user = $user->fresh();
                    \App\Models\Transaction::create([
                        'user_id' => $transaction->user_id,
                        'payeer_id' => $user->id,
                        'system' => $user->username,
                        'type' => $type,
                        'summ' => $amount,
                        'old' => $old,
                        'new' => $requestuser->balance,
                        'balance' => $user->balance,
                        'request_id' => $transaction->id,
                        'shop_id' => $transaction->shop_id,
                        'created_at' => \Carbon\Carbon::now(),
                        'updated_at' => \Carbon\Carbon::now()
                    ]);
                }

                $transaction->update([
                    'status' => 1
                ]);
            }
            return response()->json(['error'=>false, 'code'=>0, 'msg'=> '조작이 성공적으로 진행되었습니다.']);
        }

        public function addmanage(\Illuminate\Http\Request $request)
        {
            $type = 'add';
            if(isset($request->type) && $request->type == 'out'){
                $type = 'out';
            }
            if (auth()->user()->isInoutPartner())
            {
                // $in_out_request = \App\Models\WithdrawDeposit::where([
                //     'payeer_id'=> auth()->user()->id,
                //     'status'=> \App\Models\WithdrawDeposit::REQUEST,
                // ])->where('type', $type);
                // $in_out_wait = \App\Models\WithdrawDeposit::where([
                //     'payeer_id'=> auth()->user()->id,
                //     'status'=> \App\Models\WithdrawDeposit::WAIT,
                // ])->where('type', $type);
                $charges = \App\Models\WithdrawDeposit::where([
                    'payeer_id'=> auth()->user()->id,
                ])->where('type', $type)->orderBy('created_at','desc');


                $start_date = date("Y-m-01 00:00:00");
                $end_date = date("Y-m-d H:i:s",strtotime("1 hours"));
                if ($request->join != '')
                {
                    // $dates = explode(' - ', $request->dates);
                    $start_date = preg_replace('/T/',' ', $request->join[0]);
                    $end_date = preg_replace('/T/',' ', $request->join[1]);            
                }
                $charges = $charges->where('created_at', '>', $start_date)->where('created_at', '<', $end_date);
                if($request->search != '')
                {
                    if($request->kind == '')
                    {
                        $availableUsers = auth()->user()->availableUsers();
                        $user_ids = \App\Models\User::whereIn('id', $availableUsers)->where('username','like', '%' . $request->search . '%')->pluck('id')->toArray();
                        if (count($user_ids) > 0){
                            $charges = $charges->whereIn('user_id', $user_ids);
                        }
                    }
                    else if($request->kind == 1)
                    {
                        if(is_numeric($request->search))
                        {
                            $charges = $charges->where('status', $request->search);
                        }
                        else
                        {
                            return redirect()->back()->withErrors('상태값을 확인해주세요.');
                        }
                        
                    }
                }
            }
            else
            {
                return redirect()->back()->withErrors('접근권한이 없습니다.');
            }
            $total = [
                'add' => (clone $charges)->where('status', \App\Models\WithdrawDeposit::REQUEST)->sum('sum'),
                'out' => (clone $charges)->where('status', \App\Models\WithdrawDeposit::WAIT)->sum('sum'),
            ];
            $charges = $charges->paginate(20);
            return view('dw.addoutmanage', compact('charges','total','type'));
        }
        public function outmanage(\Illuminate\Http\Request $request)
        {
            $type = 'out';
            if (auth()->user()->isInoutPartner())
            {
                $in_out_request = \App\Models\WithdrawDeposit::where([
                    'payeer_id'=> auth()->user()->id,
                    'status'=> \App\Models\WithdrawDeposit::REQUEST,
                ])->where('type', $type);
                $in_out_wait = \App\Models\WithdrawDeposit::where([
                    'payeer_id'=> auth()->user()->id,
                    'status'=> \App\Models\WithdrawDeposit::WAIT,
                ])->where('type', $type);
                $in_out_logs = \App\Models\WithdrawDeposit::where([
                    'payeer_id'=> auth()->user()->id,
                ])->where('type', $type)->whereIn('status', [\App\Models\WithdrawDeposit::DONE, \App\Models\WithdrawDeposit::CANCEL])->orderBy('created_at','desc')->take(20);
            }
            else
            {
                return redirect()->back()->withErrors('접근권한이 없습니다.');
            }
            $total = [
                'add' => $in_out_request->sum('sum'),
                'out' => $in_out_wait->sum('sum'),
            ];
            $in_out_request = $in_out_request->orderBy('created_at', 'desc');
            $in_out_request = $in_out_request->paginate(20);
            $in_out_wait = $in_out_wait->orderBy('created_at', 'desc');
            $in_out_wait = $in_out_wait->paginate(20);
            $in_out_logs = $in_out_logs->get();
            return view('backend.argon.dw.outmanage', compact('in_out_request','in_out_wait','in_out_logs','total','type'));
        }
    }

}
