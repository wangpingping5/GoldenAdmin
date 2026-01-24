<?php 
    $statuses = \App\Support\Enum\UserStatus::lists(); 
    $status_class = \App\Support\Enum\UserStatus::bgclass(); 
    $badge_class = \App\Models\User::badgeclass();
    $bg_class = \App\Models\User::bgclass();
    if($child_role_id > 0){
        $classname = $bg_class[$child_role_id].'-opacity';
    }else{
        $classname = '';
    }
?>
@if (count($users) > 0)
    @foreach ($users as $user)
        <tr data-tt-id="{{$user->id}}" class="{{$classname}}" data-tt-parent-id="{{$user->parent_id}}" data-tt-branch="{{($user->role_id>3 && $user->status == \App\Support\Enum\UserStatus::ACTIVE)?'true':'false'}}">
            @include('agent.partials.row')
        </tr>
    @endforeach
@else
    <tr  data-tt-id="-10" data-tt-parent-id="{{isset($child_id)?$child_id:0}}" >
        <!-- <td colspan='9'>No Data</td> -->
    </tr>
@endif