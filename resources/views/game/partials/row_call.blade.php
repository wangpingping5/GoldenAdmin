<tr>
	<td>{{ $happyhour->id }}</td>
	<td>
		@if ($happyhour->admin)
		<span class="btn {{$badge_class[$happyhour->admin->role_id]}} px-2 py-1 mb-1 rounded-0">
		{{ $happyhour->admin->username }}
		</span>
		@else
		삭제된 파트너
		@endif
	</td>
	<td>
		@if ($happyhour->user)
		<a href="#" class="btn btn-primary px-2 py-1 mb-1 rounded-0" style="background-color: #20c997;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!! $happyhour->user->parents(auth()->user()->role_id, 3) !!}">{{ $happyhour->user->username }}</a>
		@else
		삭제된 회원
		@endif
	</td>
	<td>{{ number_format($happyhour->total_bank,0) }}</td>
	<td>{{ number_format($happyhour->current_bank,0) }}</td>
	<td>{{ number_format($happyhour->over_bank,0) }}</td>
	<!-- <td>{{ ['없음','메이저','그랜드'][$happyhour->jackpot] }}</td> -->
	<td>{{ $happyhour->created_at }}</td>
	<td>
		@if($happyhour->status == 2)
		{{ $happyhour->updated_at }}
		@endif
	</td>
	<td>
		@if($happyhour->status == 0)
			<span class="text-danger">차단</span>
		@elseif($happyhour->status == 1)
			<span class="text-success">활성</span>
		@else
			<span class="text-danger">완료</span>
		@endif
	</td>
	<td>
		@if ($happyhour->status != 2)
		<a href="{{ argon_route('game.call.delete', ['id'=>$happyhour->id]) }}" 
        data-method="DELETE"
        data-confirm-title="확인"
        data-confirm-text="콜을 완료하시겠습니까?"
        data-confirm-delete="확인"
        data-confirm-cancel="취소"
		onClick="$(this).css('pointer-events', 'none');">
        <button class="btn btn-warning px-3 py-1 mb-0 rounded-0" >완료</button>
    	</a>
		@endif
	</td>
</tr>