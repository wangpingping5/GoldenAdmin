<?php
        $game = preg_replace('/PM/', '_pp', $stat->game);
        $game = preg_replace('/HBN/', '_HBN', $game);
        $game = preg_replace('/CQ9/', '_cq9', $game);
        $game = explode(' ', $game)[0];
		$game = preg_replace('/^_/', '', $game);
        $cc = explode('_', $game);
        if (count($cc) > 1)
        {
            $game = $cc[0];
        }
        if ($gacmerge == 1)
        {
            $game = preg_replace('/포츈스피드바카라/', 'VIP스피드바카라', $game);
        }
        $badge_class = \App\Models\User::badgeclass();
	?>
<tr>
<td class="text-center">{{$stat->id}}</td>
<td class="text-center">
    @if ($stat->partner)
    <a data-toggle="tooltip" data-original-title="{{$stat->partner->parents(auth()->user()->role_id)}}">
        <span class="badge {{$badge_class[$stat->partner->role_id]}} rounded-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$stat->partner->parents(auth()->user()->role_id, $stat->partner->role_id+1)!!}">{{$stat->partner->username}}&nbsp;&nbsp;[{{__($stat->partner->role->name)}}]</span>
    </a>
    @else
    {{__('Unknown')}}
    @endif
</td>
<td class="text-center">
    @if ($stat->user)
    <a href="javascript:;" onclick="userClick('{{$stat->user->username}}');"><span class="badge {{$badge_class[0]}} rounded-0" data-bs-toggle="tooltip">{{$stat->user->username}}</span></a>
    @else
        {{__('Unknown')}}
    @endif
</td>
<td class="text-center"> {{$game}} </td>
<td class="text-center"> {{$stat->category->trans?$stat->category->trans->trans_title:$stat->category->title}}</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.BetAmount')}}</span></td>
                <td class="text-end w-80">{{number_format($stat->bet,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('agent.WinAmount')}}</span></td>
                <td class="text-end w-80">{{number_format($stat->win,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-0 rounded-0">{{__('agent.RollingAmount')}}</span></td>
                <td class="text-end w-80">{{ number_format($stat->deal_profit  - $stat->mileage,2) }}</td>
            </tr>
        </tbody>
    </table>
</td>
<td class="text-center">{{ $stat->date_time }}</td>
</tr>