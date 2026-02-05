<?php 
namespace App\Http\Controllers\Backend
{
    use Maatwebsite\Excel\Facades\Excel;
    class UsersController extends \App\Http\Controllers\Controller
    {
        private $users = null;
        private $max_users = 10000;
        public function __construct(\App\Repositories\User\UserRepository $users)
        {
            $this->middleware('auth');
            $this->middleware('permission:access.admin.panel');
            $this->users = $users;
        }
        public function agent_create(\Illuminate\Http\Request $request)
        {
            if($request->role_id != ""){
                $role_id = $request->role_id;
            }
            else
            {
                $role_id = null;
            }
            return view('modal.patnercreate', compact('role_id'));
        }

        public function agent_store(\App\Http\Requests\User\CreateUserRequest $request)
        {
            $data = $request->all() + ['status' => \App\Support\Enum\UserStatus::ACTIVE];

            $availablePartners = auth()->user()->hierarchyPartners();
            $availablePartners[] = auth()->user()->id;
            $parent = \App\Models\User::where(['username' => $request->parent, 'role_id' => $request->role_id+1])->whereIn('id', $availablePartners)->first();
            if (!$parent)
            {
                $role = \jeremykenedy\LaravelRoles\Models\Role::find($request->role_id+1);
                return response()->json(['error'=>true, 'msg'=> $request->parent . ' ' . trans($role->name) . '을(를) 찾을수 없습니다']);
            }
            if($parent->role_id>=8)
            {
                $validateuser = \App\Models\User::where(['username' => $request->username])->first();
            }
            else
            {
                $validatecomaster = $parent;
                while ($validatecomaster && !$validatecomaster->isInOutPartner())
                {
                    $validatecomaster = $validatecomaster->referral;
                }
                $validateuser = \App\Models\User::where(['username' => $request->username])->whereIn('id', $validatecomaster->availableUsers())->first();
            }
            if(isset($validateuser))
            {
                return redirect()->back()->withErrors([$request->username . '파트너가 이미 존재합니다']);
            }
            $role = \jeremykenedy\LaravelRoles\Models\Role::find($request->role_id);
            $data['parent_id'] = $parent->id;

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
                if (isset($data[$dealtype]) && $parent!=null &&  $parent->{$dealtype} < $data[$dealtype])
                {
                    return response()->json(['error'=>true, 'msg'=> trans('RollingGGRCannotBiggerThanParent')]);
                }
            }

            $comaster = auth()->user();
            while ($comaster && !$comaster->isInOutPartner())
            {
                $comaster = $comaster->referral;
            }
            // $manualjoin = 1; //default 1
            // if (auth()->user()->isInOutPartner())
            // {
            //     $manualjoin = 0;
            // }
            // else if (isset($comaster->sessiondata()['manualjoin']))
            // {
            //     $manualjoin = $comaster->sessiondata()['manualjoin'];
            // }
            $manualjoin = 0;
            if ($manualjoin == 1)
            {
                $data['status'] = \App\Support\Enum\UserStatus::JOIN;
            }

            $user = \App\Models\User::create($data);
            $user->detachAllRoles();
            $user->attachRole($role);
            event(new \App\Events\User\Created($user));

            //create shift for partners
            $type = 'partner';

            \App\Models\OpenShift::create([
                'start_date' => \Carbon\Carbon::now(), 
                'user_id' => $user->id, 
                'shop_id' => 0,
                'old_total' => 0,
                'deal_profit' => 0,
                'mileage' => 0,
                'type' => $type
            ]);

            if ($data['role_id'] == 3)  //create shop
            {
                $data['name'] = $data['username'];
                $shop = \App\Models\Shop::create($data + ['user_id' => auth()->user()->id]);
                
                //create shopuser table for all agents
                $parent = $user;
                while ($parent && !$parent->isInOutPartner())
                {
                    \App\Models\ShopUser::create([
                        'shop_id' => $shop->id, 
                        'user_id' => $parent->id
                    ]);
                    $parent = $parent->referral;
                }
                
                $user->update(['shop_id' => $shop->id]);
                $site = null;
                if ($parent == null){
                    $site = \App\Models\WebSite::where('backend', \Request::root())->first();
                }
                else
                {
                    $site = \App\Models\WebSite::where('adminid', $parent->id)->first();
                    if (!$site)
                    {
                        $site = \App\Models\WebSite::where('backend', \Request::root())->first();
                    }
                }
                if ($site == null)
                {
                    return response()->json(['error'=>true, 'msg'=> '파트너생성이 실패하였습니다.']);
                }
                \App\Models\Task::create([
                    'category' => 'shop', 
                    'action' => 'create', 
                    'item_id' => $shop->id,
                    'details' => $site->id,
                ]);
                $open_shift = \App\Models\OpenShift::create([
                    'start_date' => \Carbon\Carbon::now(), 
                    'balance' => 0, 
                    'user_id' => auth()->user()->id, 
                    'shop_id' => $shop->id
                ]); 
                event(new \App\Events\Shop\ShopCreated($shop));

            }
            if ($manualjoin == 1)
            {
                return response()->json(['error'=>false, 'msg'=> '파트너생성이 신청되었습니다. 승인될때까지 기다려주세요.', 'id'=>$user->id]);
            }
            return response()->json(['error'=>false, 'msg'=> '파트너가 생성되었습니다.', 'id'=>$user->id]);
        }
        public function agent_move(\Illuminate\Http\Request $request)
        {
            $availablePartners = auth()->user()->hierarchyPartners();
            $id = $request->id;
            $user = \App\Models\User::where('id', $id)->first();
            if (!$user || !in_array($id, $availablePartners))
            {
                return redirect()->back()->withErrors(['에이전트를 찾을수 없습니다']);
            }
            return view('modal.patnermove', compact('user'));
        }

