<?php 
namespace App\Http\Controllers\Backend
{
    class CommonController extends \App\Http\Controllers\Controller
    {
        public function __construct(\App\Repositories\User\UserRepository $users)
        {
            $this->middleware('auth');
            $this->middleware('permission:access.admin.panel');
        }

        public function balance(\Illuminate\Http\Request $request)
        {
            $userid = -1;
            if ($request->id != '')
            {
                $userid = $request->id;
            }
            
            $availableUsers = auth()->user()->availableUsers();
            if (!in_array($userid, $availableUsers))
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            $user = \App\Models\User::find($userid);
            if (!$user)
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            $url = $request->url;
            
            return view('modal.balance',compact( 'user'));
        }

        public function updateBalance(\Illuminate\Http\Request $request)
        {
            \DB::beginTransaction();
            $data = $request->all();
            if( !array_get($data, 'type') ) 
            {
                $data['type'] = 'add';
            }
            $user = \App\Models\User::lockForUpdate()->find($request->user_id);
            if (!$user)
            {
                \DB::commit();
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }

            if (!in_array($user->id, auth()->user()->availableUsers()))
            {
                \DB::commit();
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            if ($user->id == auth()->user()->id)
            {
                \DB::commit();
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            if ($user->playing_game != null)
            {
                $ct = \App\Models\Category::where('href', $user->playing_game)->first();
                if ($ct != null && $ct->provider != null)
                {
                    \DB::commit();
                    return redirect()->back()->withErrors([trans('PressGameTerminalButton')]);
                }
            }
            //대기중의 게임입장큐 삭제
            $launchReque = \App\Models\GameLaunch::where('user_id', $user->id)->first();
            if(isset($launchReque)){
                \DB::commit();
                return redirect()->back()->withErrors([$user->username . trans('WaitInGaming')]);
            }
            $b = $user->withdrawAll('updateBalance');
            if (!$b)
            {
                \DB::commit();
                return redirect()->back()->withErrors([trans('OccurErrorInProvierWithdraing')]);
            }

            $summ = abs(str_replace(',','',$request->amount));
            if ($summ == 0 && $request->all != '1' )
            {
                \DB::commit();
                return redirect()->back()->withErrors([trans('InvalidAmount')]);
            }

            if ($user->hasRole('manager')) // add shop balance
            {

                if($data['type'] == ''){
                    \DB::commit();
                    return redirect()->back()->withErrors([trans('UnauthorizedOperating')]);
                }
                $shop = $user->shop;
                if( $request->all && $request->all == '1' ) 
                {
                    $summ = $shop->balance;
                }
                $payer = auth()->user();
                if( $data['type'] == 'add' && (!$payer->hasRole('admin') && $payer->balance < $summ  ) )
                {
                    \DB::commit();
                    return redirect()->back()->withErrors([trans('app.not_enough_money_in_the_user_balance', [
                        'name' => $user->username, 
                        'balance' => $user->balance
                    ])]);
                }
                if( $data['type'] == 'out' && $shop->balance < $summ  ) 
                {
                    \DB::commit();
                    return redirect()->back()->withErrors([trans('app.not_enough_money_in_the_shop', [
                        'name' => $shop->name, 
                        'balance' => $shop->balance
                    ])]);
                }

                $sum = ($request->type == 'out' ? -1 * $summ  : $summ );

                if (!$payer->hasRole('admin')) {
                    $payer->update([
                        'balance' => $payer->balance - $sum, 
                        'count_balance' => $payer->count_balance - $sum
                    ]);
                    $payer = $payer->fresh();
                }

                $old = $shop->balance;
                $shop->update(['balance' => $shop->balance + $sum]);
                $shop = $shop->fresh();
                \App\Models\ShopStat::create([
                    'user_id' => \Auth::id(), 
                    'shop_id' => $shop->id, 
                    'type' => $request->type, 
                    'sum' => abs($summ),
                    'old' => $old,
                    'new' => $shop->balance,
                    'balance' => $payer->balance,
                    'reason' => $request->reason,
                ]);


                //create another transaction for mananger account
                \App\Models\Transaction::create([
                    'user_id' => $user->id, 
                    'payeer_id' => $payer->id, 
                    'type' => $request->type, 
                    'summ' => abs($summ), 
                    'old' => $old,
                    'new' => $shop->balance,
                    'balance' => $payer->balance,
                    'shop_id' => $shop->id,
                    'reason' => $request->reason
                ]);

                if( $payer->balance == 0 ) 
                {
                    $payer->update([
                        'wager' => 0, 
                        'bonus' => 0
                    ]);
                }
                if( $payer->count_balance < 0 ) 
                {
                    $payer->update(['count_balance' => 0]);
                }
            }
            else
            {
                if( $request->all && $request->all == '1' ) 
                {
                    $summ = $user->balance;
                }

                $result = $user->addBalance($data['type'], abs($summ), false, 0, null, isset($data['reason'])?$data['reason']:null);
            
                $result = json_decode($result, true);
                if( $result['status'] == 'error' ) 
                {
                    \DB::commit();
                    return redirect()->back()->withErrors([$result['message']]);
                }
            }
            \DB::commit();
            // return redirect($request->url)->withSuccess(['조작이 성공했습니다.']);
            return redirect()->back()->withSuccess(['Success']);
        }

        public function profile(\Illuminate\Http\Request $request, \App\Repositories\Activity\ActivityRepository $activities)
        {
            $userid = auth()->user()->id;
            if ($request->id != '')
            {
                $userid = $request->id;
            }
            
            $availableUsers = auth()->user()->availableUsers();
            if (!in_array($userid, $availableUsers))
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            if ($user->status == \App\Support\Enum\UserStatus::DELETED)
            {
                return redirect()->to(argon_route('dashboard'))->withErrors([trans('Deleted') . trans('Players')]);
            }
            $userActivities = $activities->getLatestActivitiesForUser($user->id, 10);
            $rtppercent = 0;
            if ($user->isInOutPartner())
            {
                $shopIds = $user->availableShops();
                if (count($shopIds) > 0)
                {
                    $rtppercent = \App\Models\Shop::whereIn('id', $shopIds)->avg('percent');
                }
            }
            $parent = auth()->user();
            while ($parent && !$parent->isInOutPartner())
            {
                $parent = $parent->referral;
            }
            $deluser = 0; //default 0, disable deluser
            if (auth()->user()->isInOutPartner())
            {
                $deluser = 1;
            }
            else if (isset($parent->sessiondata()['deluser']))
            {
                $deluser = $parent->sessiondata()['deluser'];
            }
            if ($user->isInOutPartner())
            {
                $transactions = \App\Models\Transaction::where('payeer_id', $userid)->orderBy('created_at', 'DESC')->take(10)->get();
            }else{
                $transactions = \App\Models\Transaction::where('user_id', $userid)->orderBy('created_at', 'DESC')->take(10)->get();
            }
            $totalstatic = \App\Models\UserDailySummary::where('date', '>=', date('Y-m-d 0:0:0', strtotime("-30 days")))->where('user_id', $userid)->selectRaw('user_id, SUM(totalbet) as totalbet, SUM(totalwin) as totalwin, SUM(totalbet-totalwin) as totalbetwin')->first();
            return view('common.profile', compact('user', 'userActivities', 'rtppercent', 'deluser', 'transactions', 'totalstatic'));
        }
        public function updateAccessrule(\Illuminate\Http\Request $request)
        {
            $user_id = $request->id;
            $user = \App\Models\User::find($user_id);
            $users = auth()->user()->hierarchyPartners();
            if( !$user || (count($users) && !in_array($user_id, $users) )) 
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            $data = $request->all();
            if ($user->accessrule)
            {
                if ($data['ip_address'] == '' && isset($data['allow_ipv6']) && $data['allow_ipv6']=='on' && $request->check_cloudflare == '')
                {
                    $user->accessrule->delete();
                }
                $user->accessrule->update([
                    'ip_address' => $data['ip_address'],
                    'allow_ipv6' => (isset($data['allow_ipv6']) && $data['allow_ipv6']=='on')?1:0,
                    'check_cloudflare' => (isset($data['check_cloudflare']) && $data['check_cloudflare']=='on')?1:0,
                ]);
            }
            else
            {
                if ($data['ip_address'] == '' && isset($data['allow_ipv6']) && $data['allow_ipv6']=='on' && $request->check_cloudflare == '')
                {
                }
                else
                {
                    \App\Models\AccessRule::create([
                        'user_id' => $user->id,
                        'ip_address' => $data['ip_address'],
                        'allow_ipv6' => (isset($data['allow_ipv6']) && $data['allow_ipv6']=='on')?1:0,
                        'check_cloudflare' => (isset($data['check_cloudflare']) && $data['check_cloudflare']=='on')?1:0,
                    ]);
                }
            }
            event(new \App\Events\User\UpdatedByAdmin($user, 'ipaddress'));
            return redirect()->back()->withSuccess(['Updated']);

        }
        public function updatePassword(\App\Http\Requests\User\UpdateDetailsRequest $request)
        {
            $user_id = $request->id;
            $user = \App\Models\User::find($user_id);
            $users = auth()->user()->availableUsers();
            if( !$user || (count($users) && !in_array($user_id, $users) )) 
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }

            $data = $request->all();
            if( trim($data['password']) != '' ) 
            {
                unset($data['id']);
                $user->update($data);
            }
            event(new \App\Events\User\UpdatedByAdmin($user, 'password'));
            return redirect()->back()->withSuccess(trans('app.login_updated'));
        }

        public function updateDWPass(\App\Http\Requests\User\UpdateDetailsRequest $request)
        {
            $user_id = $request->id;
            $user = \App\Models\User::find($user_id);
            $users = auth()->user()->availableUsers();
            if( !$user || (count($users) && !in_array($user_id, $users) )) 
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }

            $data = $request->all();
            $old_confirm_token = $data['old_confirmation_token'];
            if(!empty($user->confirmation_token) && !\Illuminate\Support\Facades\Hash::check($old_confirm_token, $user->confirmation_token) ) 
            {
                return redirect()->back()->withErrors(['이전 환전비밀번호가 틀립니다']);
            }

            if( trim($data['confirmation_token']) != '' ) 
            {
                unset($data['id']);
                $data['confirmation_token'] = \Illuminate\Support\Facades\Hash::make($data['confirmation_token']);
                $user->update($data);
            }
            event(new \App\Events\User\UpdatedByAdmin($user, 'depositinfo'));
            return redirect()->back()->withSuccess(['환전비번을 업데이트했습니다']);
        }

