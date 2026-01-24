<?php
    $cardpic = [
        'H' => '♥',
        'S' => '♠',
        'D' => '◆',
        'C' => '♣',
    ]
?>
<tr>
    <td>게임시간</td>
    <td>{{$res['result']?$res['result']['regdate']:'Unknown'}} UTC</td>
</tr>
<tr>
    <td>게임타입</td>
    <td>{{$res['result']?$res['result']['type']:'Unknown'}}</td>
</tr>
<tr>
    <td>테이블네임</td>
    <td>{{$res['result']?$res['result']['tableName']:'Unknown'}}</td>
</tr>
<tr>
    <td>게임번호</td>
    <td>{{$res['result']?$res['result']['gameNumber']:'Unknown'}}</td>
</tr>

<tr>
    <td>게임결과</td>
    <td>
        <table width="100%">
            <thead class="bg-primary text-white">
                <tr>
                    <th class="text-white">Banker</th>
                    <th class="text-white">Player</th>
                    <th class="text-white">Winner</th>
                </tr>
            </thead>
            <tr>
                <td align="center">
                    @foreach ($res['result']['bankerHand'] as $idx=>$hand)
                    <?php $cardnum= substr($hand,0,1); $cardtype=$cardpic[substr($hand,1)];?>
                    <div class="cardFrame"><div class="{{$idx%2==0?'font_red':'font_black'}} number">{{$cardnum=='T'?'10':$cardnum}}</div><div class="{{$idx%2==0?'font_red':'font_black'}}  patten">{{$cardtype}}</div></div>
                    @endforeach
                    <span>{{$res['result']?$res['result']['bankerScore']:'0'}}</span>
                </td>
                <td>
                    @foreach ($res['result']['playerHand'] as $idx=>$hand)
                    <?php $cardnum= substr($hand,0,1); $cardtype=$cardpic[substr($hand,1)];?>
                    <div class="cardFrame"><div class="{{$idx%2==0?'font_red':'font_black'}} number">{{$cardnum=='T'?'10':$cardnum}}</div><div class="{{$idx%2==0?'font_red':'font_black'}}  patten">{{$cardtype}}</div></div>
                    @endforeach
                    <span>{{$res['result']?$res['result']['playerScore']:'0'}}</span>
                </td>
                <td>
                    {{$res['result']?$res['result']['result']:'0'}}
                </td>
            </tr>
        </table>
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
                    <th class="text-white">배팅값</th>
                    <th class="text-white">배당</th>
                    <th class="text-white">배팅금</th>
                    <th class="text-white">당첨금</th>
                    <th class="text-white">상태</th>
                </tr>
            </thead>
            @foreach ($res['bets'] as $bet)
            <tr>
                <td>{{$bet['betValue']}}</td>
                <td>{{$bet['odds']}}</td>
                <td>
                    {{$bet['betAmount']}}
                    @if (isset($bet['betfee']) && $bet['betfee']>0)
                    + {{$bet['betfee']}}
                    @endif

                </td>
                <td>{{$bet['winAmount']}}</td>
                <td><span class='text-green'>{{$bet['status']}}</span></td>
            </tr>
            @endforeach
        </table>
    </td>
</tr>
