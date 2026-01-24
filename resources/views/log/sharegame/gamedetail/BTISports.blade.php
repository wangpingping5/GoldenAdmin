<tr>
    <td>구매 ID</td>
    <td>{{$res['betinfo']['pur_id']}}</td>
</tr>
<tr>
    <td>구매일</td>
    <td>{{$res['betinfo']['date']}}</td>
</tr>
<tr>
    <td>배팅 유형</td>
    <td>{{$res['betinfo']['bet_type']}}</td>
</tr>

<tr>
    <td>배팅금</td>
    <td>{{$res['betinfo']['bet_money']}}</td>
</tr>
<tr>
    <td>당첨금</td>
    <td>{{$res['betinfo']['award_money']}}</td>
</tr>

<tr>
    <td>배당률</td>
    <td>{{$res['betinfo']['odd']}}</td>
</tr>
<tr>
    <td colspan='2'style="
    overflow: auto;" >
        <table style="overflow: auto; margin:auto;text-wrap: nowrap;" >
            <thead class="bg-primary text-white">
                <th class="text-white">스포츠</th>
                <th class="text-white">리그</th>
                <th class="text-white">경기시간</th>
                <th class="text-white">경기</th>
                <th class="text-white">마켓</th>
                <th class="text-white">고객배팅</th>
                <th class="text-white">배당률</th>
                <th class="text-white">풀타임 스코어</th>
                <th class="text-white">상태</th>
            </thead>
            <tbody>
                @foreach($res['result'] as $res1)
                    <tr>
                        <td >{{$res1['branchname']}}</td>
                        <td >{{$res1['leaguename']}}</td>
                        <td >{{$res1['date']}}</td>
                        <td >{{$res1['game']}}</td>
                        <td >{{$res1['market']}}</td>
                        <td >{{$res1['yourbet']}}</td>
                        <td >{{$res1['odd']}}</td>
                        <td >{{$res1['fulltimescore']}}</td>
                        <td >
                            
                            @if($res1['stat'] == '적중')
                            <span class="text-green">{{$res1['stat']}}</span>
                            @elseif($res1['stat'] == '미적중')
                            <span class="text-red">{{$res1['stat']}}</span>
                            @else
                            <span class="text-black">{{$res1['stat']}}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </td>    
</tr>
 
          