        public function resetDWPass(\Illuminate\Http\Request $request)
        {
            $user_id = $request->id;
            $user = \App\Models\User::find($user_id);
            $users = auth()->user()->availableUsers();
            if( !$user && count($users) && !in_array($user->id, $users) ) 
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            $user->update(['confirmation_token' => null]);
            return redirect()->back()->withSuccess(['환전비번을 리셋했습니다']);
        }

        public function updateProfile(\Illuminate\Http\Request $request)
        {
            $user_id = $request->id;
            $user = \App\Models\User::find($user_id);
            $users = auth()->user()->availableUsers();
            if( !$user || (count($users) && !in_array($user_id, $users) )) 
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            
            $data = $request->all();
            $customesettings = [
                'gameOn' => 0,
                'moneyperm' => 0,
                'deluser' => 0,
                'manualjoin' => 0,
                'happyuser' => 0,
                'swiching' => 0,
                'ppverifyOn' => 0,
                'evoswitching' => 0,
            ];
            foreach ($customesettings as $setting => $value)
            {
                if (isset($data[$setting]) && $data[$setting]=='on')
                {
                    $customesettings[$setting] = 1;
                    unset($data[$setting]);
                }
            }

            $data['session'] = json_encode($customesettings);


            if (isset($data['gamertp']))
            {
                if ($data['gamertp'] >= 90 && $data['gamertp'] <= 100)
                {
                    if( $user->isInOutPartner())
                    {
                        $shopIds = $user->availableShops();
                        if (count($shopIds) > 0)
                        {
                            $rtppercent = \App\Models\Shop::whereIn('id', $shopIds)->update(['percent' => $data['gamertp']]);
                        }
                    }
                }
                else
                {
                    return redirect()->back()->withErrors([trans('rtp must be an integer between 80 and 98')]);
                }
                
                unset($data['gamertp']);

            }
            // if (isset($data['account_no']) || isset($data['recommender']))
            // {
            //     if (($user->account_no != $data['account_no']) || ($user->recommender != $data['recommender']))
            //     {
            //         return redirect()->back()->withErrors(['임시 계좌설정을 할수 없습니다']);
            //     }
            // }
            
            if( !$user->isInOutPartner())
            {
                if ($user->hasRole('user'))
                {
                    $parent = $user->shop;
                }
                else{
                    $parent = $user->referral;
                }
                $check_deals = [
                    'deal_percent',
                    'table_deal_percent',
                    'ggr_percent',
                    'table_ggr_percent',
                    'pball_single_percent',
                    'pball_comb_percent',
                    'sports_deal_percent',
                    'card_deal_percent'
                ];
                foreach ($check_deals as $dealtype)
                {
                    if ($parent!=null &&  isset($data[$dealtype]) && $parent->{$dealtype} < $data[$dealtype])
                    {
                        return redirect()->back()->withErrors([trans('RollingGGRCannotBiggerThanParent')]);
                    }
                }
            }
            if (!$user->hasRole('user'))
            {
                $childs = $user->childPartners();
                if (count($childs) > 0)
                {
                    $check_deals = [
                        'deal_percent',
                        'table_deal_percent',
                        'ggr_percent',
                        'table_ggr_percent',
                        'pball_single_percent',
                        'pball_comb_percent',
                        'sports_deal_percent',
                        'card_deal_percent'
                    ];
                    foreach ($check_deals as $dealtype)
                    {
                        if (isset($data[$dealtype]))
                        {
                            $highRateUsers = \App\Models\User::whereIn('id', $childs)->where($dealtype, '>', $data[$dealtype])->get();

                            if (count($highRateUsers) > 0)
                            {
                                $firstUser = $highRateUsers->first();
                                return redirect()->back()->withErrors([trans('RollingGGRCannotBiggerThanParent')]);
                            }
                        }
                    }
                }
            }
            if ($user->hasRole('manager'))
            {
                $user->shop->update($data);   
            }
            $user->update($data);

            if (empty($data['memo']))
            {
                if ($user->memo)
                {
                    $user->memo->delete();
                }
            }
            else
            {
                if ($user->memo)
                {
                    $user->memo->update(['memo' => $data['memo']]);
                }
                else
                {
                    \App\Models\UserMemo::create([
                        'user_id' => $user->id,
                        'writer_id' => auth()->user()->id,
                        'memo' => $data['memo']
                    ]);
                }
            }
            event(new \App\Events\User\UpdatedByAdmin($user, 'profile'));

            if( $this->userIsBanned($user, $request) ) 
            {
                event(new \App\Events\User\Banned($user));
            }

            return redirect()->back()->withSuccess(['Updated']);
        }
        public function percent(\Illuminate\Http\Request $request)
        {
            $userid = -1;
            if ($request->id != '')
            {
                $userid = $request->id;
            }
            
            $availableUsers = auth()->user()->availableUsers();
            if (!in_array($userid, $availableUsers))
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            $user = \App\Models\User::find($userid);
            if (!$user)
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            
            return view('modal.percent',compact( 'user'));
        }
        public function updatePercent(\Illuminate\Http\Request $request)
        {
            $user_id = $request->user_id;
            $user = \App\Models\User::find($user_id);
            $users = auth()->user()->availableUsers();
            if( !$user || (count($users) && !in_array($user_id, $users) )) 
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            
            $data = $request->all();
            
            if( !$user->isInOutPartner())
            {
                if ($user->hasRole('user'))
                {
                    $parent = $user->shop;
                }
                else{
                    $parent = $user->referral;
                }
                $check_deals = [
                    'deal_percent',
                    'table_deal_percent',
                    'ggr_percent',
                    'table_ggr_percent',
                    'pball_single_percent',
                    'pball_comb_percent',
                    'sports_deal_percent',
                    'card_deal_percent'
                ];
                foreach ($check_deals as $dealtype)
                {
                    if ($parent!=null &&  isset($data[$dealtype]) && $parent->{$dealtype} < $data[$dealtype])
                    {
                        return redirect()->back()->withErrors([trans('RollingGGRCannotBiggerThanParent')]);
                    }
                }
            }
            if (!$user->hasRole('user'))
            {
                $childs = $user->childPartners();
                if (count($childs) > 0)
                {
                    $check_deals = [
                        'deal_percent',
                        'table_deal_percent',
                        'ggr_percent',
                        'table_ggr_percent',
                        'pball_single_percent',
                        'pball_comb_percent',
                        'sports_deal_percent',
                        'card_deal_percent'
                    ];
                    foreach ($check_deals as $dealtype)
                    {
                        if (isset($data[$dealtype]))
                        {
                            $highRateUsers = \App\Models\User::whereIn('id', $childs)->where($dealtype, '>', $data[$dealtype])->get();

                            if (count($highRateUsers) > 0)
                            {
                                $firstUser = $highRateUsers->first();
                                return redirect()->back()->withErrors([trans('RollingGGRCannotBiggerThanParent')]);
                            }
                        }
                    }
                }
            }
            if ($user->hasRole('manager'))
            {
                $user->shop->update($data);   
            }
            $user->update($data);
            return redirect()->back()->withSuccess(['Updated']);
        }
        public function message(\Illuminate\Http\Request $request)
        {
            $userid = -1;
            if ($request->id != '')
            {
                $userid = $request->id;
            }
            
            $availableUsers = auth()->user()->availableUsers();
            if (!in_array($userid, $availableUsers))
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            $user = \App\Models\User::find($userid);
            if (!$user)
            {
                return redirect()->back()->withErrors([trans('Could not find user')]);
            }
            $msgtemps = \App\Models\MsgTemp::where('writer_id', auth()->user()->id)->orderBy('order','desc')->get();
            return view('modal.message',compact( 'user', 'msgtemps'));
        }
        public function sendMessage(\Illuminate\Http\Request $request)
        {
            $data = $request->only([
                'title',
                'content',
                'ref_id',
                'user_id',
                'type'
            ]);
            $data['writer_id'] = auth()->user()->id;
            \App\Models\Message::create($data);
            return redirect()->back()->withSuccess([trans('SentMessage')]);
        }
        private function userIsBanned(\App\Models\User $user, \Illuminate\Http\Request $request)
        {
            return $user->status != $request->status && $request->status == \App\Support\Enum\UserStatus::BANNED;
        }
        public function convertDealBalance(\Illuminate\Http\Request $request){
            if( !\Illuminate\Support\Facades\Auth::check() ) {
                return response()->json(['error' => true, 'msg' => trans('app.site_is_turned_off'), 'code' => '001']);
            }
            \DB::beginTransaction();
            if ($request->user_id)
            {
                $users = auth()->user()->availableUsers();
                if( count($users) && !in_array($request->user_id, $users) ) 
                {
                    \DB::commit();
                    return response()->json([
                        'error' => true, 
                        'msg' => trans('No Permission'),
                        'code' => '005'
                    ], 200);
                }
                $user = \App\Models\User::lockForUpdate()->where('id',$request->user_id)->first();
            }
            else
            {
                $user = \App\Models\User::lockForUpdate()->where('id',\Auth::id())->first();
            }
            if (!$user)
            {
                \DB::commit();
                return response()->json([
                    'error' => true, 
                    'msg' => trans('Retry'),
                    'code' => '001'
                ], 200);
            }
            if ($user->hasRole('user'))
            {
                $b = $user->withdrawAll('convertDealBalance');
                if (!$b)
                {
                    \DB::commit();
                    return response()->json([
                        'error' => true, 
                        'msg' => trans('OccurErrorInProvierWithdraing'),
                        'code' => '002'
                    ], 200);
                }
            }


            $summ = str_replace(',','',$request->summ);
            $shop = \App\Models\Shop::lockForUpdate()->where('id',$user->shop_id)->first();           
            if($user->hasRole('manager')){
                if (!$shop)
                {
                    \DB::commit();
                    return response()->json([
                        'error' => true, 
                        'msg' => trans('Retry'),
                        'code' => '001'
                    ], 200);
                }
                $real_deal_balance = $shop->deal_balance - $shop->mileage;
            }
            else {
                $real_deal_balance = $user->deal_balance - $user->mileage;
            }

            if ($summ )
            {
                $summ = abs($summ);
                if ($real_deal_balance < $summ)
                {
                    \DB::commit();
                    return response()->json([
                        'error' => true, 
                        'msg' => trans('LowDeal'),
                        'code' => '000'
                    ], 200);
                }
            }
            else
            {
                $summ = $real_deal_balance;
            }

            if ($summ > 0) {
                //out balance from master
                $master = $user->referral;
                while ($master!=null && !$master->isInoutPartner())
                {
                    $master = $master->referral;
                }
                if ($master == null)
                {
                    \DB::commit();
                    return response()->json([
                        'error' => true, 
                        'msg' => '상위파트너를 찾을수 없습니다.',
                        'code' => '000'
                    ], 200);
                }
                
                if ($master->balance < $summ)
                {
                    \DB::commit();
                    return response()->json([
                        'error' => true, 
                        'msg' => '상위파트너보유금이 부족합니다',
                        'code' => '000'
                    ], 200);
                }
                $master->update(
                    ['balance' => $master->balance - $summ ]
                );
                $master = $master->fresh();

                if ($user->hasRole('manager'))
                {
                    $old = $shop->balance;
                    $shop->balance = $shop->balance + $summ;
                    $shop->deal_balance = $real_deal_balance - $summ;
                    $shop->mileage = 0;
                    $shop->save();
                    $shop = $shop->fresh();
                    \App\Models\ShopStat::create([
                        'user_id' => $master->id,
                        'type' => 'deal_out',
                        'sum' => $summ,
                        'old' => $old,
                        'new' => $shop->balance,
                        'balance' => $master->balance,
                        'shop_id' => $shop->id,
                        'date_time' => \Carbon\Carbon::now()
                    ]);
                    //create another transaction for mananger account
                    \App\Models\Transaction::create([
                        'user_id' => $user->id, 
                        'payeer_id' => $master->id, 
                        'type' => 'deal_out', 
                        'summ' => abs($summ), 
                        'old' => $old,
                        'new' => $shop->balance,
                        'balance' => $master->balance,
                        'shop_id' => $shop->id,
                    ]);
                }
                else
                {
                    $old = $user->balance;
                    $user->balance = $user->balance + $summ;
                    $user->deal_balance = $real_deal_balance - $summ;
                    $user->mileage = 0;
                    $user->save();
                    $user = $user->fresh();
        
                    \App\Models\Transaction::create([
                        'user_id' => $user->id,
                        'payeer_id' => $master->id,
                        'system' => $user->username,
                        'type' => 'deal_out',
                        'summ' => $summ,
                        'old' => $old,
                        'new' => $user->balance,
                        'balance' => $master->balance,
                        'shop_id' => $user->shop_id
                    ]);
                }
            }

            \DB::commit();
            
            return response()->json(['error' => false]);
        }
        public function waitInOut(\Illuminate\Http\Request $request){
            $ids = [];
            if(isset($request->ids))
            {
                $ids = $request->ids;
            }
            $transactions = \App\Models\WithdrawDeposit::whereIn('id', $ids)->get();
            if (!$transactions)
            {
                return response()->json(['error'=>true, 'code'=>0, 'msg'=> '유효하지 않은 신청입니다.']);
            }
            $err_ids = [];
            foreach($transactions as $transaction){
                if ($transaction->status!=\App\Models\WithdrawDeposit::REQUEST)
                {
                    $err_ids[] = $transaction->id;
                }
                $transaction->update(['status' => \App\Models\WithdrawDeposit::WAIT]);
            }
            if(count($err_ids) > 0){
                return response()->json(['error'=>true, 'code'=>1, 'msg'=> '번호가 [' . implode(',', $err_ids) . ']인 항목들은 이미 처리된 신청내역들입니다.']);
            }
            return response()->json(['error'=>false, 'msg'=> '대기처리하였습니다.']);
        }
        public function depositAccount(\Illuminate\Http\Request $request){
            $amount = $request->money;
            $account = $request->account;
            $force = $request->force;
            $user = auth()->user();
            if (!$user)
            {
                return response()->json([
                    'error' => true, 
                    'msg' => '로그인이 필요합니다.',
                    'code' => '001'
                ], 200);
            }
            

            // if ($amount > 5000000)
            // {
            //     return response()->json([
            //         'error' => true, 
            //         'msg' => '충전금액은 최대 500만원까지 가능합니다.',
            //         'code' => '002'
            //     ], 200);
            // }

            // if ($account == '')
            // {
            //     return response()->json([
            //         'error' => true, 
            //         'msg' => '입금자명을 입력하세요',
            //         'code' => '003'
            //     ], 200);
            // }
            $master = $user->referral;
            while ($master!=null && !$master->isInoutPartner())
            {
                $master = $master->referral;
            }
            if (!$master)
            {
                return response()->json([
                    'error' => true, 
                    'msg' => '본사를 찾을수 없습니다.',
                    'code' => '001'
                ], 200);
            }
            if ($master->bank_name == 'PAYWIN') // 페이윈 가상계좌
            {
                if ($amount == 0)
                {
                    return response()->json([
                        'error' => true, 
                        'msg' => '충전금액을 입력하세요',
                        'code' => '002'
                    ], 200);
                }
                //상품조회
                try {
                    $data = [
                        'sellerId' => "mir",
                    ];
                    $response = Http::withBody(json_encode($data), 'application/json')->post('http://api.paywin.co.kr/api/markerSellerInfo');
                    if ($response->ok())
                    {
                        $data = $response->json();
                        $product = null;
                        if ($data['resultCode'] == '0000')
                        {
                            foreach ($data['productList'] as $p)
                            {
                                if ($p['p_price'] == $amount)
                                {
                                    $product = $p;
                                    break;
                                }
                            }
                            if ($product != null)
                            {
                                //계좌발급 요청
                                $data = [
                                    'm_id' => $user->id,
                                    'm_name' => $account,
                                    'p_seq' => $product['p_seq'],
                                    'mid' => $master->account_no
                                ];
                                $response = Http::withBody(json_encode($data), 'application/json')->post('http://api.paywin.co.kr/api/markerBuyProduct');
                                if ($response->ok())
                                {
                                    $data = $response->json();
                                    if ($data['resultCode'] == '0000')
                                    {
                                        return response()->json([
                                            'error' => false, 
                                            'msg' => $data['orderVO']['o_sender_bank_name'] . ' [ ' .$data['orderVO']['o_virtual_account']. ' ]',
                                        ], 200);
                                    }
                                    else //가상계좌 발급 실패
                                    {
                                        return response()->json([
                                            'error' => true, 
                                            'msg' => '계좌요청이 실패하였습니다. 다시 시도해주세요',
                                            'code' => 001
                                        ], 200);
                                    }
                                }
                                else //가상계좌 발급 실패
                                {
                                    return response()->json([
                                        'error' => true, 
                                        'msg' => '계좌요청이 실패하였습니다. 다시 시도해주세요',
                                        'code' => 001
                                    ], 200);
                                }

                            }
                            else //매칭되는 금액 상품 없음
                            {
                                return response()->json([
                                    'error' => true, 
                                    'msg' => '입금금액은 1만원단위로 최대 199만원까지 가능합니다.',
                                    'code' => 002
                                ], 200);
                            }
                        }
                        else //상품조회 실패
                        {
                            return response()->json([
                                'error' => true, 
                                'msg' => '계좌요청이 실패하였습니다. 다시 시도해주세요',
                                'code' => 001
                            ], 200);
                        }
                    }
                    else //상품조회 실패
                    {
                        return response()->json([
                            'error' => true, 
                            'msg' => '계좌요청이 실패하였습니다. 다시 시도해주세요',
                            'code' => 001
                        ], 200);
                    }
                }
                catch (Exception $ex)
                {
                    return response()->json([
                        'error' => true, 
                        'msg' => '계좌요청이 실패하였습니다. 다시 시도해주세요',
                        'code' => 001
                    ], 200);
                }
            }
            else if ($master->bank_name == 'JUNCOIN') //준코인 가상계좌
            {
                if ($amount == 0)
                {
                    return response()->json([
                        'error' => true, 
                        'msg' => '충전금액을 입력하세요',
                        'code' => '002'
                    ], 200);
                }

                if ($amount % 10000 != 0)
                {
                    return response()->json([
                        'error' => true, 
                        'msg' => '1만원 단위로 입금하세요',
                        'code' => '001'
                    ], 200);
                }
                $url = 'https://jun-200.com/sign-up-process-api?uid='.$master->account_no. '@'.$user->id.'&site='.$master->recommender.'&p='.($amount/10000).'&rec_name=' . $account;
                return response()->json([
                    'error' => false, 
                    'msg' => '팝업창에서 입금계좌신청을 하세요',
                    'url' => $url
                ], 200);
            }
            else if ($master->bank_name == 'WORLDPAY') //월드페이 가상계좌
            {
                $paramete=$master->account_no.'||'.$user->username.'||'.$user->recommender.'||'.$user->bank_name.'||'.$user->account_no;
                
                $url = 'http://1one-pay.com/api_mega/?token='.rawurlencode($paramete);
                return response()->json([
                    'error' => false, 
                    'msg' => '팝업창에서 입금계좌신청을 하세요',
                    'url' => $url
                ], 200);
            }
            else if ($master->bank_name == 'OSSCOIN') //OSS코인 가상계좌
            {
                $url = 'https://testapi.osscoin.net';
                $publicKey = $master->account_no; //WGJzUHM1UEZRMmRCb3JuRjJxLzM1UT09
                $agent = $master->recommender; // tposeidon
                $ossbanks = ['국민은행' => 0,'우리은행' => 1,'신한은행' => 2,'하나은행' => 3,'SC 제일은행' => 4,'씨티은행' => 5,'기업은행' => 6,'농협중앙회' => 7,'단위농협' => 8,'새마을금고' => 9,'케이뱅크' => 10,'카카오뱅크' => 11,'수협' => 12,'신협' => 13,'산림조합' => 14,'상호저축은행' => 15,'부산은행' => 16,'경남은행' => 17,'광주은행' => 18,'대구은행' => 19,'전북은행' => 20,'제주은행' => 21,'우체국' => 22,'한국투자증권' => 23,'토스뱅크' => 24,'뱅크오브아메리카' => 25,'bnp 파리바' => 26,'jp 모간' => 27,'동양증권' => 28,'현대증권' => 29,'미래에셋증권' => 30,'대우증권' => 31,'삼성증권' => 32,'우리투자증권' => 33,'교보증권' => 34,'하이투자증권' => 35,'HMC 투자증권' => 36,'키움증권' => 37,'이트레이드증권' => 38,'대신증권' => 39,'아이엠투자증권' => 40,'한화투자증권' => 41,'하나대투증권' => 42,'신한금융투자' => 43,'동부증권' => 44,'유진투자증권' => 45,'메리츠증권' => 46,'부국증권' => 47,'신영증권' => 48,'LIG 투자증권' => 49,'유안타증권' => 50,'이베스트투자증권' => 51,'NH 농협투자증권' => 52,'KB 증권' => 53,'SK 증권' => 54,'SBI 저축은행' => 55,'카카오페이증권' => 56,'IBK 투자증권' => 57,'산업은행' => 58,'모건스탠리' => 59,'중국공상은행' => 60,'케이티비투자증권' => 61,'BNK 투자증권' => 63,'BOA 은행' => 64,'HSBC 은행' => 65,'도이치은행' => 66,'미쓰비시도쿄 UFJ 은행' => 67,'미즈호은행' => 68,'피엔피파리팡느행' => 69,'중국은행' => 70,'한국포스증권' => 71,'현대차증권' => 72];
                // 회원조회
                $data = [
                    'agent' => $agent,
                    'userAccount' => $user->username
                ];
                $response = Http::withHeaders([
                    'PublicKey' => $publicKey
                    ])->post(config('app.osscoin_url') . '/api_user', $data);
                if (!$response->ok())
                {
                    return response()->json([
                        'error' => true, 
                        'msg' => '계좌요청이 실패하였습니다. 다시 시도해주세요',
                        'code' => 001
                    ], 200);
                }
                $data = $response->json();
                if ($data['code'] != 0)
                {
                    $bank_id = 99;
                    foreach($ossbanks as $bankname => $id){
                        if($bankname == $user->bank_name){
                            $bank_id = $id;
                        }
                    }
                    // 회원가입
                    $data = [
                        'agent' => $agent,
                        'userAccount' => $user->username,
                        'userName' => $user->username,
                        'userBank' => $bank_id,
                        'userBankaccount' => $user->account_no
                    ];
                    $response = Http::withHeaders([
                        'PublicKey' => $publicKey
                        ])->post(config('app.osscoin_url') . '/api_join', $data);
                    if (!$response->ok())
                    {
                        return response()->json([
                            'error' => true, 
                            'msg' => '계좌요청이 실패하였습니다. 다시 시도해주세요',
                            'code' => 001
                        ], 200);
                    }
                    $data = $response->json();
                }
                if(($data['code'] == 0 || $data['code'] == 5) && isset($data['token'])){
                    $url = config('app.osscoin_user_url') . '/?token='.$data['token'];
                    return response()->json([
                        'error' => false, 
                        'msg' => '팝업창에서 입금계좌신청을 하세요',
                        'url' => $url
                    ], 200);
                }else{
                    return response()->json([
                        'error' => true, 
                        'msg' => '계좌요청이 실패하였습니다. 다시 시도해주세요',
                        'code' => 001
                    ], 200);
                }
            }
            else if ($master->bank_name == '경남가상계좌') //경남 가상계좌
            {
                // $url = 'https://jun-200.com/sign-up-process-api?uid='.$master->account_no. '@'.$user->id.'&site='.$master->recommender.'&p='.($amount/10000).'&rec_name=' . $account;
                $url = 'https://bnka.jtrader.net';
                return response()->json([
                    'error' => false, 
                    'msg' => '팝업창에서 입금계좌신청을 하세요',
                    'url' => $url
                ], 200);
            }
            else if ($master->bank_name == 'VirtualAcc') //버철가상계좌
            {
                // $url = 'https://jun-200.com/sign-up-process-api?uid='.$master->account_no. '@'.$user->id.'&site='.$master->recommender.'&p='.($amount/10000).'&rec_name=' . $account;
                $url = 'https://virtualacc.co.kr/mobileAuth';
                return response()->json([
                    'error' => false, 
                    'msg' => '팝업창에서 입금계좌신청을 하세요',
                    'url' => $url
                ], 200);
            }
            else if ($master->bank_name == '효원라이프') //효원라이프
            {
                // $url = 'https://jun-200.com/sign-up-process-api?uid='.$master->account_no. '@'.$user->id.'&site='.$master->recommender.'&p='.($amount/10000).'&rec_name=' . $account;
                $url = 'http://shop.hwlife.kr/direct/payInfo.do?key=cml2ZXI=';
                return response()->json([
                    'error' => false, 
                    'msg' => '팝업창에서 입금계좌신청을 하세요',
                    'url' => $url
                ], 200);
            }
            else if ($master->bank_name == 'MESSAGE') //계좌를 쪽지방식으로 알려주는 총본사
            {
                $date_time = date('Y-m-d H:i:s', strtotime("-2 minutes"));
                $lastMsgs = \App\Models\Message::where([
                    'writer_id' => $user->id,
                ])->where('created_at', '>', $date_time)->get();
                if (count($lastMsgs) > 0)
                {
                    return response()->json([
                        'error' => true, 
                        'msg' => '이미 쪽지를 발송하였습니다. 잠시후 다시 시도하세요',
                    ], 200);
                }
                $data = [
                    'user_id' => $master->id,
                    'writer_id' => $user->id,
                    'type' => 1,
                    'title' => '계좌문의드립니다',
                    'content' => '입금계좌 문의드립니다'
                ];
                \App\Models\Message::create($data);
                return response()->json([
                    'error' => false, 
                    'msg' => trans('SentMessage'),
                ], 200);
            }
            else
            {
                $telegramId = $master->address;
                if ($force==0 && $user->hasRole('user'))
                {
                    return response()->json([
                        'error' => false, 
                        'msg' => '텔레그램 문의, 아이디 ' . $telegramId,
                    ], 200);
                }
                else
                {
                    return response()->json([
                        'error' => false, 
                        'msg' => $master->bank_name . ' [ ' .$master->account_no. ' ] , ' . $master->recommender . (empty($telegramId)?'':(', 텔레그램아이디 ' . $telegramId)),
                    ], 200);
                }
            }
        }
        public function deposit(\Illuminate\Http\Request $request){
            if( !\Illuminate\Support\Facades\Auth::check() ) {
                return response()->json(['error' => true, 'msg' => trans('app.site_is_turned_off'), 'code' => '001']);
            }

            $user = auth()->user();

            if($user->role_id > 1 && ($user->bank_name == null || $user->bank_name == '')){
                return response()->json([
                    'error' => true, 
                    'msg' => '계좌정보를 입력해주세요',
                    'code' => '004'
                ], 200);
            }
            if ($user->hasRole('user') && ($user->recommender == '' || $user->bank_name == ''  || $user->account_no == '') && ($request->accountName == '' || $request->bank == ''  || $request->no == ''))
            {
                return response()->json([
                    'error' => true, 
                    'msg' => '입금정보를 입력해주세요.',
                ], 200);
            }
            $oldrequest = \App\Models\WithdrawDeposit::where(['user_id' => $user->id])->whereIn('status', [\App\Models\WithdrawDeposit::REQUEST,\App\Models\WithdrawDeposit::WAIT])->get();
            if (count($oldrequest) > 0)
            {
                return response()->json([
                    'error' => true, 
                    'msg' => '이미 신청중이니 기다려주세요',
                    'code' => '001'
                ], 200);
            }
            if( !$request->money ) 
            {
                return response()->json([
                    'error' => true, 
                    'msg' => '충전금액을 입력해주세요'
                ], 200);
            }
            if ($user->hasRole('user')  && ($request->accountName != '' || $request->bank != ''  || $request->no != ''))
            {
                $user->update([
                    'recommender' => $request->accountName,
                    'bank_name' => $request->bank,
                    'account_no' => $request->no
                ]);
                $user =  $user->fresh();
            }
            $money = abs(str_replace(',','', $request->money));
            if($user->hasRole('manager')){
                //send it to master.
                $master = $user->referral;
                while ($master!=null && !$master->isInoutPartner())
                {
                    $master = $master->referral;
                }
                if ($master == null)
                {
                    return response()->json([
                        'error' => true, 
                        'msg' => '본사를 찾을수 없습니다.',
                        'code' => '002'
                    ], 200);
                }
                \App\Models\WithdrawDeposit::create([
                    'user_id' => $user->id,
                    'payeer_id' => $master->id,
                    'type' => 'add',
                    'sum' => $money,
                    'status' => \App\Models\WithdrawDeposit::REQUEST,
                    'shop_id' => $user->shop_id,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                    'bank_name' => $user->bank_name,
                    'account_no' => $user->account_no,
                    'recommender' => $user->recommender,
                    'partner_type' => 'shop'
                ]);
            }
            else {
                $master = $user->referral;
                while ($master!=null && !$master->isInoutPartner())
                {
                    $master = $master->referral;
                }
                if ($master == null)
                {
                    return response()->json([
                        'error' => true, 
                        'msg' => '본사를 찾을수 없습니다.',
                        'code' => '002'
                    ], 200);
                }
                \App\Models\WithdrawDeposit::create([
                    'user_id' => $user->id,
                    'payeer_id' => $master->id,
                    'type' => 'add',
                    'sum' => $money,
                    'status' => \App\Models\WithdrawDeposit::REQUEST,
                    'shop_id' => 0,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                    'bank_name' => $user->bank_name,
                    'account_no' => $user->account_no,
                    'recommender' => $user->recommender,
                    'partner_type' => 'partner'
                ]);
            }

            

            return response()->json(['error' => false]);
        }

