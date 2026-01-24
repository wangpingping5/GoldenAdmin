<?php 
    $badge_class = \App\Models\User::badgeclass();
?>
    <td class="text-center"><input type="checkbox" class="checkitem" name="msg_CheckBox"></td>
    <td class="text-center">{{ $msg->id }}</td>
    <td class="text-center">
        <span  class="{{count($msg->refs)>0?'partner':'shop'}}">
            @if ($msg->writer)
                @if ($msg->writer->role_id > auth()->user()->role_id)
                    {{__('CoMaster')}}
                @elseif($msg->writer->isInoutPartner())
                    <a href="{{argon_route('common.profile', ['id'=>$msg->writer->id])}}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$msg->writer->parents(auth()->user()->role_id, $msg->writer->role_id+1)!!}">
                    <span class="rounded-0 badge {{$badge_class[$msg->writer->role_id]}}">[{{__($msg->writer->role->name)}}]&nbsp;&nbsp;{{$msg->writer->username}}</span>
                    </a>
                @else
                    <a href="{{argon_route('common.profile', ['id'=>$msg->writer->id])}}">
                    <span class="rounded-0 badge {{$badge_class[$msg->writer->role_id]}}">[{{__($msg->writer->role->name)}}]&nbsp;&nbsp;{{$msg->writer->username}}</span>
                    </a>
                    
                @endif
            @elseif (isset(\App\Models\Message::RECV_NAME[$msg->writer_id]))
                {{\App\Models\Message::RECV_NAME[$msg->writer_id]}}
            @else
            {{__('Unknown')}}
            @endif
        </span>
    </td>    
    <td class="text-center">
        @if (auth()->user()->id == $msg->user_id)
        <span class="badge badge-success">{{__('agent.MessageReceive')}}</span>
        @else
        <span class="badge badge-primary">{{__('agent.MessageSend')}}</span>
        @endif
    </td>
    <td class="text-center">
        <?php
            if ($msg->writer_id == 0) //system message
            {
                $msg->content = preg_replace('/replace_with_backend/',config('app.admurl'),$msg->content);
            }
        ?>
        @if ($msg->writer)
        <a class="newMsg viewMsg" href="#" data-bs-toggle="modal" data-bs-target="#openMsgModal" data-bs-msg="{{ $msg->content }}" data-bs-id="{{$msg->id}}" data-bs-writer="{{$msg->writer->role_id>auth()->user()->role_id?{{__('CoMaster')}}:$msg->writer->username}}" data-bs-parent="{{$msg->writer->role_id>auth()->user()->role_id?{{__('CoMaster')}}:$msg->writer->parents(auth()->user()->role_id-1, $msg->writer->referral?$msg->writer->referral->role_id:$msg->writer->role_id)}}" data-bs-title="{{$msg->title}}" onclick="viewMsg(this);">
            <span class="text-primary">{{$msg->title}}</span>
        </a>
        @else
        <a class="newMsg viewMsg" href="#" data-bs-toggle="modal" data-bs-target="#openMsgModal" data-bs-msg="{{ $msg->content }}" data-bs-id="{{$msg->id}}" data-bs-writer="Unknown" data-bs-parent="Unknown" data-bs-title="{{$msg->title}}" onclick="viewMsg(this);">
            <span class="text-primary">{{$msg->title}}</span>
        </a>
        @endif
    </td>
    <td class="text-center">
        @if ($msg->user)
            @if ($msg->user->role_id > auth()->user()->role_id)
                <span class="rounded-0 badge badge-info">{{__('CoMaster')}}</span>
            @elseif($msg->user->isInoutPartner())
                <span class="rounded-0 badge {{$badge_class[$msg->user->role_id]}}">[{{__($msg->user->role->name)}}]&nbsp;&nbsp;{{$msg->user->username}}</span>
            @else
                <span class="rounded-0 badge {{$badge_class[$msg->user->role_id]}}" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$msg->user->parents(auth()->user()->role_id, $msg->user->role_id+1)!!}">[{{__($msg->user->role->name)}}]&nbsp;&nbsp;{{$msg->user->username}}</span>
            @endif
        @elseif (isset(\App\Models\Message::RECV_NAME[$msg->user_id]))
        <span class="rounded-0 badge badge-info">{{\App\Models\Message::RECV_NAME[$msg->user_id]}}</span>
        @else
        {{__('Unknown')}}
        @endif
    </td>
	<td class="text-center">{{ $msg->created_at }}</td>
    <td class="text-center">{{ $msg->read_at??__('agent.NoRead') }}</td>
    <td class="text-center">{{ $msg->count }}</td>
    <td class="text-center">
        @if (count($msg->refs)>0)
        <span class="text-green">{{__('agent.Responsed')}}</span>
        @else
        <span class="text-red">{{__('agent.NoResponsed')}}</span>
        @endif
    </td>
    
        <?php
            if ($msg->writer_id == 0) //system message
            {
                $msg->content = preg_replace('/replace_with_backend/',config('app.admurl'),$msg->content);
            }
        ?>
        @if (auth()->user()->isInOutPartner() && $msg->user_id==auth()->user()->id)
            <td class="text-center">
                <a  href="{{argon_route('msg.create', ['ref' => $msg->id, 'type'=>\Request::get('type')])}}" class="badge btn-info rounded-0">
                    {{__('agent.Response')}}
                </a>
            </td>
        @else
        <td class="text-center"></td>
        @endif
    