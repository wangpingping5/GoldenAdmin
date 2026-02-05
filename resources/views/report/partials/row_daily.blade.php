
<td><a class="text-primary" href="javascript:;" onclick="userClick({{$adjustment->user->role_id}}, '{{$adjustment->user->username}}')"><span class="btn {{$badge_class[$adjustment->user->role_id]}} px-2 py-1 mb-0 rounded-0">[{{__($adjustment->user->role->name)}}]</span></br>{{$adjustment->user->username}}</a></td>
@if ($adjustment->date=='')
    @if(isset($sumInfo) && $sumInfo!='')
    <td>{{$sumInfo}}</td>
    @else
    <td></td>
    @endif
@else
<td>{{ date('Y-m-d',strtotime($adjustment->date)) }}</td>
@endif
<?php
    $isInOutPartner = auth()->user()->isInoutPartner();
    if ($adjustment->date == date('Y-m-d'))
    {
        $inout = $adjustment->calcInOut();
        $adjustment->totalin = $inout['totalin'];
        $adjustment->totalout = $inout['totalout'];
        $adjustment->moneyin = $inout['moneyin'];
        $adjustment->moneyout = $inout['moneyout'];
    }
    if(isset($sumInfo) && $sumInfo!=''){
        $betwin = \App\Models\DailySummary::rangebetwin($adjustment->user->id, $sumInfo);
    }else{
        $betwin = $adjustment->betwin();
    }
?>
<!-- 계좌충환전 -->
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><a href="javascript:;" class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Deposit')}}</a></td>
                <td class="text-end w-70">{{number_format($adjustment->totalin)}}</td>
            </tr>
            <tr>
                <td class="text-start"><a href="javascript:;" class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('agent.Withdraw')}}</a></td>
                <td class="text-end w-70">{{number_format($adjustment->totalout)}}</td>
            </tr>
            <tr>
                <td class="text-start"><a href="javascript:;" class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.Profit')}}</a></td>
                <td class="text-end w-70"> {{number_format($adjustment->totalin - $adjustment->totalout)}}</td>
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
                <td class="text-end w-70">{{number_format($adjustment->moneyin)}}</td>
            </tr>
            <tr>
                <td class="text-start"><a href="javascript:;" class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('agent.Withdraw')}}</a></td>
                <td class="text-end w-70">{{number_format($adjustment->moneyout)}}</td>
            </tr>
            <tr>
                <td class="text-start"><a href="javascript:;" class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.Profit')}}</a></td>
                <td class="text-end w-70"> {{number_format($adjustment->moneyin - $adjustment->moneyout)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<!-- 롤링전환 -->
<td >{{number_format($adjustment->dealout, 2)}}</td>

<!-- 보유금 -->
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->balance+$adjustment->childsum, 2)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->balance, 2)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('Players')}}</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->user_sum, 2)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($adjustment->partner_sum, 2)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<!-- 카지노 -->
@if(isset($betwin['live']))
<td>
    {!!$isInOutPartner?number_format($betwin['live']['totalbet']) . '/<span class="text-warning">('. number_format($betwin['live']['totaldealbet']) .')</span>':number_format($betwin['live']['totaldealbet'])!!}
