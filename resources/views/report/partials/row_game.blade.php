@if ($total)
<td><a class="text-primary" href="javascript:;"><span class="btn {{$badge_class[$user->role_id]}} px-2 py-1 mb-0 rounded-0">[{{__($user->role->name)}}]</span></br>{{$user->username}}</a></td>
<?php
    $game = $stat->title;
    if ($gacmerge == 1)
    {
        $game = preg_replace('/GameArtCasino/', 'EvolutionVIP', $game);
    }
    $provider = __('agent.SelfGame');
    if(isset($stat->provider) && $stat->provider != '' && $stat->provider != 'gac')
    {
        $provider = $stat->provider;
    }
?>
<td>
@if (auth()->user()->hasRole(['admin','comaster']))
    <a href="{{argon_route('report.game.details', ['cat_id'=>$stat->category_id, 'user_id'=>$user->id, 'dates' => Request::get('dates')])}}"><span class="text-primary">{{$game}} @if(auth()->user()->hasRole('admin')){{'('. $provider .')'}}@endif</span></a>
@else
    {{$game}}
@endif
</td>
<td>
    {!!$isInOutPartner?number_format($stat->totalbet) . '/<span class="text-warning">('. number_format($stat->totaldealbet) .')</span>':number_format($stat->totaldealbet)!!}
</td>
<td>
    {!!$isInOutPartner?number_format($stat->totalwin) . '/<span class="text-warning">('. number_format($stat->totaldealwin) .')</span>':number_format($stat->totaldealwin)!!}
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($stat->total_deal)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($stat->total_mileage)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($stat->total_deal-$stat->total_mileage)}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                <td class="text-end w-90">{{ number_format($stat->total_ggr)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                <td class="text-end w-90">{{ number_format($stat->total_ggr_mileage)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                <td class="text-end w-90">{{ number_format($stat->total_ggr-$stat->total_ggr_mileage)}}</td>
            </tr>
        </tbody>
    </table>
</td>
@else
@foreach ($category['cat'] as $stat)
    @if ($loop->index > 0)
	<tr>
	@endif
    <td><a class="text-primary" href="javascript:;"><span class="btn {{$badge_class[$user->role_id]}} px-2 py-1 mb-0 rounded-0">[{{__($user->role->name)}}]</span></br>{{$user->username}}</a></td>
    <?php
        $game = $stat['title'];
        if ($gacmerge == 1)
        {
            $game = preg_replace('/GameArtCasino/', 'EvolutionVIP', $game);
        }
        $provider = __('agent.SelfGame');
        if(isset($stat['provider']) && $stat['provider'] != '' && $stat['provider'] != 'gac')
        {
            $provider = $stat['provider'];
        }
    ?>
    <td>
    @if (auth()->user()->hasRole(['admin','comaster']))
        <a href="{{argon_route('report.game.details', ['cat_id'=>$stat['category_id'], 'user_id'=>$user->id, 'dates'=>[$category['date'], $category['date']]])}}"><span class="text-primary">{{$game}}@if(auth()->user()->hasRole('admin')){{'('. $provider .')'}}@endif</span></a>
    @else
        {{$game}}
    @endif
    </td>
    <td>
        {!!$isInOutPartner?number_format($stat['totalbet']) . '/<span class="text-warning">('. number_format($stat['totaldealbet']) .')</span>':number_format($stat['totaldealbet'])!!}
    </td>
    <td>
        {!!$isInOutPartner?number_format($stat['totalwin']) . '/<span class="text-warning">('. number_format($stat['totaldealwin']) .')</span>':number_format($stat['totaldealwin'])!!}
    </td>
    <td>
        <table>
            <tbody>
                <tr>
                    <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                    <td class="text-end w-90">{{ number_format($stat['total_deal'])}}</td>
                </tr>
                <tr>
                    <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                    <td class="text-end w-90">{{ number_format($stat['total_mileage'])}}</td>
                </tr>
                <tr>
                    <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                    <td class="text-end w-90">{{ number_format($stat['total_deal']-$stat['total_mileage'])}}</td>
                </tr>
            </tbody>
        </table>
    </td>
    <td>
        <table>
            <tbody>
                <tr>
                    <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                    <td class="text-end w-90">{{ number_format($stat['total_ggr'])}}</td>
                </tr>
                <tr>
                    <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                    <td class="text-end w-90">{{ number_format($stat['total_ggr_mileage'])}}</td>
                </tr>
                <tr>
                    <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                    <td class="text-end w-90">{{ number_format($stat['total_ggr']-$stat['total_ggr_mileage'])}}</td>
                </tr>
            </tbody>
        </table>
    </td>
    @if ($loop->index > 0)
	</tr>
	@endif
@endforeach
@endif