        public function withdraw(\Illuminate\Http\Request $request)
        {
            if( !\Illuminate\Support\Facades\Auth::check() ) {
                return response()->json(['error' => true, 'msg' => trans('app.site_is_turned_off'), 'code' => '001']);
            }

            $user = auth()->user();

            if(($user->bank_name == null || $user->bank_name == '')){
                return response()->json([
                    'error' => true, 
                    'msg' => '계좌정보를 입력해주세요',
                    'code' => '004'
                ], 200);
            }
            if ($user->role_id > 1 && empty($user->confirmation_token))
            {
                return response()->json([
                    'error' => true, 
                    'msg' => '환전비밀번호를 설정하세요',
                    'code' => '010'
                ], 200);
            }
            if ($user->role_id > 1 && empty($request->confirmation_token))
            {
                return response()->json([
                    'error' => true, 
                    'msg' => '환전비밀번호를 입력하세요',
                    'code' => '011'
                ], 200);
            }
            if ($user->role_id > 1 && !\Illuminate\Support\Facades\Hash::check($request->confirmation_token, $user->confirmation_token))
            {
                return response()->json([
                    'error' => true, 
                    'msg' => '환전비밀번호가 틀립니다',
                    'code' => '012'
                ], 200);
            }

            if ($user->hasRole('user') && ($user->recommender == '' || $user->bank_name == ''  || $user->account_no == '') && ($request->accountName == '' || $request->bank == ''  || $request->no == ''))
            {
                return response()->json([
                    'error' => true, 
                    'msg' => '출금정보를 입력해주세요.',
                ], 200);
            }
            if( !$request->money ) 
            {
                return response()->json([
                    'error' => true, 
                    'msg' => '환전금액을 입력해주세요',
                    'code' => '001'
                ], 200);
            }
            $oldrequest = \App\Models\WithdrawDeposit::where(['user_id' => $user->id])->whereIn('status', [\App\Models\WithdrawDeposit::REQUEST,\App\Models\WithdrawDeposit::WAIT])->get();
            if (count($oldrequest) > 0)
            {
                return response()->json([
                    'error' => true, 
                    'msg' => '이미 신청중이니 기다려주세요',
                    'code' => '001'
                ], 200);
            }

            $money = abs(str_replace(',','', $request->money));

            if($user->hasRole('manager')){
                if($money > $user->shop->balance) {
                    return response()->json([
                        'error' => true, 
                        'msg' => '환전금액은 보유금액을 초과할수 없습니다',
                        'code' => '002'
                    ], 200);
                }
            }
            else {
                if($money > $user->balance) {
                    return response()->json([
                        'error' => true, 
                        'msg' => '환전금액은 보유금액을 초과할수 없습니다',
                        'code' => '002'
                    ], 200);
                }
            }

            if ($user->hasRole('user'))
            {
                $b = $user->withdrawAll('withdrawRequest');
                if (!$b)
                {
                    \DB::commit();
                    return response()->json([
                        'error' => true, 
                        'msg' => trans('OccurErrorInProvierWithdraing'),
                        'code' => '002'
                    ], 200);
                }

                // if ($user->playing_game != null)
                // {
                //     return response()->json([
                //         'error' => true, 
                //         'msg' => '게임중에 환전신청을 할수 없습니다.',
                //         'code' => '002'
                //     ], 200);
                // }
                // $user->update([
                //     'recommender' => $request->accountName,
                //     'bank_name' => $request->bank,
                //     'account_no' => $request->no
                // ]);
                // $user =  $user->fresh();
            }


            if($user->hasRole('manager')){
                //send it to master.
                $master = $user->referral;
                while ($master!=null && !$master->isInoutPartner())
                {
                    $master = $master->referral;
                }
                if ($master == null)
                {
                    return response()->json([
                        'error' => true, 
                        'msg' => '본사를 찾을수 없습니다.',
                        'code' => '002'
                    ], 200);
                }
                \App\Models\WithdrawDeposit::create([
                    'user_id' => $user->id,
                    'payeer_id' => $master->id,
                    'type' => 'out',
                    'sum' => $money,
                    'status' => \App\Models\WithdrawDeposit::REQUEST,
                    'shop_id' => $user->shop_id,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                    'bank_name' => $user->bank_name,
                    'account_no' => $user->account_no,
                    'recommender' => $user->recommender,
                    'partner_type' => 'shop'
                ]);

                $shop = \App\Models\Shop::lockforUpdate()->where('id', $user->shop_id)->get()->first();
                $shop->update([
                    'balance' => $shop->balance - $money,
                ]);

                $open_shift = \App\Models\OpenShift::where([
                    'shop_id' => $shop->id, 
                    'end_date' => null,
                    'type' => 'shop'
                ])->first();
                if( $open_shift ) 
                {
                    $open_shift->increment('balance_out', $money);
                }
            }
            else {
                $user->update(
                    [
                        'balance' => $user->balance - $money,
                        'total_out' => $user->total_out + $money,
                    ]
                );
                $master = $user->referral;
                while ($master!=null && !$master->isInoutPartner())
                {
                    $master = $master->referral;
                }
                if ($master == null)
                {
                    return response()->json([
                        'error' => true, 
                        'msg' => '본사를 찾을수 없습니다.',
                        'code' => '002'
                    ], 200);
                }
                \App\Models\WithdrawDeposit::create([
                    'user_id' => $user->id,
                    'payeer_id' => $master->id,
                    'type' => 'out',
                    'sum' => $money,
                    'status' => \App\Models\WithdrawDeposit::REQUEST,
                    'shop_id' => 0,
                    'created_at' => \Carbon\Carbon::now(),
                    'updated_at' => \Carbon\Carbon::now(),
                    'bank_name' => $user->bank_name,
                    'account_no' => $user->account_no,
                    'recommender' => $user->recommender,
                    'partner_type' => 'partner'
                ]);

                $open_shift = \App\Models\OpenShift::where([
                    'user_id' => $user->id, 
                    'end_date' => null,
                    'type' => 'partner'
                ])->first();
                if( $open_shift ) 
                {
                    $open_shift->increment('balance_out', $money);
                }
            }
            return response()->json(['error' => false]);
        }
        public function inoutList_json(\Illuminate\Http\Request $request)
        {
            if( !\Illuminate\Support\Facades\Auth::check() ) {
                return response()->json(['error' => true, 'add' => 0, 'out'=>0,'now' => \Carbon\Carbon::now()]);
            }

            if (isset($request->rating))
            {
                auth()->user()->rating = $request->rating;
                auth()->user()->save();
            }

            $res['now'] = \Carbon\Carbon::now();
            $transactions1 = \App\Models\WithdrawDeposit::where([
                'type' => 'add',
                'status' => \App\Models\WithdrawDeposit::REQUEST,
                'payeer_id' => auth()->user()->id]);
            $transactions2 = \App\Models\WithdrawDeposit::where([
                'type' => 'out',
                'status' => \App\Models\WithdrawDeposit::REQUEST,
                'payeer_id' => auth()->user()->id]);
            $unreadmsgs = \App\Models\Message::where('user_id', auth()->user()->id)->whereNull('read_at')->get();
            $res['join'] = 0;
            $res['msg'] = count($unreadmsgs);
            if (auth()->user()->isInOutPartner()) {
                $availableUsers = auth()->user()->availableUsers();
                $joinUsers = \App\Models\User::where('status', \App\Support\Enum\UserStatus::JOIN)->whereIn('id', $availableUsers);
                $res['join'] = $joinUsers->count();
            }
            $res['add'] = $transactions1->count();
            $res['out'] = $transactions2->count();
            
            $res['rating'] = auth()->user()->rating;
            return response()->json($res);
        }
    }

}
