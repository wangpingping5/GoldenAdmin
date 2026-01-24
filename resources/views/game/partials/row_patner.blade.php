@foreach ($categories as $category)
    @if ($loop->index > 0)
	<tr>
	@endif
    <td>
        @if ($category->provider != '')
        {{$category->title}}
        @else
        <a class="text-primary" href="{{argon_route('game.game', ['user_id'=>$user->id, 'cat_id'=>$category->original_id])}}">{{$category->title}}</a></td>
        @endif
    <td>
        @if ($category->provider != '')
        @php
            $games = call_user_func('\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($category->provider) . 'Controller::getgamelist', $category->href);
        @endphp
            {{$games!=null?count($games):0}}
        @else
        {{$category->games->count()}}
        @endif
    </td>
    <td>{{$category->provider??__('agent.SelfGame')}}</td>
    @php
        $shops = $user->shops(true);
        $disableCount = \App\Models\Category::where(['view'=>0, 'original_id' => $category->original_id])->whereIn('shop_id', $shops)->count();
        $activeCount = \App\Models\Category::where(['view'=>1, 'original_id' => $category->original_id])->whereIn('shop_id', $shops)->count();
        $openCount = \App\Models\Category::where(['status'=>1, 'original_id' => $category->original_id])->whereIn('shop_id', $shops)->count();
        $closeCount = \App\Models\Category::where(['status'=>0, 'original_id' => $category->original_id])->whereIn('shop_id', $shops)->count();
    @endphp
    <td><span class="text-success">{{$activeCount}}</span></td>
    <td><span class="text-danger">{{$disableCount}}</span></td>
    <td><span class="text-success">{{$openCount}}</span></td>
    <td><span class="text-warning">{{$closeCount}}</span></td>
    <td class="text-end">
        <a class="btn btn-success px-2 py-1 mb-1 mx-1 rounded-0" href="{{argon_route('game.patner.status', ['user_id'=>$user->id, 'cat_id'=>$category->original_id, 'view'=>1])}}">{{__('agent.GameActive')}}</a>
        <a  class="btn btn-danger px-2 py-1 mb-1 mx-1 rounded-0" href="{{argon_route('game.patner.status', ['user_id'=>$user->id, 'cat_id'=>$category->original_id, 'view'=>0])}}">{{__('agent.GameInactive')}}</a>
        <a class="btn btn-success px-2 py-1 mb-1 mx-1 rounded-0" href="{{argon_route('game.patner.status', ['user_id'=>$user->id, 'cat_id'=>$category->original_id, 'status'=>1])}}">{{__('agent.GameEnable')}}</a>
        <a  class="btn btn-warning px-2 py-1 mb-1 mx-1 rounded-0" href="{{argon_route('game.patner.status', ['user_id'=>$user->id, 'cat_id'=>$category->original_id, 'status'=>0])}}">{{__('agent.GameDisable')}}</a>
    </td>
    @if ($loop->index > 0)
	</tr>
	@endif
@endforeach
