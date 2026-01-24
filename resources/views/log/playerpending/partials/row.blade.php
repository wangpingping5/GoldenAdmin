<?php
    $statdata = json_decode($stat->data,true);
    $table = null;
    if (isset($statdata['tableName'])){
        $table = \App\Http\Controllers\GameProviders\GACController::getGameObj($statdata['tableName']);
    }
    if ($table == null)
    {
        $table = \App\Http\Controllers\GameProviders\GACController::getGameObj('unknowntable');
    }
    $badge_class = \App\Models\User::badgeclass();
?>
<td class="text-center">{{$stat->user->username}}</td>
<td class="text-center">
   Evolution
</td>
<td class="text-center">
{{$table['title']}}
</td>
<td class="text-center">
{{number_format(isset($statdata['betAmount'])?$statdata['betAmount']:0)}}
</td>
<td class="text-center">{{ $stat->date_time }}</td>
<td class="text-center">{{__('agent.Pending')}}</td>
@if (auth()->user()->hasRole('admin'))
<td class="text-center">
<a href="{{argon_route('player.processgame', ['id' => $stat->id])}}" class="badge badge-info rounded-9">{{__('agent.Finished')}}</a>
</td>
@endif