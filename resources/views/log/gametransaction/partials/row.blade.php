
<tr>
<td class="text-center">{{$stat->id}}</td>
<?php 
    $badge_class = \App\Models\User::badgeclass();
?>
<td class="text-center">
    @if ($stat->user)
    {{--<a href="{{argon_route('common.profile', ['id'=>$stat->user->id])}}" data-toggle="tooltip" data-original-title="{{$stat->user->parents(auth()->user()->role_id)}}">
         <span class="badge {{$badge_class[$stat->user->role_id]}} rounded-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$stat->user->parents(auth()->user()->role_id, $stat->user->role_id+1)!!}">{{$stat->name}}&nbsp;&nbsp;[{{__($stat->user->role->name)}}]</span>
    </a>--}}
    {{$stat->name}}
    @else
        {{__('Unknown')}}
    @endif
</td>
<td class="text-center">
{{number_format($stat->old, 2)}}
</td>
<td class="text-center">
{{number_format($stat->new, 2)}}
</td>
@if($stat->type == 'add')
<td class="text-center"><span class="text-success">{{number_format($stat->sum, 2)}}</span></td>
<td class="text-center"><span class="text-success">{{__($stat->type)}}</span></td>
@else
<td class="text-center"><span class="text-warning">{{number_format($stat->sum, 2)}}</span></td>
<td class="text-center"><span class="text-warning">{{__($stat->type)}}</span></td>
@endif
<td class="text-center">{{$stat->created_at}}</td>
</tr>