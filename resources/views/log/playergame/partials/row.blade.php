<?php
$game = preg_replace('/PM/', '_pp', $stat->game);
$game = preg_replace('/HBN/', '_HBN', $game);
$game = preg_replace('/CQ9/', '_cq9', $game);

if (str_ends_with($game, '_truxbet')) {
    $game = substr($game, 0, -strlen('_truxbet')); // _truxbet 제거
} else {
    $game = explode(' ', $game)[0]; // 공백 기준 첫 단어
}

$game = preg_replace('/^_/', '', $game);
$cc = explode('_', $game);
if (count($cc) > 1) {
    array_pop($cc);
    $game = implode(' ', $cc);
}
if ($gacmerge == 1) {
    $game = preg_replace('/포츈스피드바카라/', 'VIP스피드바카라', $game);
}
$badge_class = \App\Models\User::badgeclass();
	?>
<tr>
    <td class="text-center">
        <span class="badge {{$badge_class[$stat->user->role_id]}} rounded-0">[{{__($stat->user->role->name)}}]</span>
    </td>

    <td class="text-center"><span class="btn btn-primary m-0 rounded-0" style="background-color: #20c997;"
            data-bs-toggle="tooltip" data-bs-placement="bottom"
            title="{!!$stat->user->parents(auth()->user()->role_id, $stat->user->role_id + 1)!!}">[{{__($stat->user->referral->role->name)}}]
            {{$stat->user->referral->username}}</span></td>

    <td class="text-center">
        @if ($stat->user)
            <a href="javascript:;" onclick="userClick('{{$stat->user->username}}')" data-toggle="tooltip" class="text-info"
                data-original-title="{{$stat->user->parents(auth()->user()->role_id)}}">
                {{$stat->user->username}}
            </a>
        @else
            {{__('Unknown')}}
        @endif
    </td>

    <td class="text-center">
        @if ($stat->category)
            @if ($stat->category->trans)
                {{$stat->category->trans->trans_title}}
            @else
                {{$stat->category->title}}
            @endif
        @else
            {{__('Unknown')}}
        @endif
    </td>
    <td class="text-center"> {{$game}} </td>
    <td class="text-center">
        <table>
            <tbody>
                <tr>
                    <td class="text-start"><span class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.Balance')}}</span></td>
                    @if ($stat->category && $stat->category->href == 'habaneroplay')
                        -1
                    @else
                        <td class="text-end w-80">{{number_format($stat->balance, 0)}}</td>
                    @endif
                </tr>
                @if($stat->bet_type == 'betwin')
                    <tr>
                        <td class="text-start"><span class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('agent.BetAmount')}}</span></td>
                        <td class="text-end w-80">{{number_format($stat->bet, 0)}}</td>
                    </tr>
                    <tr>
                        <td class="text-start"><span class="btn btn-success px-2 py-1 mb-0 rounded-0">{{__('agent.WinAmount')}}</span></td>
                        <td class="text-end w-80">{{number_format($stat->win, 0)}}</td>
                    </tr>
                @elseif($stat->bet_type == 'bet')
                    <tr>
                        <td class="text-start"><span class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('agent.BetAmount')}}</span></td>
                        <td class="text-end w-80">{{number_format($stat->bet, 0)}}</td>
                    </tr>
                @elseif($stat->bet_type == 'cancel')
                    <tr>
                        <td class="text-start"><span class="btn btn-dark px-2 py-1 mb-1 rounded-0">{{__('agent.BetCancel')}}</span></td>
                        <td class="text-end w-80">{{number_format($stat->win, 0)}}</td>
                    </tr>
                @else
                    <tr>
                        <td class="text-start"><span class="btn btn-success px-2 py-1 mb-0 rounded-0">{{__('agent.WinAmount')}}</span></td>
                        <td class="text-end w-80">{{number_format($stat->win, 0)}}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </td>

    <td class="text-center">{{ $stat->date_time }}</td>
    <td class="text-center">
        @if ($stat->status == 1)
            <span class="text-success">{{__('agent.Complete')}}</span>
        @else
            <span class="text-warning">{{__('agent.Waiting')}}</span>
        @endif
    </td>
    <td class="text-center">
        <a href="{{argon_route('player.gamedetail', ['statid' => $stat->id])}}"
            onclick="window.open(this.href,'newwindow','width=1000,height=800'); return false;"><button
                class="btn btn-success btn-sm rounded-0">{{__('agent.Detail')}}</button></a>
    </td>
</tr>