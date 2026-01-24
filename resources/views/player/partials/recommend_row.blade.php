<td>{{$user->username}}</td>
<td><span class="btn btn-primary  px-2 py-1 rounded-0 my-0" style="background-color: #20c997;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$user->parents(auth()->user()->role_id, $user->role_id+1)!!}">{{$user->referral->username}}</span></td>
<td>
    {{$user->recommender}}
    <hr class='my-0'>
    {{$user->account_no}}
    <hr class='my-0'>
    {{$user->phone}}
</td>
<td>{{date('Y-m-d H:i', strtotime($user->created_at))}}</td>

<td class="text-right">
    <a href="{{argon_route('player.join', ['id' => $user->id, 'type'=>'allow'])}}"><button class="btn btn-success px-2 py-1 rounded-0 my-0">{{__('agent.Approved')}}</button></a>
@if ($join)
    <a href="{{argon_route('player.join', ['id' => $user->id, 'type'=>'stand'])}}"><button class="btn btn-info  px-2 py-1 rounded-0 my-0">{{__('agent.Pending')}}</button></a>
@endif    
    <a href="{{argon_route('player.join', ['id' => $user->id, 'type'=>'reject'])}}"><button class="btn btn-warning  px-2 py-1 rounded-0 my-0">{{__('agent.Cancel')}}</button></a>
</td>