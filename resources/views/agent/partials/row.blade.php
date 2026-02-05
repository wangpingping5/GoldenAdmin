@if(isset($isparent))
<td><input type="checkbox" class="Check" name="user_CheckBox"></td>
<td>{{$user->id}}</td>
@endif
<td><a class="text-primary" href="{{argon_route('common.profile', ['id'=>$user->id])}}"><span class="btn {{$badge_class[$user->role_id]}} px-2 py-1 mb-0 rounded-0">[{{__($user->role->name)}}]</span></br>{{$user->username}}</a></td>
<td>
@if ($user->hasRole('manager'))    
<button type="button" class="btn btn-primary mb-0 rounded-0 px-2 py-1" style="background-color: #596CFF;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="--**{{__('Rolling')}}**--&#10;{{__('live')}}: {{$user->shop->table_deal_percent}}%&#10;{{__('slot')}}: {{$user->shop->deal_percent}}%&#10;{{__('sports')}}: {{$user->shop->sports_deal_percent}}%&#10;{{__('BoardGame')}}: {{$user->shop->card_deal_percent}}%&#10;--**{{__('GGR')}}**--&#10;{{__('live')}}: {{$user->shop->table_ggr_percent}}%&#10;{{__('slot')}}: {{$user->shop->ggr_percent}}%" data-html="true">{{__('Rolling')}}</button>
@else
<button type="button" class="btn btn-primary mb-0 rounded-0 px-2 py-1" style="background-color: #596CFF;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="--**{{__('Rolling')}}**--&#10;{{__('live')}}: {{$user->table_deal_percent}}%&#10;{{__('slot')}}: {{$user->deal_percent}}%&#10;{{__('sports')}}: {{$user->sports_deal_percent}}%&#10;{{__('BoardGame')}}: {{$user->card_deal_percent}}%&#10;--**{{__('GGR')}}**--&#10;{{__('live')}}: {{$user->table_ggr_percent}}%&#10;{{__('slot')}}: {{$user->ggr_percent}}%" data-html="true">{{__('Rolling')}}</button>
@endif
</td>
@if(isset($isparent) && $isparent)
<td>
    <?php
        $parents = $user->parents(auth()->user()->role_id, 0, true);
    ?>
    @if (count($parents)>0)
        @foreach($parents as $patner)
            <span class="btn {{$badge_class[$patner['role_id']]}} px-2 py-1 mb-1 mx-1 rounded-0"  data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{$patner['role_name']}}">{{$patner['parent']}}</span></br>
        @endforeach
    @endif
</td>
@endif
<td>
    <div>
        <button class="btn bg-info m-0 rounded-0 text-white dropdown-toggle" type="button" id="dropdownImport" data-bs-toggle="dropdown" aria-expanded="false">
        {{__('agent.Manage')}}
        </button>
        <ul class="dropdown-menu bg-light border-2 border-light" aria-labelledby="dropdownImport" data-popper-placement="left-start">
            @if ($user->status == \App\Support\Enum\UserStatus::ACTIVE)
            <li><a class="dropdown-item" href="javascript:;" onClick="window.open('{{ argon_route('common.balance', ['id'=> $user->id]) }}', '{{__("agent.DW")}}','width=600, height=520, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');"><span class="text-dark">{{__("agent.Balance")}} {{__("agent.DW")}}</span></a></li>
            <li><a class="dropdown-item" href="javascript:;" onClick="window.open('{{ argon_route('common.percent', ['id'=> $user->id]) }}', '{{__("agent.RollingSet")}}','width=600, height=690, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');"><span class="text-dark">{{__("agent.RollingSet")}}</span></a></li>
            <li><a class="dropdown-item" href="javascript:;" onClick="window.open('{{ argon_route('common.message', ['id'=> $user->id]) }}', '{{__("agent.SendMessage")}}','width=800, height=580, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');"><span class="text-dark">{{__("agent.SendMessage")}}</span></a></li>
            @if (auth()->user()->isInoutPartner() && !$user->isInoutPartner())
            <li><a class="dropdown-item" href="{{argon_route('comp.dealconvert', ['user'=>$user->id])}}"><span class="text-dark">{{__("agent.ConvertRolling")}}</span></a><li>  
            @endif
            <li><a class="dropdown-item" href="{{argon_route('partner.dealstat', ['userID'=>'user', 'search' => $user->username])}}"><span class="text-dark">{{__("agent.RollingHistory")}}</span></a><li>  
            <li><a class="dropdown-item" href="{{argon_route('partner.transaction', ['userID'=>'admin', 'search' => $user->username])}}"><span class="text-dark">{{__("agent.DepositHistory")}}</span></a><li> 
            @if (auth()->user()->hasRole('admin'))
            <li><a class="dropdown-item" href="javascript:;"  onClick="window.open('{{ argon_route('patner.move', ['id'=> $user->id]) }}', '{{__("agent.SendMessage")}}','width=800, height=480, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');"><span class="text-dark">{{__("agent.PartnerMove")}}</span></a></li>
            @endif
            <li><a class="dropdown-item" href="{{argon_route('player.logout', ['id' => $user->id])}}" onClick="$(this).css('pointer-events', 'none');"><span class="text-dark">{{__("agent.ForceLogout")}}</span></a></li>
            @endif
        </ul>
    </div>