        public function agent_update(\Illuminate\Http\Request $request)
        {
            $availablePartners = auth()->user()->hierarchyPartners();
            $id = $request->user_id;
            $user = \App\Models\User::where('id', $id)->first();
            if (!$user || !in_array($id, $availablePartners))
            {
                return response()->json(['error'=>true, 'msg'=> '에이전트를 찾을수 없습니다.']);
            }

            $parentname = $request->parent;
            $parent = \App\Models\User::where('username', $parentname)->where('role_id', $user->role_id+1)->first();

            if (!$parent || !in_array($parent->id, $availablePartners))
            {
                return response()->json(['error'=>true, 'msg'=> '상위에이전트를 찾을수 없습니다.']);
            }
            $validatecomaster = $parent;
            while ($validatecomaster && !$validatecomaster->isInOutPartner())
            {
                $validatecomaster = $validatecomaster->referral;
            }
            $duplicatepatner = \App\Models\User::where('username', $user->username)->whereIn('id', $validatecomaster->availableUsers())->first();
            if(isset($duplicatepatner) && !in_array($user->id, $validatecomaster->availableUsers()))
            {
                return response()->json(['error'=>true, 'msg'=> '상위에이전트에 ' . $user->username . ' 파트너가 이미 존재합니다.']);
            }
            // $deal_percent = $user->deal_percent;
            // $table_deal_percent = $user->tabe_deal_percent;            
            // if ($user->hasRole('manager'))
            // {
            //     $deal_percent = $user->shop->deal_percent;
            //     $table_deal_percent = $user->shop->tabe_deal_percent;            
            // }
            $check_deals = [
                'deal_percent',
                'table_deal_percent',
                'pball_single_percent',
                'pball_comb_percent',
                'sports_deal_percent',
                'card_deal_percent'
            ];
            foreach ($check_deals as $dealtype)
            {
                if ($parent->{$dealtype} < $user->{$dealtype})
                {
                    return redirect()->back()->withErrors(['딜비는 상위에이전트보다 클수 없습니다']);
                }
            }
            

            // if ($deal_percent > $parent->deal_percent || $table_deal_percent > $parent->table_deal_percent)
            // {
            //     return redirect()->back()->withErrors(['롤링은 상위에이전트보다 클수 없습니다.']);
            // }

            if ($user->hasRole('manager'))
            {
                $referral = $user->referral;
                $parent_referral = $parent;
                while ($referral && !$referral->isInOutPartner())
                {
                    $shopUser = \App\Models\ShopUser::where(['user_id' => $referral->id, 'shop_id' => $user->shop->id])->first();
                    if ($shopUser)
                    {
                        $shopUser->update(['user_id' => $parent_referral->id]);
                    }
                    $referral = $referral->referral;
                    $parent_referral = $parent_referral->referral;
                }
                $user->shop->update(['user_id' => $parent->id]);
            }
            else 
            {
                \App\Models\ShopUser::where('user_id' , $user->referral->id)->whereIn('shop_id', $user->availableShops())->update(['user_id' => $parent->id]);
            }

            $user->update(['parent_id' => $parent->id]);
            return response()->json(['error'=>false, 'msg'=> '에이전트를 이동하였습니다.']);
           
        }
        public function agent_child(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            $availablePartners = $user->hierarchyPartners();
            $child_id = $request->id;
            $users = [];
            if (in_array($child_id, $availablePartners))
            {
                $users = \App\Models\User::where('parent_id', $child_id)->where('status', \App\Support\Enum\UserStatus::ACTIVE)->get();
            }

            $parent = $user;
            while ($parent && !$parent->isInOutPartner())
            {
                $parent = $parent->referral;
            }
            $moneyperm = 0;
            if ($user->isInOutPartner())
            {
                $moneyperm = 1;
            }
            else if (isset($parent->sessiondata()['moneyperm']))
            {
                $moneyperm = $parent->sessiondata()['moneyperm'];
            }
            $child_role_id = 0;
            if(count($users) > 0){
                $child_role_id = $users[0]->role_id;
            }
            return view('agent.partials.childs', compact('users', 'child_id','moneyperm', 'child_role_id'));
        }
        public function agent_list(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            if (($request->kind != '' && $request->kind != 2) || $request->role != '')
            {
                 $childPartners = $user->hierarchyPartners();
            }
            else if ($request->status == 2 && $request->role == '')
            {
                 $childPartners = $user->hierarchyPartners();
            }
            else if ($request->kind == '' && $request->role == '' && $request->search != '')
            {
                 $childPartners = $user->hierarchyPartners();
            }
            else
            {
                $childPartners = $user->childPartners();
            }
            $parent = $user;
            while ($parent && !$parent->isInOutPartner())
            {
                $parent = $parent->referral;
            }
            $moneyperm = 0;
            if ($user->isInOutPartner())
            {
                $moneyperm = 1;
            }
            else if (isset($parent->sessiondata()['moneyperm']))
            {
                $moneyperm = $parent->sessiondata()['moneyperm'];
            }
            
            $status = [];
            if($request->status == 1){
                $status = [\App\Support\Enum\UserStatus::ACTIVE];
            }else if($request->status == 2){
                $status = [\App\Support\Enum\UserStatus::BANNED];
            }else{
                $status = [\App\Support\Enum\UserStatus::ACTIVE, \App\Support\Enum\UserStatus::BANNED];
            }
            
            $users = \App\Models\User::select('users.*')->whereIn('users.id', $childPartners)->whereIn('users.status', $status);
            $searchName = 'username';
            $searchValue = '';
            if($request->search != ''){
                $searchValue = $request->search;
            }
            if($request->kind != ''){
                if($request->kind == 1){
                    $searchName = 'id';
                }else if($request->kind == 2){
                    $searchName = 'balance';
                }else if($request->kind == 4){
                    $searchName = 'recommender';
                }else if($request->kind == 5){
                    $searchName = 'phone';
                }
            }
            $order = 'asc';
            if($request->order == ''){
                $order = 'desc';
            }
            if($searchName != '' && $searchValue != ''){
                if($searchName == 'id' || $searchName == 'balance'){
                    $users = $users->where($searchName,  $searchValue);
                }else{
                    $users = $users->where($searchName, 'like', '%' . $searchValue . '%');
                }
            }
            if($request->kind == 2)
            {
                if($request->role == 3)
                {
                    $users = $users->join('shops', 'shops.id', '=', 'users.shop_id');
                    $users = $users->orderby('shops.balance', $order);
                }
                else
                {
                    $users = $users->orderby('users.balance', $order);
                }
            }
            if($request->role != '')
            {
                $users = $users->orderby($searchName, $order);    
                $users = $users->where('users.role_id', $request->role);
            }
            if ($request->join != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->join[0]);
                $end_date = preg_replace('/T/',' ', $request->join[1]);
                $users = $users->where('created_at', '>', $start_date)->where('created_at', '<', $end_date);
            }
            
