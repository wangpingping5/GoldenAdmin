<?php 
    $badge_class = \App\Models\User::badgeclass();
?>
<td><a class="text-primary" href="javascript:;"><span class="btn {{$badge_class[$user->role_id]}} px-2 py-1 mb-0 rounded-0">[{{__($adjustment->user->role->name)}}]</span></br>{{$adjustment->user->username}}</a></td>
<td>{{ date('Y-m-d',strtotime($adjustment->date)) }}</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">배팅금</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->bet,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">파트너배팅금</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->betlimit,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">받치기배팅금</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->bet - $adjustment->betlimit,0)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">당첨금</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->win,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">파트너당첨금</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->winlimit,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">받치기당첨금</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->win - $adjustment->winlimit,0)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">벳윈</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->bet - $adjustment->win,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">파트너벳원</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->betlimit - $adjustment->winlimit,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">받치기벳원</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->bet - $adjustment->win - $adjustment->betlimit + $adjustment->winlimit,0)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">롤링금</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->deal_limit + $adjustment->deal_share,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">파트너롤링금</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->deal_limit,0)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">받치기롤링금</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->deal_share,0)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>{{ number_format($adjustment->deal_out,0) }}</td>
