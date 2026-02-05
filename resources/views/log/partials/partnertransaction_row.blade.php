
<tr>
<td class="text-center">{{$stat->id}}</td>
<?php 
    $badge_class = \App\Models\User::badgeclass();
?>
<td class="text-center">
    @if ($stat->user)
    <a href="javascript:;" onclick="userClick('{{$stat->user->username}}',true)" data-toggle="tooltip" data-original-title="{{$stat->user->parents(auth()->user()->role_id)}}">
         <span class="badge {{$badge_class[$stat->user->role_id]}} rounded-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$stat->user->parents(auth()->user()->role_id, $stat->user->role_id+1)!!}">{{$stat->user->username}}&nbsp;&nbsp;[{{__($stat->user->role->name)}}]</span>
    </a>
    @else
        {{__('Unknown')}}
    @endif
</td>
<td class="text-center">
    @if ($stat->admin)
    <a href="javascript:;" onclick="userClick('{{$stat->admin->username}}',false)"><span class="badge {{$badge_class[$stat->admin->role_id]}} rounded-0">{{$stat->admin->username}}&nbsp;&nbsp;[{{__($stat->admin->role->name)}}]</span></a>
    @else
    {{__('Unknown')}}
    @endif
</td>
<td class="text-center">
    @if ($stat->admin && auth()->user()->role_id >= $stat->admin->role_id)
        {{number_format($stat->balance, 2)}}
    @else
        0
    @endif
</td>
<td class="text-center">{{number_format($stat->old, 2)}}</td>
<td class="text-center">{{number_format($stat->new, 2)}}</td>
@if($stat->type == 'add')
<td class="text-center"><span class="text-success">{{number_format($stat->summ, 2)}}</span></td>
<td class="text-center"><span class="text-success">{{__($stat->type)}}</span></td>
@else
<td class="text-center"><span class="text-warning">{{number_format($stat->summ, 2)}}</span></td>
<td class="text-center"><span class="text-warning">{{__($stat->type)}}</span></td>
@endif
<td class="text-center">
@if ($stat->request_id != null)
<a href="#" data-toggle="tooltip" data-original-title="{{$stat->requestInfo->bankinfo()}}">
<span class="badge badge-success">{{__('agent.BankAccount')}}</span>
</a>
@else
<span class="badge badge-primary">{{__('agent.Manual')}}</span>
@endif
</td>
<td class="text-center">{{$stat->reason}}</td>
<td class="text-center">{{$stat->created_at}}</td>
</tr>