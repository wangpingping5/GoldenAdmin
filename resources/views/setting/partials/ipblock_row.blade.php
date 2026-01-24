<tr>   
    <td class="text-center"><input type="checkbox" class="checkitem" name="ip_CheckBox"></td>
    <td class="text-center text-dark"><span style="padding-left: 10px; padding-right: 10px; padding-top: 2px; padding-bottom: 2px;">{{$ipblock->id}}</td>
    <?php 
        $badge_class = \App\Models\User::badgeclass();
    ?>
	<td class="text-center text-white"><span class="btn mb-0 px-2 py-1 rounded-0 {{$badge_class[$ipblock->user->role_id]}}">[{{__($ipblock->user->role->name)}}]   {{ $ipblock->user->username }}</span></td>
    <td class="text-center text-dark">{{$ipblock->ip_address}}</td>
    <td class="text-center">
    @if (auth()->user()->isInoutPartner())
        <a onclick="window.open('{{ argon_route('ipblock.add', ['edit'=>true, 'id'=>$ipblock->id]) }}', 'IP {{__("agent.Change")}}','width=900, height=500, left=500, top=290, toolbar=no, menubar=no, scrollbars=no, resizable=yes');">
            <button type="button" class="btn btn-info btn-sm rounded-0 mb-0" style="background-color:#5c1ac3">{{__('agent.Change')}}</button>
        </a>
        <!-- <a href="{{ argon_route('ipblock.delete', $ipblock->id) }}"
            data-method="DELETE"
            data-confirm-title="확인"
            data-confirm-text="IP을 삭제하시겠습니까?"
            data-confirm-delete="확인"
            data-confirm-cancel="취소">
            <button type="button" class="btn-danger btn-sm">삭제</button>
        </a> -->
    @endif
    </td>
</tr>