            $usersum = (clone $users)->get();
            $childsum = 0;
            $balancesum = 0;
            $count = 0;
            foreach ($usersum as $u)
            {
                $childsum = $childsum + $u->childBalanceSum();
                if ($u->role_id > 3)
                {
                    $count = $count + count($u->hierarchyPartners());
                    $balancesum = $balancesum + $u->balance;
                }
                else if ($u->role_id==3)
                {
                    $balancesum = $balancesum + $u->shop->balance;
                }
            }

            $total = [
                'count' => $users->count() + $count,
                'balance' => $balancesum,
                'childbalance' => $childsum
            ];
            $users = $users->paginate(20);
            return view('agent.list', compact('users','total', 'moneyperm'));
        }
        public function agent_eachlist(\Illuminate\Http\Request $request)
        {
            if($request->parent_id != ''){
                $user= \App\Models\User::where('id', $request->parent_id)->first();
            }else{
                $user = auth()->user();
            }
            $childPartners = $user->hierarchyPartners();

            $parent = auth()->user();
            while ($parent && !$parent->isInOutPartner())
            {
                $parent = $parent->referral;
            }
            $moneyperm = 0;
            if ($user->isInOutPartner())
            {
                $moneyperm = 1;
            }
            else if (isset($parent->sessiondata()['moneyperm']))
            {
                $moneyperm = $parent->sessiondata()['moneyperm'];
            }
            
            $status = [];
            if($request->status == 1){
                $status = [\App\Support\Enum\UserStatus::ACTIVE];
            }else if($request->status == 2){
                $status = [\App\Support\Enum\UserStatus::BANNED];
            }else{
                $status = [\App\Support\Enum\UserStatus::ACTIVE, \App\Support\Enum\UserStatus::BANNED];
            }
            if($request->role != '')
                $page_role = $request->role;
            else
                $page_role = auth()->user()->role_id - 1;

            $users = \App\Models\User::select('users.*')->whereIn('users.id', $childPartners)->where('users.role_id', $page_role)->whereIn('users.status', $status);
            $searchName = 'username';
            $searchValue = '';
            if($request->search != ''){
                $searchValue = $request->search;
            }
            if($request->kind != ''){
                if($request->kind == 1){
                    $searchName = 'id';
                }else if($request->kind == 2){
                    $searchName = 'balance';
                }else if($request->kind == 4){
                    $searchName = 'recommender';
                }else if($request->kind == 5){
                    $searchName = 'phone';
                }
            }
            $order = 'asc';
            if($request->order == ''){
                $order = 'desc';
            }
            if($searchName != '' && $searchValue != ''){
                if($searchName == 'id' || $searchName == 'balance'){
                    $users = $users->where($searchName,  $searchValue);
                }else{
                    $users = $users->where($searchName, 'like', '%' . $searchValue . '%');
                }
            }
            if($request->kind == 2)
            {
                if($page_role == 3)
                {
                    $users = $users->join('shops', 'shops.id', '=', 'users.shop_id');
                    $users = $users->orderby('shops.balance', $order);
                }
                else
                {
                    $users = $users->orderby('users.balance', $order);
                }
            }
            if ($request->join != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->join[0]);
                $end_date = preg_replace('/T/',' ', $request->join[1]);
                $users = $users->where('created_at', '>', $start_date)->where('created_at', '<', $end_date);
            }
            
            $usersum = (clone $users)->get();
            $childsum = 0;
            $balancesum = 0;
            $count = 0;
            foreach ($usersum as $u)
            {
                $childsum = $childsum + $u->childBalanceSum();
                if ($u->role_id > 3)
                {
                    $balancesum = $balancesum + $u->balance;
                }
                else if ($u->role_id==3)
                {
                    $balancesum = $balancesum + $u->shop->balance;
                }
            }

            $total = [
                'count' => $users->count() + $count,
                'balance' => $balancesum,
            ];
            $users = $users->paginate(20);
            $roledes = trans(\App\Models\Role::where('level', $page_role)->first()->name);
            $deluser = 0; //default 0, disable deluser
            if (auth()->user()->isInOutPartner())
            {
                $deluser = 1;
            }
            else if (isset($parent->sessiondata()['deluser']))
            {
                $deluser = $parent->sessiondata()['deluser'];
            }
            return view('agent.elist', compact('users','total', 'moneyperm', 'deluser', 'roledes', 'page_role'));
        }
        public function player_list(\Illuminate\Http\Request $request)
        {
            set_time_limit(0);
            $user = auth()->user();
            $availableUsers = $user->hierarchyUsersOnly();
            $parent = $user;
            while ($parent && !$parent->isInOutPartner())
            {
                $parent = $parent->referral;
            }
            $moneyperm = 0;
            if ($user->isInOutPartner() || $user->hasRole('manager'))
            {
                $moneyperm = 1;
            }
            else if (isset($parent->sessiondata()['moneyperm']))
            {
                $moneyperm = $parent->sessiondata()['moneyperm'];
            }
            $status = [];
            if($request->status == 1){
                $status = [\App\Support\Enum\UserStatus::ACTIVE];
            }else if($request->status == 2){
                $status = [\App\Support\Enum\UserStatus::BANNED];
            }else{
                $status = [\App\Support\Enum\UserStatus::ACTIVE, \App\Support\Enum\UserStatus::BANNED];
            }
            $users = \App\Models\User::whereIn('id', $availableUsers)->whereIn('status', $status);

            $searchName = 'username';
            $searchValue = '';
            if($request->search != ''){
                $searchValue = $request->search;
            }
            if($request->kind != ''){
                if($request->kind == 1){
                    $searchName = 'id';
                }else if($request->kind == 2){
                    $searchName = 'balance';
                }else if($request->kind == 4){
                    $searchName = 'recommender';
                }else if($request->kind == 5){
                    $searchName = 'phone';
                }
            }
            $order = 'asc';
            if($request->order == ''){
                $order = 'desc';
            }
            if($request->kind == 3){
                if ($request->kind != '' && $searchValue != '')
                {
                    $shops = \App\Models\Shop::where('name', 'like', '%'.$searchValue.'%')->whereIn('id', auth()->user()->availableShops())->pluck('id')->toArray();
                    if (count($shops) > 0){
                        $users = $users->whereIn('shop_id',$shops);
                    }
                    else
                    {
                        $users = $users->where('shop_id',-1); //show nothing
                    }
                }
            }else{
                if($searchName != '' && $searchValue != ''){
                    if($searchName == 'id' || $searchName == 'balance'){
                        $users = $users->where($searchName,  $searchValue);
                    }else{
                        $users = $users->where($searchName, 'like', '%' . $searchValue . '%');
                    }
                }
            }
            $users = $users->orderby($searchName, $order);

            if ($request->join != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->join[0]);
                $end_date = preg_replace('/T/',' ', $request->join[1]);
                $users = $users->where('created_at', '>', $start_date)->where('created_at', '<=', $end_date);
            }

            $today = date('Y-m-d 0:0:0');
            $newusers = (clone $users)->where('created_at', '>', $today)->get();

            $totalusercount = $users->count();

            $total = [
                'count' => $totalusercount,
                'balance' => $users->sum('balance'),
                'new' => count($newusers)
            ];
            $deluser = 0; //default 0, disable deluser
            if (auth()->user()->isInOutPartner())
            {
                $deluser = 1;
            }
            else if (isset($parent->sessiondata()['deluser']))
            {
                $deluser = $parent->sessiondata()['deluser'];
            }
            $users = $users->paginate(20);
            $joinusers = \App\Models\User::orderBy('username', 'ASC')->where(['status' => \App\Support\Enum\UserStatus::JOIN, 'role_id' => 1])->whereIn('users.id', $availableUsers)->get();

            $confirmusers = \App\Models\User::orderBy('username', 'ASC')->where(['status' => \App\Support\Enum\UserStatus::UNCONFIRMED, 'role_id' => 1])->whereIn('users.id', $availableUsers)->get();
            return view('player.list', compact('users','total','moneyperm', 'deluser', 'joinusers', 'confirmusers'));
        }
        public function player_connected_list(\Illuminate\Http\Request $request)
        {
            set_time_limit(0);
            $user = auth()->user();
            $availableUsers = $user->hierarchyUsersOnly();
            
            $users = \App\Models\User::whereIn('id', $availableUsers)->whereIn('status', [\App\Support\Enum\UserStatus::ACTIVE, \App\Support\Enum\UserStatus::BANNED]);
            $usersId = (clone $users)->pluck('id')->toArray();
            $validTimestamp = \Carbon\Carbon::now()->subMinutes(config('session.lifetime'))->timestamp;
            $validTime = date('Y-m-d H:i:s', strtotime("-5 minutes"));
            $onlineUsers = \App\Models\Session::whereIn('user_id', $usersId)->where('last_activity', '>=', $validTimestamp)->pluck('user_id')->toArray();
            $onlineUserByGame = \App\Models\StatGame::whereIn('user_id', $usersId)->where('date_time', '>=', $validTime)->pluck('user_id')->toArray();
            
            $onlineUsers = array_unique($onlineUsers);
            $onlineUserByGame = array_unique($onlineUserByGame);
            
            $onlineUsers = array_merge_recursive($onlineUsers, $onlineUserByGame);
            $users = $users->whereIn('id',$onlineUsers);
            $users = $users->paginate(20);
            return view('player.connectedlist', compact('users'));
        }
        public function player_noconnect(\Illuminate\Http\Request $request)
        {
            set_time_limit(0);
            $user = auth()->user();
            $availableUsers = $user->hierarchyUsersOnly();
            
            $users = \App\Models\User::whereIn('id', $availableUsers)->whereIn('status', [\App\Support\Enum\UserStatus::ACTIVE, \App\Support\Enum\UserStatus::BANNED]);
            $diffday = 90;
            if(isset($request->diffday)){
                $diffday = $request->diffday;
            }
            if($diffday == -1){
                $users = $users->whereRaw('last_login IS NULL');
            }else{
                $search_date = date('Y-m-d 0:0:0',  strtotime('-'. $diffday .' days'));
                $users = $users->whereRaw('(last_login<"'. $search_date .'" OR (last_login IS NULL AND created_at<"'.$search_date.'"))');
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
            $users = $users->paginate(20);
            return view('player.noconnectlist', compact('users', 'deluser'));
        }
        public function player_join(\Illuminate\Http\Request $request)
        {
            if (!auth()->user()->isInoutPartner())
            {
                return redirect()->back()->withErrors(['권한이 없습니다.']);
            }

            $user = \App\Models\User::whereIn('status', [\App\Support\Enum\UserStatus::JOIN, \App\Support\Enum\UserStatus::UNCONFIRMED])->where('id', $request->id)->first();
            if (!$user)
            {
                return redirect()->back()->withErrors(['잘못된 요청입니다.']);
            }
            if ($request->type == 'allow')
            {
                $user->update(['status' => \App\Support\Enum\UserStatus::ACTIVE]);
                $onlineShop = \App\Models\OnlineShop::where('shop_id', $user->shop_id)->first();

                if ($onlineShop) //it is online users
                {
                    $admin = \App\Models\User::where('role_id', 9)->first();
                    $user->addBalance('add',$onlineShop->join_bonus, $admin);
                }
            }
            else if ($request->type == 'stand')
            {
                $user->update(['status' => \App\Support\Enum\UserStatus::UNCONFIRMED]);
            }
            else
            {
                $user->update(['status' => \App\Support\Enum\UserStatus::REJECTED]);
            }
            return redirect()->back()->withSuccess(['가입신청을 처리했습니다']);
        }
        public function player_terminate(\Illuminate\Http\Request $request)
        {
            $userid = $request->id;
            $availableUsers = auth()->user()->hierarchyUsersOnly();
            if (!in_array($userid, $availableUsers))
            {
                return redirect()->back()->withErrors([trans('UnauthorizedOperating')]);
            }
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                return redirect()->back()->withErrors(['플레이어를 찾을수 없습니다.']);
            }
            //대기중의 게임입장큐 삭제
            \App\Models\GameLaunch::where('user_id', $user->id)->delete(); // where('finished', 0)->
            $b = $user->withdrawAll('playerterminate');
            if (!$b)
            {
                return redirect()->back()->withSuccess(['게임종료시 오류가 발생했습니다.']);
            }
            $user->update(['api_token' => 'playerterminate']);
            event(new \App\Events\User\TerminatedByAdmin($user));

            return redirect()->back()->withSuccess(['플레이어의 게임을 종료하였습니다']);
        }

        public function player_logout(\Illuminate\Http\Request $request,  \App\Repositories\Session\SessionRepository $sessionRepository)
        {
            $userid = $request->id;
            $availableUsers = auth()->user()->hierarchyUsersOnly();
            if (!in_array($userid, $availableUsers))
            {
                return redirect()->back()->withErrors([trans('UnauthorizedOperating')]);
            }
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                return redirect()->back()->withErrors(['플레이어를 찾을수 없습니다.']);
            }
            \App\Models\GameLaunch::where('finished', 0)->where('user_id', $user->id)->delete();
            $b = $user->withdrawAll('playerlogout');

            $user->update(['api_token' => null]);

            $sessionRepository->invalidateAllSessionsForUser($user->id);

            event(new \App\Events\User\TerminatedByAdmin($user));

            return redirect()->back()->withSuccess(['플레이어를 로그아웃시켰습니다.']);
        }
        

        public function player_transaction(\Illuminate\Http\Request $request)
        {
            $statistics = \App\Models\Transaction::select('transactions.*')->orderBy('transactions.created_at', 'DESC');
            $user = auth()->user();
            $availableUsers = $user->hierarchyUsersOnly();
            $statistics = $statistics->whereIn('user_id', $availableUsers);

            $start_date = date("Y-m-1 0:0:0");
            $end_date = date("Y-m-d 23:59:59");

            if ($request->dates != '')
            {
                // $dates = explode(' - ', $request->dates);
                $start_date = preg_replace('/T/',' ', $request->dates[0]);
                $end_date = preg_replace('/T/',' ', $request->dates[1]);            
            }
            $statistics = $statistics->where('transactions.created_at', '>=', $start_date);
            $statistics = $statistics->where('transactions.created_at', '<=', $end_date );

            $statistics = $statistics->join('users', 'users.id', '=', 'transactions.user_id');

            if ($request->role != '')
            {
                $statistics = $statistics->where('users.role_id', $request->role);
            }
            if ($request->user != '')
            {
                $statistics = $statistics->where('users.username', 'like', '%' . $request->user . '%');
            }

            if ($request->admin != '')
            {
                $payeerIds = \App\Models\User::where('username', 'like', '%' . $request->admin . '%')->pluck('id')->toArray();
                $statistics = $statistics->whereIn('transactions.payeer_id', $payeerIds);
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
            return view('player.transaction', compact('statistics', 'total'));
        }
        public function player_create(\Illuminate\Http\Request $request)
        {
            return view('player.create');
        }

        public function player_store(\App\Http\Requests\User\CreateUserRequest $request)
        {
            $data = $request->all() + ['status' => \App\Support\Enum\UserStatus::ACTIVE];

            $availablePartners = auth()->user()->hierarchyPartners();
            $availablePartners[] = auth()->user()->id;
            $parent = \App\Models\User::where(['username' => $request->parent, 'role_id' => 3])->whereIn('id', $availablePartners)->first();
            if (!$parent)
            {
                return redirect()->back()->withErrors([$request->parent . '매장을 찾을수 없습니다']);
            }
            $validatecomaster = $parent;
            while ($validatecomaster && !$validatecomaster->isInOutPartner())
            {
                $validatecomaster = $validatecomaster->referral;
            }
            $validateuser = \App\Models\User::where(['username' => $request->username])->whereIn('id', $validatecomaster->availableUsers())->first();
            if(isset($validateuser))
            {
                return redirect()->back()->withErrors([$request->username . '유저가 이미 존재합니다']);
            }
            $role = \jeremykenedy\LaravelRoles\Models\Role::find(1);
            $data['parent_id'] = $parent->id;

            $shop = $parent->shop;
            $check_deals = [
                'deal_percent',
                'table_deal_percent',
                'pball_single_percent',
                'pball_comb_percent',
                'sports_deal_percent',
                'card_deal_percent'
            ];
            foreach ($check_deals as $dealtype)
            {
                if (isset($data[$dealtype]) && $shop!=null &&  $shop->{$dealtype} < $data[$dealtype])
                {
                    return redirect()->back()->withErrors(['딜비는 매장보다 클수 없습니다']);
                }
            }

            $data['shop_id'] = $shop->id;
            $data['role_id'] = $role->id;

            $comaster = auth()->user();
            while ($comaster && !$comaster->isInOutPartner())
            {
                $comaster = $comaster->referral;
            }
            $manualjoin = 1; //default 0
            if (auth()->user()->isInOutPartner())
            {
                $manualjoin = 0;
            }
            else if (isset($comaster->sessiondata()['manualjoin']))
            {
                $manualjoin = $comaster->sessiondata()['manualjoin'];
            }

            if ($manualjoin == 1)
            {
                $data['status'] = \App\Support\Enum\UserStatus::JOIN;
            }

            $user = \App\Models\User::create($data);
            $user->detachAllRoles();
            $user->attachRole($role);
            event(new \App\Events\User\Created($user));

            \App\Models\ShopUser::create([
                'shop_id' => $shop->id, 
                'user_id' => $user->id
            ]);
            if ($manualjoin == 1)
            {
                return redirect()->back()->withSuccess(['플레이어생성이 신청되었습니다. 승인될때까지 기다려주세요']);
            }
            return redirect()->back()->withSuccess(['플레이어가 생성되었습니다']);
        }
        public function player_multicreate(\Illuminate\Http\Request $request)
        {
            return view('modal.multicreate');
        }
        public function player_multistore(\Illuminate\Http\Request $request)
        {
            set_time_limit(0);
            if(!$request->hasFile('uploadFile')){
                return response()->json(['error'=>true, 'msg'=> '업로드 파일 선택해주세요']);
            }else{
                $temp = $request->file('uploadFile')->store('temp');
                $path = storage_path('app').'/'.$temp;
                $results = [];
                $arr_excellist = Excel::toArray(function (array $row){
                    return $row;
                }, $path)[0];
                $userlist = [];
                $parent = null;
                $validatecomaster = null;
                $errUserList = [];
                $comaster = auth()->user();
                while ($comaster && !$comaster->isInOutPartner())
                {
                    $comaster = $comaster->referral;
                }
                $manualjoin = 1; //default 0
                if (auth()->user()->isInOutPartner())
                {
                    $manualjoin = 0;
                }
                else if (isset($comaster->sessiondata()['manualjoin']))
                {
                    $manualjoin = $comaster->sessiondata()['manualjoin'];
                }
                $role = \jeremykenedy\LaravelRoles\Models\Role::find(1);
                foreach ($arr_excellist as $index => $val) {
                    if($index > 0){
                        $start_index = 0;
                        $end_index = 0;
                        $ismulti = false;
                        if(!empty($val[2]))
                        {
                            $numbers = explode('~', $val[2]);
                            if(count($numbers) == 2 && is_numeric($numbers[0]) && is_numeric($numbers[1]))
                            {
                                $start_index = $numbers[0];
                                $end_index = $numbers[1];
                                $ismulti = true;
                            }
                        }
                        if(!empty($val[0])){
                            $availablePartners = auth()->user()->hierarchyPartners();
                            $availablePartners[] = auth()->user()->id;
                            $parent = \App\Models\User::where(['username' => $val[0], 'role_id' => 3])->whereIn('id', $availablePartners)->first();
                            if (!$parent)
                            {
                                $errUserList[] = $val[1];
                                continue;
                            }
                            $validatecomaster = $parent;
                            while ($validatecomaster && !$validatecomaster->isInOutPartner())
                            {
                                $validatecomaster = $validatecomaster->referral;
                            }
                        }
                        for($i = $start_index; $i <= $end_index; $i++)
                        {
                            $newusername = $val[1];
                            if($ismulti == true)
                            {
                                $newusername = $newusername . $i;
                            }
                            if(empty($newusername) || empty($val[3]) || empty($val[4])){
                                $errUserList[] = $newusername;
                                continue;
                            }
                            $validateuser = \App\Models\User::where(['username' => $newusername])->whereIn('id', $validatecomaster->availableUsers())->first();
                            if(isset($validateuser))
                            {
                                $errUserList[] = $newusername;
                                continue;
                            }
                            $data = [
                                'username' => $newusername,
                                'password' => $val[3],
                                'parent_id' => $parent->id,
                                'shop_id' => $parent->shop->id,
                                'role_id' => $role->id,
                                'phone' => isset($val[5]) ? $val[5] : '',
                                'bank_name' => isset($val[6]) ? $val[6] : '',
                                'recommender' => isset($val[7]) ? $val[7] : '',
                                'account_no' => isset($val[8]) ? $val[8] : ''
                            ];
                            if($manualjoin == 1){
                                $data['status'] = \App\Support\Enum\UserStatus::JOIN;
                            }else{
                                $data['status'] = \App\Support\Enum\UserStatus::ACTIVE;
                            }
                            $user = \App\Models\User::create($data);
                            $user->detachAllRoles();
                            $user->attachRole($role);
                            event(new \App\Events\User\Created($user));
                
                            \App\Models\ShopUser::create([
                                'shop_id' => $parent->shop->id, 
                                'user_id' => $user->id
                            ]);
                        }
                    }
                }
            }
            return response()->json(['error'=>false, 'err_users' => $errUserList]);
        }
        public function player_refresh(\Illuminate\Http\Request $request)
        {
            $userid = $request->id;
            $dealid = $request->deal;
            if(!isset($dealid)){
                $dealid = 0;
            }
            $availableUsers = auth()->user()->hierarchyUsersOnly();
            if (!in_array($userid, $availableUsers))
            {
                return response()->json(['error'=>true, 'msg'=> trans('Could not find user')]);
            }
            $user = \App\Models\User::where('id', $userid)->first();
            if (!$user)
            {
                return response()->json(['error'=>true, 'msg'=> trans('Could not find user')]);
            }
            if($dealid > 0){
                return response()->json(['error'=>false, 'balance'=> number_format($user->deal_balance - $user->mileage, 2)]);
            }
            //게임사 연동 위해 대기중이면 머니동기화 하지 않기
            $launchRequests = \App\Models\GameLaunch::where('finished', 0)->where('user_id', $user->id)->get();
            if (count($launchRequests) > 0)
            {
                return response()->json(['error'=>false, 'balance'=> number_format($user->balance, 2)]);
            }
            $balance = \App\Models\User::syncBalance($user, 'playerrefresh');
            if ($balance < 0)
            {
                return response()->json(['error'=>true, 'msg'=> '게임사머니 연동오류']);
            }
            else
            {
                return response()->json(['error'=>false, 'balance'=> number_format($balance, 2)]);
            }        
        }
        public function checkId(\Illuminate\Http\Request $request)
        {
            $comaster = auth()->user();
            while ($comaster && !$comaster->isInOutPartner())
            {
                $comaster = $comaster->referral;
            }
            if ($request->id){
                $user = \App\Models\User::where('username',$request->id);
                if(isset($request->role_id) && $request->role_id < 7)   
                {
                    $user = $user->whereIn('id', $comaster->availableUsers());
                }
                $user = $user->get();
                if (count($user) > 0)
                {
                    return response()->json(['error' => false, 'ok' => 0]);
                }
                else{
                    return response()->json(['error' => false, 'ok' => 1]);
                }
            }
            return response()->json(['error' => true]);
        }
        public function activeUser(\Illuminate\Http\Request $request){
            $ids = $request->ids;
            $status = $request->status;
            $statusValue = \App\Support\Enum\UserStatus::ACTIVE;
            if($status == 1){
                $statusValue = \App\Support\Enum\UserStatus::BANNED;
            }
            \App\Models\User::whereIn('id', $ids)->update(['status' => $statusValue]);
            if($status == 1){
                return response()->json(['error'=>false, 'msg'=> '선택된 유저들이 차단되었습니다.']);
            }else{                
                return response()->json(['error'=>false, 'msg'=> '선택된 유저들이 활성화되었습니다.']);
            }
        }
        public function deleteUser(\Illuminate\Http\Request $request)
        {
            $ids = $request->ids;
            $hard = $request->hard;

            $availableUsers = auth()->user()->availableUsers();
            foreach($ids as $id){

                $user = \App\Models\User::where('id', $id)->first();

                if (!$user || !in_array($id, $availableUsers))
                {
                    return response()->json(['error'=>true, 'msg'=> '[' . $user->username .']'. trans('Could not find user')]);
                }
    
                if( $user->id == \Illuminate\Support\Facades\Auth::id() ) 
                {
                    return response()->json(['error'=>true, 'msg'=> '[' . $user->username .']' . trans('app.you_cannot_delete_yourself')]);
                }
    
                if ($hard != '1')
                {
                    if( $user->balance > 0 || ($user->hasRole('manager') && $user->shop->balance > 0)) 
                    {
                        return response()->json(['error'=>true, 'msg'=> '[' . $user->username .'] 유저의 보유금이 0이 아닙니다.']);
                    }
    
                    if ($user->childBalanceSum() > 0)
                    {
                        return response()->json(['error'=>true, 'msg'=> '[' . $user->username .'] 하부 보유금이 0이 아닙니다']);
                    }
                }
                else if (!auth()->user()->hasRole('admin'))
                {
                    return response()->json(['error'=>true, 'msg'=> '허용되지 않은 접근입니다']);
                }
                
                if ($user->hasRole('user')) 
                {
                    \App\Models\Task::create([
                        'user_id' => auth()->user()->id,
                        'category' => 'user', 
                        'action' => 'delete', 
                        'item_id' => $user->id
                    ]);
                    $user->update(['status' => \App\Support\Enum\UserStatus::DELETED]);
                }
                else
                {
    
                    $childUsers = $user->availableUsers();
                    \App\Models\User::whereIn('id', $childUsers)->update(['status' => \App\Support\Enum\UserStatus::DELETED]);
    
                    foreach ($childUsers as $cid){
                        if ($cid != 0){
                            \App\Models\Task::create([
                                'user_id' => auth()->user()->id,
                                'category' => 'user', 
                                'action' => 'delete', 
                                'item_id' => $cid
                            ]);
                        }
                    }
                    $shop_ids = $user->availableShops();
                    \App\Models\Shop::whereIn('id', $shop_ids)->update(['pending'=>2]);
    
                    foreach ($shop_ids as $shop){
                        if ($shop != 0){
                            \App\Models\Task::create([
                                'user_id' => auth()->user()->id,
                                'category' => 'shop', 
                                'action' => 'delete', 
                                'item_id' => $shop
                            ]);
                            $shopInfo = \App\Models\Shop::where('id', $shop)->first();
                            event(new \App\Events\Shop\ShopDeleted($shopInfo));
                        }
                    }
                }
    
                event(new \App\Events\User\Deleted($user));
            }
            return response()->json(['error'=>false, 'msg'=> '선택된 유저들이 삭제되었습니다.']);
        }
        public function exportCSV(\Illuminate\Http\Request $request)
        {
            set_time_limit(0);
            $fileName = '유저목록.csv';
            $headers = array(
                "Content-type"        => "text/csv;charset=UTF-8",
                "Content-Disposition" => "attachment; filename=$fileName",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0",
                "Content-Transfer-Encoding" => "binary"
            );

            $columns = array('번호', '이름', '상위유저', '등급', '폰번호', '은행이름', '예금주', '계좌번호', '보유금');
            $currentUser = auth()->user();
            if($currentUser == null){
                return redirect()->back()->withErrors(trans('app.logout'));
            }else if($currentUser->isInOutPartner() == false){
                return redirect()->back()->withErrors(trans('UnauthorizedOperating'));
            }
            $currentUser->export_csvUserList = [];
            $userlist = $currentUser->getCSVUserList($currentUser->id);
            $callback = function() use($userlist, $columns) {
                echo "\xEF\xBB\xBF"; 
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);

                foreach ($userlist as $sub_user) {
                    fputcsv($file, array($sub_user['id'], $sub_user['username'], $sub_user['parent_id'], $sub_user['role_id'], $sub_user['phone'], $sub_user['bank_name'], $sub_user['recommender'], $sub_user['account_no'], $sub_user['balance']));
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }


        public function player_game_detail(\Illuminate\Http\Request $request)
        {
            $statid = $request->statid;
            $statgame = \App\Models\StatGame::where('id', $statid)->first();
            if (!$statgame)
            {
                abort(404);
            }
            $ct = $statgame->category;
            $res = null;
            
            if ($ct->provider != null)
            {
                if (method_exists('\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($ct->provider) . 'Controller','getgamedetail'))
                {
                    $res = call_user_func('\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($ct->provider) . 'Controller::getgamedetail', $statgame);
                    if(($ct->provider == 'nexus' || $ct->provider == 'rg') && $res != null){
                        return redirect($res);
                    }
                }
                else
                {
                    
                }
            }
            else //local game
            {
                $game = $statgame->game_item;
                if ($game)
                {
                    //check game category
                    $gameCats = $game->categories;
                    $object = '\App\Games\\' . $game->name . '\Server';
                    $isMini = false;
                    foreach ($gameCats as $gcat)
                    {
                        if ($gcat->category->href == 'pragmatic') //pragmatic game history
                        {
                            if ($statgame->user)
                            {
                                return redirect(config('app.mainserver') . '/gs2c/lastGameHistory.do?symbol='.$game->label.'&token='.$statgame->user->id.'-'.$statgame->id);
                            }
                        }
                        else if ($gcat->category->href == 'bngplay') // booongo game history
                        {
                            if ($statgame->user)
                            {
                                return redirect(config('app.mainserver') . "/op/major/history.html?session_id=68939e9a5d134e78bfd9993d4a2cc34e#player_id=".$statgame->user->id."&brand=*&show=transactions&game_id=".$statgame->game_id."&tz=0&start_date=&end_date=&per_page=100&round_id=".$statgame->roundid."&currency=KRW&mode=REAL&report_type=GGR&header=0&totals=1&info=0&exceeds=0&lang=ko");
                            }
                        }
                        else if ($gcat->category->href == 'minigame')
                        {
                            $object = '\App\Http\Controllers\GameParsers\PowerBall\\' . $game->name;
                            $isMini = true;
                            break;        
                        }
                    }

                    if (!class_exists($object))
                    {
                        abort(404);
                    }
                    if ($isMini)
                    {
                        $gameObject = new $object($game->id);
                    }else{
                        $gameObject = new $object();
                    }
                    
                    if (method_exists($gameObject, 'gameDetail'))
                    {
                        $res = $gameObject->gameDetail($statgame);
                    }
                    else
                    {
                        
                    }
                }
                else
                {
                    abort(404);
                }

            }
            return view('log.sharegame.partials.gamedetail', compact('res'));
        }


        public function player_game_pending(\Illuminate\Http\Request $request)
        {
            $user = auth()->user();
            $availableUsers = $user->hierarchyUsersOnly();
            if (count($availableUsers) == 0)
            {
                return redirect()->back()->withErrors(['유저가 없습니다']);
            }


            $statistics = \App\Models\GACTransaction::whereIn('user_id', $availableUsers)->where(['gactransaction.type'=>1,'gactransaction.status'=>0])->orderBy('date_time', 'ASC');


            $statistics = $statistics->paginate(20);


            return view('log.playerpending.list', compact('statistics'));
        }

        public function player_game_process(\Illuminate\Http\Request $request)
        {
            $gacid = $request->id;
            if (!auth()->user()->hasRole('admin'))
            {
                return redirect()->back()->withErrors(['허용되지 않은 접근입니다.']);
            }

            $result = \App\Http\Controllers\GameProviders\GACController::processResult($gacid);
            if ($result['error'] == false)
            {
                return redirect()->back()->withSuccess([$result['win'] . '원의 당첨금 결과처리되었습니다']);
            }
            else
            {
                \App\Http\Controllers\GameProviders\GACController::cancelResult($gacid);

                return redirect()->back()->withSuccess(['취소처리 되었습니다']);
            }
        }
    }

}
