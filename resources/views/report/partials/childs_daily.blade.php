<?php 
    $badge_class = \App\Models\User::badgeclass();
    $bg_class = \App\Models\User::bgclass();
    if($child_role_id > 0){
        $classname = $bg_class[$child_role_id].'-opacity';
    }else{
        $classname = '';
    }
?>
@if (count($summary) > 0)
    @foreach ($summary as $adjustment)
        @if (isset($sumInfo) && $sumInfo!='')
            <tr data-tt-id="{{$adjustment->user_id }}~{{$sumInfo}}" class="{{$classname}}" data-tt-parent-id="{{isset($parent_id)?$parent_id:$adjustment->user->parent_id}}" data-tt-branch="{{$adjustment->user->role_id>3?'true':'false'}}">
                @include('report.partials.row_daily')
            </tr>
        @else
        <tr data-tt-id="{{$adjustment->user_id }}~{{date('Y-m-d',strtotime($adjustment->date))}}" class="{{$classname}}" data-tt-parent-id="{{isset($parent_id)?$parent_id:$adjustment->user->parent_id}}" data-tt-branch="{{$adjustment->user->role_id>3?'true':'false'}}">
            @include('report.partials.row_daily')
        </tr>
        @endif
    @endforeach
@else
    <tr  data-tt-id="-10" data-tt-parent-id="{{isset($parent_id)?$parent_id:0}}" >
        <!-- <td colspan='9'>No Data</td> -->
    </tr>
@endif