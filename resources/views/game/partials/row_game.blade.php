@foreach ($games as $game)
    @if ($loop->index > 0)
	<tr>
	@endif
    <td>{{$game->name}}</td>
    @php
        $shops = $user->shops(true);
        $disableCount = \App\Models\Game::where(['view'=>0, 'original_id' => $game->original_id])->whereIn('shop_id', $shops)->count();
        $activeCount = \App\Models\Game::where(['view'=>1, 'original_id' => $game->original_id])->whereIn('shop_id', $shops)->count();
    @endphp
    <td><span class="text-success">{{$activeCount}}</span></td>
    <td><span class="text-danger">{{$disableCount}}</span></td>
    <td class="text-end">
        <a class="btn btn-success px-2 py-1 mb-1 mx-1 rounded-0" href="{{argon_route('game.game.status', ['user_id'=>$user->id, 'game_id'=>$game->original_id, 'value'=>1,'field'=>'view'])}}">{{__('agent.GameActive')}}</a>
        <a  class="btn btn-warning px-2 py-1 mb-1 mx-1 rounded-0" href="{{argon_route('game.game.status', ['user_id'=>$user->id, 'game_id'=>$game->original_id, 'value'=>0,'field'=>'view'])}}">{{__('agent.GameInactive')}}</a>
    </td>
    @if ($loop->index > 0)
	</tr>
	@endif
@endforeach