</td>
<td>
    {!!$isInOutPartner?number_format($betwin['live']['totalwin']) . '/<span class="text-warning">('. number_format($betwin['live']['totaldealwin']) .')</span>':number_format($betwin['live']['totaldealwin'])!!}
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['live']['total_deal'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['live']['total_mileage'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['live']['total_deal']-$betwin['live']['total_mileage'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['live']['total_ggr'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['live']['total_ggr_mileage'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['live']['total_ggr']-$betwin['live']['total_ggr_mileage'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    {!!$isInOutPartner?number_format($betwin['live']['totalbet'] - $betwin['live']['totalwin'] - ($betwin['live']['total_deal']>0?$betwin['live']['total_deal']:$betwin['live']['total_mileage'])) . '/<span class="text-warning">('. number_format($betwin['live']['totaldealbet'] - $betwin['live']['totaldealwin'] -($betwin['live']['total_deal']>0?$betwin['live']['total_deal']:$betwin['live']['total_mileage'])) .')</span>':number_format($betwin['live']['totaldealbet'] - $betwin['live']['totaldealwin'] -($betwin['live']['total_deal']>0?$betwin['live']['total_deal']:$betwin['live']['total_mileage']))!!}
</td>
@else
<td>0</td>
<td>0</td>
<td>0</td>
<td>0</td>
<td>0</td>
@endif

<!-- 슬롯 -->
@if(isset($betwin['slot']))
<td>
    {!!$isInOutPartner?number_format($betwin['slot']['totalbet']) . '/<span class="text-warning">('. number_format($betwin['slot']['totaldealbet']) .')</span>':number_format($betwin['slot']['totaldealbet'])!!}
</td>
<td>
    {!!$isInOutPartner?number_format($betwin['slot']['totalwin']) . '/<span class="text-warning">('. number_format($betwin['slot']['totaldealwin']) .')</span>':number_format($betwin['slot']['totaldealwin'])!!}
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['slot']['total_deal'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['slot']['total_mileage'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['slot']['total_deal']-$betwin['slot']['total_mileage'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['slot']['total_ggr'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['slot']['total_ggr_mileage'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['slot']['total_ggr']-$betwin['slot']['total_ggr_mileage'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    {!!$isInOutPartner?number_format($betwin['slot']['totalbet'] - $betwin['slot']['totalwin'] - ($betwin['slot']['total_deal']>0?$betwin['slot']['total_deal']:$betwin['slot']['total_mileage'])) . '/<span class="text-warning">('. number_format($betwin['slot']['totaldealbet'] - $betwin['slot']['totaldealwin'] -($betwin['slot']['total_deal']>0?$betwin['slot']['total_deal']:$betwin['slot']['total_mileage'])) .')</span>':number_format($betwin['slot']['totaldealbet'] - $betwin['slot']['totaldealwin'] -($betwin['slot']['total_deal']>0?$betwin['slot']['total_deal']:$betwin['slot']['total_mileage']))!!}
</td>
@else
<td>0</td>
<td>0</td>
<td>0</td>
<td>0</td>
<td>0</td>
@endif
@if(config('app.logo')!='poseidon02')
<!-- 스포츠 -->
@if(isset($betwin['sports']))
<td>
    {!!$isInOutPartner?number_format($betwin['sports']['totalbet']) . '/<span class="text-warning">('. number_format($betwin['sports']['totaldealbet']) .')</span>':number_format($betwin['sports']['totaldealbet'])!!}
</td>
<td>
    {!!$isInOutPartner?number_format($betwin['sports']['totalwin']) . '/<span class="text-warning">('. number_format($betwin['sports']['totaldealwin']) .')</span>':number_format($betwin['sports']['totaldealwin'])!!}
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['sports']['total_deal'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['sports']['total_mileage'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['sports']['total_deal']-$betwin['sports']['total_mileage'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['sports']['total_ggr'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['sports']['total_ggr_mileage'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['sports']['total_ggr']-$betwin['sports']['total_ggr_mileage'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    {!!$isInOutPartner?number_format($betwin['sports']['totalbet'] - $betwin['sports']['totalwin'] - ($betwin['sports']['total_deal']>0?$betwin['sports']['total_deal']:$betwin['sports']['total_mileage'])) . '/<span class="text-warning">('. number_format($betwin['sports']['totaldealbet'] - $betwin['sports']['totaldealwin'] -($betwin['sports']['total_deal']>0?$betwin['sports']['total_deal']:$betwin['sports']['total_mileage'])) .')</span>':number_format($betwin['sports']['totaldealbet'] - $betwin['sports']['totaldealwin'] -($betwin['sports']['total_deal']>0?$betwin['sports']['total_deal']:$betwin['sports']['total_mileage']))!!}
</td>
@else
<td>0</td>
<td>0</td>
<td>0</td>
<td>0</td>
<td>0</td>
@endif

<!-- 보드 -->
@if(isset($betwin['card']))
<td>
    {!!$isInOutPartner?number_format($betwin['card']['totalbet']) . '/<span class="text-warning">('. number_format($betwin['card']['totaldealbet']) .')</span>':number_format($betwin['card']['totaldealbet'])!!}
</td>
<td>
    {!!$isInOutPartner?number_format($betwin['card']['totalwin']) . '/<span class="text-warning">('. number_format($betwin['card']['totaldealwin']) .')</span>':number_format($betwin['card']['totaldealwin'])!!}
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['card']['total_deal'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['card']['total_mileage'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['card']['total_deal']-$betwin['card']['total_mileage'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['card']['total_ggr'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['card']['total_ggr_mileage'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['card']['total_ggr']-$betwin['card']['total_ggr_mileage'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    {!!$isInOutPartner?number_format($betwin['card']['totalbet'] - $betwin['card']['totalwin'] - ($betwin['card']['total_deal']>0?$betwin['card']['total_deal']:$betwin['card']['total_mileage'])) . '/<span class="text-warning">('. number_format($betwin['card']['totaldealbet'] - $betwin['card']['totaldealwin'] -($betwin['card']['total_deal']>0?$betwin['card']['total_deal']:$betwin['card']['total_mileage'])) .')</span>':number_format($betwin['card']['totaldealbet'] - $betwin['card']['totaldealwin'] -($betwin['card']['total_deal']>0?$betwin['card']['total_deal']:$betwin['card']['total_mileage']))!!}
</td>
@else
<td>0</td>
<td>0</td>
<td>0</td>
<td>0</td>
<td>0</td>
@endif
<!-- 파워볼 -->
@if(isset($betwin['pball']))
<td>
    {!!$isInOutPartner?number_format($betwin['pball']['totalbet']) . '/<span class="text-warning">('. number_format($betwin['pball']['totaldealbet']) .')</span>':number_format($betwin['pball']['totaldealbet'])!!}
</td>
<td>
    {!!$isInOutPartner?number_format($betwin['pball']['totalwin']) . '/<span class="text-warning">('. number_format($betwin['pball']['totaldealwin']) .')</span>':number_format($betwin['pball']['totaldealwin'])!!}
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['pball']['total_deal'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['pball']['total_mileage'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['pball']['total_deal']-$betwin['pball']['total_mileage'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['pball']['total_ggr'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['pball']['total_ggr_mileage'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['pball']['total_ggr']-$betwin['pball']['total_ggr_mileage'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    {!!$isInOutPartner?number_format($betwin['pball']['totalbet'] - $betwin['pball']['totalwin'] - ($betwin['pball']['total_deal']>0?$betwin['pball']['total_deal']:$betwin['pball']['total_mileage'])) . '/<span class="text-warning">('. number_format($betwin['pball']['totaldealbet'] - $betwin['pball']['totaldealwin'] -($betwin['pball']['total_deal']>0?$betwin['pball']['total_deal']:$betwin['pball']['total_mileage'])) .')</span>':number_format($betwin['pball']['totaldealbet'] - $betwin['pball']['totaldealwin'] -($betwin['pball']['total_deal']>0?$betwin['pball']['total_deal']:$betwin['pball']['total_mileage']))!!}
</td>
@else
<td>0</td>
<td>0</td>
<td>0</td>
<td>0</td>
<td>0</td>
@endif
@endif
<!-- 합계 -->
@if(isset($betwin['total']))
<td>
    {!!$isInOutPartner?number_format($betwin['total']['totalbet']) . '/<span class="text-warning">('. number_format($betwin['total']['totaldealbet']) .')</span>':number_format($betwin['total']['totaldealbet'])!!}
</td>
<td>
    {!!$isInOutPartner?number_format($betwin['total']['totalwin']) . '/<span class="text-warning">('. number_format($betwin['total']['totaldealwin']) .')</span>':number_format($betwin['total']['totaldealwin'])!!}
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['total']['total_deal'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['total']['total_mileage'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['total']['total_deal']-$betwin['total']['total_mileage'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['total']['total_ggr'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['total']['total_ggr_mileage'])}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($betwin['total']['total_ggr']-$betwin['total']['total_ggr_mileage'])}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    {!!$isInOutPartner?number_format($betwin['total']['totalbet'] - $betwin['total']['totalwin'] - ($betwin['total']['total_deal']>0?$betwin['total']['total_deal']:$betwin['total']['total_mileage'])) . '/<span class="text-warning">('. number_format($betwin['total']['totaldealbet'] - $betwin['total']['totaldealwin'] -($betwin['total']['total_deal']>0?$betwin['total']['total_deal']:$betwin['total']['total_mileage'])) .')</span>':number_format($betwin['total']['totaldealbet'] - $betwin['total']['totaldealwin'] -($betwin['total']['total_deal']>0?$betwin['total']['total_deal']:$betwin['total']['total_mileage']))!!}
</td>
@else
<td>0</td>
<td>0</td>
<td>0</td>
<td>0</td>
<td>0</td>
@endif

@if (isset($sumInfo) && $sumInfo!='')
@else
    @if (auth()->user()->hasRole('admin'))
    <td>
    <?php
        $prevDay = $adjustment->prevDay;
        $margin = 0;
        if ($prevDay)
        {
            if ($adjustment->user->isInoutPartner())
            {
                $margin = ($adjustment->balance+$adjustment->childsum) - ($prevDay->balance + $prevDay->childsum + $adjustment->moneyin - $adjustment->moneyout - ($betwin['total']['totalbet']-$betwin['total']['totalwin']));
            }
            else
            {
                $margin = ($adjustment->balance+$adjustment->childsum) - ($prevDay->balance + $prevDay->childsum + $adjustment->totalin - $adjustment->totalout + $adjustment->moneyin - $adjustment->moneyout + $adjustment->dealout - ($betwin['total']['totalbet']-$betwin['total']['totalwin']));
            }
        }
        
    ?>
    @if ($margin > 0)
    <span class="text-danger">{{number_format($margin, 0)}}</span>
    @else
    <span class="text-success">{{number_format($margin, 0)}}</span>
    @endif
    </td>
    @endif
@endif