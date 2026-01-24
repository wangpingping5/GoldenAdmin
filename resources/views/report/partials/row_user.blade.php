
<td><a class="text-primary" href="javascript:;"><span class="btn {{$badge_class[$adjustment->user->role_id]}} px-2 py-1 mb-0 rounded-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$adjustment->user->parents(auth()->user()->role_id, 3)!!}">[{{__($adjustment->user->role->name)}}]</span></br>{{$adjustment->user->username}}</a></td>
<?php
    $inout = \App\Models\UserDailySummary::calcInOut($adjustment->user->id, $daterange);
?>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><a href="javascript:;" class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Deposit')}}</a></td>
                <td class="text-end w-70">{{number_format($inout['totalin'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><a href="javascript:;" class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('agent.Withdraw')}}</a></td>
                <td class="text-end w-70">{{number_format($inout['totalout'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><a href="javascript:;" class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('Summary')}}</a></td>
                <td class="text-end w-70"> {{number_format($inout['totalin'] - $inout['totalout'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<!-- 수동충환전 -->
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><a href="javascript:;" class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Deposit')}}</a></td>
                <td class="text-end w-70">{{number_format($inout['moneyin'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><a href="javascript:;" class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('agent.Withdraw')}}</a></td>
                <td class="text-end w-70">{{number_format($inout['moneyout'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><a href="javascript:;" class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('Summary')}}</a></td>
                <td class="text-end w-70"> {{number_format($inout['moneyin'] - $inout['moneyout'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<?php
    $betwins = \App\Models\UserDailySummary::rangebetwin($adjustment->user->id, $daterange);
?>
@foreach ($betwins as $type => $bt)
<td>
    <span class="text-primary">{{number_format($bt['totalbet'])}}</span>
</td>
<td>
    <span class="text-warning">{{number_format($bt['totalwin'])}}</span>
</td>
<td>
    @if($bt['totalbet'] >= $bt['totalwin'])
        <span class="text-success">{{number_format($bt['totalbet'] - $bt['totalwin'])}}</span>
    @else
        <span class="text-danger">{{number_format($bt['totalbet'] - $bt['totalwin'])}}</span>
    @endif
</td>
@endforeach