</td>
@if(auth()->user()->role_id>3)
<td>
    <?php
        $childs = $user->childPartnersNumbers();
    ?>
    @if (count($childs)>0)
        @foreach($childs as $child)
            <a class="" href="{{argon_route('patner.eachlist', ['role' => $child['role_id'], 'parent_id'=>$user->id ])}}">
            <button type="button" class="btn {{$badge_class[$child['role_id']]}} px-1 py-0 mb-1 rounded-0"  data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{$child['role_name']}}" data-html="true">{{$child['count']}}</button>
            </a>
        @endforeach
    @endif
</td>
@endif
<td>{{count($user->hierarchyUsersOnly())}}</td>
<td>
    {{$user->recommender}}
    <hr class='my-0'>
    {{$user->phone}}
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><a href="javascript:;" class="btn btn-primary px-2 py-1 mb-1 rounded-0" id="rfs_{{$user->id}}">{{__("agent.Balance")}}</a></td>
                <td class="text-end w-70" id="uid_{{$user->id}}">{{$user->hasRole('manager') ? number_format($user->shop->balance, 2) : number_format($user->balance, 2)}}</td>
            </tr>
            <tr>
                <td class="text-start"><a href="javascript:;"  class="btn btn-danger px-2 py-1 m-0 rounded-0"  id="drfs_{{$user->id}}">{{__("agent.Rolling")}}</a></td>
                <td class="text-end w-70" id="duid_{{$user->id}}">{{ $user->hasRole('manager') ? number_format($user->shop->deal_balance - $user->shop->mileage, 2) : number_format($user->deal_balance - $user->mileage, 2)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('Deposit')}}</span></td>
                <td class="text-end w-70">{{number_format($user->total_in, 2)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('Withdraw')}}</span></td>
                <td class="text-end w-70">{{number_format($user->total_out, 2)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-0 rounded-0">{{__('Profit')}}</span></td>
                <td class="text-end w-70">{{number_format($user->total_in - $user->total_out, 2)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('Register')}}</span></td>
                <td class="text-end w-90">{{ $user->created_at }}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('LastLogin')}}</span></td>
                <td class="text-end w-90">{{ $user->last_login }}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    @if($user->status == \App\Support\Enum\UserStatus::BANNED)
    <span class="btn btn-danger px-2 py-1 rounded-0 mb-0">{{$statuses[$user->status]}}</span>
    @else
    <span class="btn btn-primary px-2 py-1 rounded-0 mb-0">{{$statuses[$user->status]}}</span>
    @endif
</td>
<td>
@if(!$user->hasRole('manager') && $user->status == \App\Support\Enum\UserStatus::ACTIVE)
<a class="btn {{$badge_class[$user->role_id-1]}} px-2 py-1 mb-1 rounded-0" href="javascript:;" onClick="window.open('{{ argon_route('patner.create', ['role_id'=> $user->role_id-1, 'parent'=>$user->username]) }}', '','width=600, height=640, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');">{{__(\App\Models\Role::find($user->role_id-1)->name)}} {{__('New')}}</a>
@endif
</td>