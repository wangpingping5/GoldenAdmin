<?php
	$bet_string = [
		'',  //0
		'파워볼(홀)',//01
		'파워볼(짝)',//02

		'파워볼(언더)',//03
		'파워볼(오버)',//04

		'파워볼(홀/언더)',//05
		'파워볼(홀/오버)',//06
		'파워볼(짝/언더)',//07
		'파워볼(짝/오버)',//08

		'일반볼(홀)',//09
		'일반볼(짝)',//10

		'일반볼(언더)',//11
		'일반볼(오버)',//12

		'일반볼(홀/언더)',//13
		'일반볼(홀/오버)',//14
		'일반볼(짝/언더)',//15
		'일반볼(짝/오버)',//16

		'조합(홀/언더/파홀)',//17
		'조합(홀/오버/파짝)',//18
		'조합(홀/언더/파홀)',//19
		'조합(홀/오버/파짝)',//20
		'조합(짝/언더/파홀)',//21
		'조합(짝/오버/파짝)',//22
		'조합(짝/언더/파홀)',//23
		'조합(짝/오버/파짝)',//24
	];

    $bet_class = [
		'',  //0
		'rbutton',//01
		'gbutton',//02

		'bbutton',//03
		'rbutton',//04

		'파워볼(홀/언더)',//05
		'파워볼(홀/오버)',//06
		'파워볼(짝/언더)',//07
		'파워볼(짝/오버)',//08

		'일반볼(홀)',//09
		'일반볼(짝)',//10

		'일반볼(언더)',//11
		'일반볼(오버)',//12
        
		'일반볼(홀/언더)',//13
		'일반볼(홀/오버)',//14
		'일반볼(짝/언더)',//15
		'일반볼(짝/오버)',//16

		'조합(홀/언더/파홀)',//17
		'조합(홀/오버/파짝)',//18
		'조합(홀/언더/파홀)',//19
		'조합(홀/오버/파짝)',//20
		'조합(짝/언더/파홀)',//21
		'조합(짝/오버/파짝)',//22
		'조합(짝/언더/파홀)',//23
		'조합(짝/오버/파짝)',//24
	];
?>
<tr>
    <td>시작시간</td>
    <td>{{$res['result']?$res['result']->s_time:'Unknown'}}</td>
</tr>
                        
<tr>
    <td>종료시간</td>
    <td>{{$res['result']?$res['result']->e_time:'Unknown'}}</td>
</tr>

<tr>
    <td>게임이름</td>
    <td>@lang('gamename.'.$res['game_name'])</td>
</tr>
<tr>
    <td>게임회차</td>
    <td>{{$res['result']?substr($res['result']->dround_no, -3):'---'}}회차</td>
</tr>

<tr>
    <td>볼 번호</td>
    <td>
        <ul  style="display: table;margin-left: auto;margin-right: auto;">
        <?php
            if ($res['result'])
            {
                $balls = explode('|', $res['result']->balls);
                foreach ($balls as $i=>$b)
                {
                    $class = '';
                    if ($i == 5) $class='black_ball'; 
                    else if ($b < 7) $class='orange_ball';
                    else if ($b < 14) $class='green_ball';
                    else if ($b < 21) $class='blue_ball';
                    else  $class='red_ball';
                    echo '<li class="'.$class.'">' . $b . '</li>';
                }
            }
            else
            {
                echo '<li>Unknown</li>';
            }
        ?>
        </ul>
    </td>
</tr>


<tr>
    <td>게임결과</td>
    <td>
        <ul  style="display: table;margin-left: auto;margin-right: auto;">
        <?php
            if ($res['result'])
            {
                $rts = explode('|', $res['result']->result);
                foreach ($rts as $i=>$r)
                {
                    $class = '';
                    if ($r % 4 == 0) $class='rbutton';
                    else if ($r % 4 == 1) $class='gbutton';
                    else if ($r % 4 == 2) $class='bbutton';
                    else  $class='obutton';
                    echo '<button class="'.$class.'">' . $bet_string[$r] . '</li>';
                }
            }
            else
            {
                echo '<li>Unknown</li>';
            }
        ?>
        </ul>
    </td>
</tr>



<tr>
    <td>유저</td>
    <td>{{$res['stat']->user?$res['stat']->user->username:'Unknown'}}</td>
</tr>
<tr>
    <td>베팅</td>
    <td>
        <table width="100%">
            <thead class="bg-primary text-white">
                <tr>
                    <th class="text-white">배팅번호</th>
                    <th class="text-white">배팅값</th>
                    <th class="text-white">배당</th>
                    <th class="text-white">배팅금</th>
                    <th class="text-white">당첨금</th>
                    <th class="text-white">상태</th>
                </tr>
            </thead>
            @foreach ($res['bets'] as $bet)
            <tr>
                <td>{{$bet->bet_id}}</td>
                <td>{{$bet_string[$bet->result]}}</td>
                <td>{{$bet->rate}}</td>
                <td>{{$bet->amount}}</td>
                <td>{{$bet->win}}</td>
                <td>
                    @if ($bet->win>0)
                    <span class='text-green'>당첨</span>
                    @else
                    <span class='text-red'>낙첨</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
    </td>
</tr>