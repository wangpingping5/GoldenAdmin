<?php
        $game = explode(' ', $stat->game)[0];
		$game = preg_replace('/^_/', '', $game);
        $cc = explode('_', $game);
        if (count($cc) > 1)
        {
            array_pop($cc);
            $game = implode(' ', $cc);
        }
        $badge_class = \App\Models\User::badgeclass();
	?>
<tr>
<td class="text-center">
    @if ($stat->user)
    <a href="javascript:;" onclick="userClick('{{$stat->user->username}}');" data-toggle="tooltip" data-original-title="{{$stat->user->parents(auth()->user()->role_id)}}">
        <span class="badge {{$badge_class[$stat->user->role_id]}} rounded-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$stat->user->parents(auth()->user()->role_id, $stat->user->role_id+1)!!}">{{$stat->user->username}}&nbsp;&nbsp;[{{__($stat->user->role->name)}}]</span>
    </a>
    @else
        {{__('Unknown')}}
    @endif
</td>
<td class="text-center">
    @if ($stat->partner)
        <span class="badge {{$badge_class[$stat->partner->role_id]}} rounded-0" >{{$stat->partner->username}}</span>
    @else
        {{__('Unknown')}}
    @endif
</td>
<td class="text-center">
    @if (auth()->user()->hasRole('comaster'))
        상위파트너
    @else
        @if ($stat->shareuser)
            <span class="badge {{$badge_class[$stat->shareuser->role_id]}} rounded-0" >{{$stat->shareuser->username}}</span>
        @else
            {{__('Unknown')}}
        @endif
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
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;총배팅금&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
                <td class="text-end w-80">{{ number_format($stat->bet,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-danger px-2 py-1 mb-1 rounded-0">파트너배팅금</span></td>
                <td class="text-end w-80">{{ number_format($stat->betlimit,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-0 rounded-0">받치기배팅금</span></td>
                <td class="text-end w-80">{{ number_format($stat->bet - $stat->betlimit,0)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td class="text-center">
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;총당첨금&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
                <td class="text-end w-80">{{ number_format($stat->win,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-danger px-2 py-1 mb-1 rounded-0">파트너당첨금</span></td>
                <td class="text-end w-80">{{ number_format($stat->winlimit,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-0 rounded-0">받치기당첨금</span></td>
                <td class="text-end w-80">{{ number_format($stat->win - $stat->winlimit,0)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td class="text-center">{{number_format($stat->deal_percent,2)}}</td>
<td class="text-center">
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;총롤링금&nbsp;&nbsp;&nbsp;&nbsp;</span></td>
                <td class="text-end w-80">{{ number_format(($stat->bet * $stat->deal_percent) / 100,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-danger px-2 py-1 mb-1 rounded-0">파트너롤링금</span></td>
                <td class="text-end w-80">{{ number_format($stat->deal_limit,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-0 rounded-0">받치기롤링금</span></td>
                <td class="text-end w-80">{{ number_format($stat->deal_share,0)}}</td>
            </tr>
        </tbody>
    </table>
</td>

<td class="text-center">{{ $stat->date_time }}</td>
<td class="text-center">
<a href="{{argon_route('player.gamedetail', ['statid' => $stat->stat_id])}}" onclick="window.open(this.href,'newwindow','width=1000,height=800'); return false;"><button class="btn btn-success btn-sm rounded-0">상세보기</button></a>
</td>
</tr>