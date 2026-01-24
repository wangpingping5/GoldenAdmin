<?php 
    $badge_class = \App\Models\User::badgeclass();
?>
<tr>        
    <td class="text-center"><input type="checkbox" class="checkitem" name="notice_CheckBox"></td>
    <td class="text-center">{{$notice->order}}</td>
    <td class="text-center"><a href="" onclick="window.open('{{ argon_route('notices.edit',['id' => $notice->id, 'edit'=>true]) }}','{{__("Edit")}}','width=900, height=1080, left=500, top=100, toolbar=no, menubar=no, scrollbars=no, resizable=yes');">{{$notice->title}}</td>
	<td class="text-center">{{ $notice->date_time }}</td>
    <td class="text-center fw-bold">
            @if ($notice->popup==null || $notice->popup == 'general')
            <span class="text-info">Normal</span>
            @else
            <span class="text-danger">{{\App\Models\Notice::popups()[$notice->popup]}}</span>
            @endif
    </td>
    <td class="text-center fw-bold">
        @if($notice->type == 'all')
            <span class="text-danger">
        @elseif($notice->type == 'partner')
            <span class="text-info">
        @else
            <span class="text-primary ">
        @endif
        {{\App\Models\Notice::lists()[$notice->type]}}
            </span>
    </td>
    <td class="text-center"><span class="{{ $notice->active == 1 ? 'badge-success': 'badge-warning'}} badge rounded-0">{{ $notice->active==1?__('Active'):__('Banned') }} </span></td>
    @if (auth()->user()->hasRole('admin'))
    <td class="text-center"><span class="rounded-0 badge {{$badge_class[$notice->writer?$notice->writer->role_id:0]}}">{{$notice->writer?$notice->writer->username:__('Unknown')}}</span></td>
    @endif
    <td class="text-center">
        <a onclick="window.open('{{ argon_route('notices.edit',['id' => $notice->id, 'edit'=>true]) }}','{{__("Edit")}}','width=900, height=1080, left=500, top=100, toolbar=no, menubar=no, scrollbars=no, resizable=yes');">
            <button type="button" class="btn btn-info btn-sm mb-0 rounded-0" style="background-color:#5c1ac3">{{__('Edit')}}</button>
        </a>
    </td>
</tr>