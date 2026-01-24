<td><input type="checkbox" class="Check" name="user_CheckBox"></td>
<td>{{$user->id}}</td>
<td><button type="button" class="btn btn-primary mb-0 rounded-0 px-2 py-1" style="background-color: #596CFF;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{__('live')}}: {{$user->table_deal_percent}}%&#10;{{__('slot')}}: {{$user->deal_percent}}%&#10;{{__('sports')}}: {{$user->sports_deal_percent}}%&#10;{{__('BoardGame')}}: {{$user->card_deal_percent}}%" data-html="true">{{__('Rolling')}}</button></td>
<td>
    <div>
        <button class="btn bg-info m-0 rounded-0 text-white dropdown-toggle" type="button" id="dropdownImport" data-bs-toggle="dropdown" aria-expanded="false">
        {{__('agent.Manage')}}
        </button>
        <ul class="dropdown-menu bg-light border-2 border-light" aria-labelledby="dropdownImport" data-popper-placement="left-start">
            <li><a class="dropdown-item" href="javascript:;" onClick="window.open('{{ argon_route('common.balance', ['id'=> $user->id]) }}', '{{__("agent.DW")}}','width=600, height=520, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');"><span class="text-dark">{{__("agent.Balance")}} {{__("agent.DW")}}</span></a></li>
            <li><a class="dropdown-item" href="javascript:;" onClick="window.open('{{ argon_route('common.percent', ['id'=> $user->id]) }}', '{{__("agent.RollingSet")}}','width=600, height=690, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');"><span class="text-dark">{{__("agent.RollingSet")}}</span></a></li>
            @if (auth()->user()->isInoutPartner() && !$user->isInoutPartner())
            <li><a class="dropdown-item" href="{{argon_route('comp.dealconvert', ['user'=>$user->id])}}"><span class="text-dark">{{__("agent.ConvertRolling")}}</span></a><li>  
            @endif
            <li><a class="dropdown-item" href="javascript:;" onClick="window.open('{{ argon_route('common.message', ['id'=> $user->id]) }}', '{{__("agent.SendMessage")}}','width=800, height=580, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');"><span class="text-dark">{{__("agent.SendMessage")}}</span></a></li>
            <li><a class="dropdown-item" href="{{argon_route('player.gamehistory', ['userID'=>'player', 'search' => $user->username])}}"><span class="text-dark">{{__("agent.BetHistory")}}</span></a></li>
            <li><a class="dropdown-item" href="{{argon_route('player.transaction', ['userID'=>'user', 'search' => $user->username])}}"><span class="text-dark">{{__("agent.DepositHistory")}}</span></a></li>
            <li><a class="dropdown-item" href="{{argon_route('player.terminate', ['id' => $user->id])}}" data-method="DELETE"
                                data-confirm-title="{{__('agent.Confirm')}}"
                                data-confirm-text="{{__('agent.TerminalGameMsg')}}"
                                data-confirm-delete="{{__('agent.Confirm')}}"
                                data-confirm-cancel="{{__('agent.Cancel')}}"
                                onClick="$(this).css('pointer-events', 'none');"
                                ><span class="text-dark">{{__('agent.TerminalGame')}}</span></a></li>
            <li><a class="dropdown-item" href="{{argon_route('player.logout', ['id' => $user->id])}}" onClick="$(this).css('pointer-events', 'none');"><span class="text-dark">{{__('agent.ForceLogout')}}</span></a></li>
        </ul>
    </div>
</td>
<td><span class="btn btn-primary m-0 rounded-0" style="background-color: #20c997;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$user->parents(auth()->user()->role_id, $user->role_id+1)!!}">[{{__($user->referral->role->name)}}] {{$user->referral->username}}</span></td>
<td><span class="btn btn-primary m-0 rounded-0" style="background-color: #63B3ED;">{{__('User')}}</span></td>
<td><a class="text-primary" href="{{argon_route('common.profile', ['id'=>$user->id])}}">{{$user->username}}</a></td>
<td>
    {{$user->recommender}}
    <hr class='my-0'>
    {{$user->phone}}
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><a href="#" onclick="refreshPlayerBalance({{$user->id}}, 0);return false;" class="btn btn-primary px-2 py-1 mb-1 rounded-0" id="rfs_{{$user->id}}">{{__('agent.Balance')}}{{__('agent.Confirm')}}</a></td>
                <td class="text-end w-70" id="uid_{{$user->id}}">{{number_format($user->balance)}}</td>
            </tr>
            <tr>
                <td class="text-start"><a href="#"  class="btn btn-danger px-2 py-1 m-0 rounded-0" onclick="refreshPlayerBalance({{$user->id}}, 1);return false;"  id="drfs_{{$user->id}}">{{__('agent.Rolling')}}{{__('agent.Confirm')}}</a></td>
                <td class="text-end w-70" id="duid_{{$user->id}}">{{ number_format($user->deal_balance - $user->mileage,0)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.Deposit')}}</span></td>
                <td class="text-end w-70">{{number_format($user->total_in)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('agent.Withdraw')}}</span></td>
                <td class="text-end w-70">{{number_format($user->total_out)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-0 rounded-0">{{__('agent.Profit')}}</span></td>
                <td class="text-end w-70">{{number_format($user->total_in - $user->total_out)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.Register')}}</span></td>
                <td class="text-end w-90">{{ $user->created_at }}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.LastLogin')}}</span></td>
                <td class="text-end w-90">{{ $user->last_login }}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    @if($user->status == \App\Support\Enum\UserStatus::BANNED)
    <span class="btn btn-danger px-2 py-1 rounded-0">{{$statuses[$user->status]}}</span>
    @else
    <span class="btn btn-primary px-2 py-1 rounded-0">{{$statuses[$user->status]}}</span>
    @endif
</td>