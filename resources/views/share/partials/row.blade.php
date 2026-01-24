<td>{{$user->id}}</td>
<td>
    <span class="btn {{$badge_class[$user->role_id]}} px-2 py-1 mb-0 rounded-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="상부트리&#10;[{{__($user->referral->role->name)}}] {{$user->referral->username}}">[{{__($user->role->name)}}] {{$user->username}}</span>
</td>

<td>{{number_format($user->balance)}}</td>
<td>{{ number_format($user->deal_balance - $user->mileage,0) }}</td>
<td>
    @if (count($user->sharebetinfo) > 0)
        @foreach ($user->sharebetinfo as $info)
        <span class="font-weight-bold">{{$info->category->trans?$info->category->trans->trans_title: $info->category->title}} : {{ number_format($info->minlimit)}}</span></br>
        @endforeach
        @else
        <span class="font-weight-bold text-warning">설정안됨</span>
    @endif
</td>
<td>
<a href="{{argon_route('game.share.setting', ['id' => $user->id])}}" ><button class="btn btn-success px-2 py-1 mb-0 rounded-0" >설정</button></a>
</td>