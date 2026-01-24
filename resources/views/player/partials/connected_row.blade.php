<td>{{$user->id}}</td>
<td><span class="btn btn-primary m-0 rounded-0" style="background-color: #20c997;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$user->parents(auth()->user()->role_id, $user->role_id+1)!!}">[{{__($user->referral->role->name)}}]{{$user->referral->username}}</span></td>
<td><span class="btn btn-primary m-0 rounded-0"  style="background-color: #63B3ED;">{{__('User')}}</span></td>
<td><a class="text-primary" href="{{argon_route('common.profile', ['id'=>$user->id])}}">{{$user->username}}</a></td>
<td>{{number_format($user->balance)}}</td>
<td>{{ number_format($user->deal_balance - $user->mileage,0)}}</td>
<td>{{$user->lastActivity()->domain}}</td>
<td>
    <a href="javascript:;" onClick="window.open('{{ argon_route('common.message', ['id'=> $user->id]) }}', '{{__('agent.SendMessage')}}','width=800, height=580, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');" class="btn btn-success px-2 py-1 rounded-0 mb-0">
    {{__('agent.SendMessage')}}
    </a>
</td>
<td>
    <a href="{{argon_route('player.gamehistory', ['userID'=>'player', 'search' => $user->username])}}" class="btn btn-primary px-2 py-1 rounded-0 mb-0">{{__('agent.BetHistory')}}
    </a>
</td>
<td>
    <a href="{{argon_route('player.logout', ['id' => $user->id])}}" onClick="$(this).css('pointer-events', 'none');" class="btn btn-danger px-2 py-1 rounded-0 mb-0">
    {{__('agent.ForceLogout')}}
    </a>
</td>

