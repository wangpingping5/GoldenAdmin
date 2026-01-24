<?php
	$bet_string = [
        12 => [
		'',  //0
		'BIG',//01
		'SMALL',//02
	    ],
        13 => [
            '',  //0
            'ODD',//01
            'EVEN',//02
            'BIG',//03
            'SMALL',//04
            'WHITE4',//05
            'RED4',//06
            'WHITE3',//07
            'RED3',//08
        ]
    ];

    $chip_string = [
        12 => [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
	    ],
        13 => [
            1 => '',
            2 => ''
        ]
    ];

?>
<tr>
    <td>시작시간</td>
    <td>{{$res['result']?$res['result']->sDate:'Unknown'}}</td>
</tr>
                        
<tr>
    <td>종료시간</td>
    <td>{{$res['result']?$res['result']->eDate:'Unknown'}}</td>
</tr>

<tr>
    <td>게임이름</td>
    <td>{{$res['result']?$res['result']->game_id:'Unknown'}}</td>
</tr>
<tr>
    <td>게임회차</td>
    <td>{{$res['result']?substr($res['result']->dno, -3):'---'}}회차</td>
</tr>

<tr>
    <td>칩 번호</td>
    <td>
        <ul  style="display: table;margin-left: auto;margin-right: auto;">
        <?php
            if ($res['result'])
            {
                $balls = explode('|', $res['result']->rno);
                foreach ($balls as $i=>$b)
                {
                    $class = '';
                    $class = '';
                    if ($b == 1) $class='white_ball'; 
                    else if ($b == 2) $class='red_ball';
                    else if ($b == 3 ) $class='green_ball';
                    else if ($b == 4) $class='blue_ball';
                    else  $class='black_ball';

                    echo '<li class="'.$class.'">' . (isset($chip_string[$res['result']->p][$b])?$chip_string[$res['result']->p][$b]:$b) . '</li>';
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
                $rts = explode('|', $res['result']->rt);
                foreach ($rts as $i=>$r)
                {
                    $class = '';
                    if ($r % 4 == 0) $class='rbutton';
                    else if ($r % 4 == 1) $class='gbutton';
                    else if ($r % 4 == 2) $class='bbutton';
                    else  $class='obutton';
                    if ($r != 0){
                        echo '<button class="'.$class.'">' . $bet_string[$res['result']->p][$r] . '</li>';
                    }
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
                <td>{{$bet_string[$bet->p][$bet->rt]}}</td>
                <td>{{$bet->o}}</td>
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