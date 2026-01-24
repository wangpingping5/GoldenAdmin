<td><input type="checkbox" class="Check" name="user_CheckBox"></td>
<td>{{$user->id}}</td>
<td><span class="btn btn-primary m-0 rounded-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$user->parents(auth()->user()->role_id, $user->role_id+1)!!}" style="background-color: #63B3ED;">{{__('User')}}</span></td>
<td><a class="text-primary" href="{{argon_route('common.profile', ['id'=>$user->id])}}">{{$user->username}}</a></td>
<td>{{$user->recommender}}</td>
<td>{{$user->phone}}</td>
<td>{{number_format($user->balance)}}</td>
<td>{{ number_format($user->deal_balance - $user->mileage,0)}}</td>
<td>{{number_format($user->total_in)}}</td>
<td>{{number_format($user->total_out)}}</td>
<td>{{ $user->created_at }}</td>
<td>{{ $user->last_login }}</td>
<td>
    @if($user->status == \App\Support\Enum\UserStatus::BANNED)
    <span class="btn btn-danger px-2 py-1 rounded-0 mb-0">{{$statuses[$user->status]}}</span>
    @else
    <span class="btn btn-primary px-2 py-1 rounded-0 mb-0">{{$statuses[$user->status]}}</span>
    @endif
</td>
<td>
    <a class="dropdown-item" href="javascript:;" onClick="window.open('{{ argon_route('common.message', ['id'=> $user->id]) }}', '{{__("agent.SendMessage")}}','width=800, height=580, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');">
        <span class="btn btn-info px-2 py-1 rounded-0 mb-0">{{__('agent.SendMessage')}}</span>
    </a>
</td>
