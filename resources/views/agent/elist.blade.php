@extends('layouts.app',[
        'parentSection' => 'patnerlist' . Request::get('role'),
        'elementName' => $roledes
    ])
@push('css')
<link type="text/css" href="{{ asset('argon') }}/css/jquery.treetable.css" rel="stylesheet">
<link type="text/css" href="{{ asset('argon') }}/css/jquery.treetable.theme.default.css" rel="stylesheet">
@endpush
@section('content')
<div class="container-fluid py-3">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card p-4 text-center">
                <p class="mb-0 text-dark font-weight-bold">{{$roledes}}{{__('agent.Count')}}: <span class="text-danger">{{number_format($total['count'], 2)}}</span>&nbsp;&nbsp;&nbsp;{{__('agent.Balance')}}:<span class="text-info">{{number_format($total['balance'], 2)}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}</p>                 
            </div>
        </div>
    </div>
    
    <?php 
        $statuses = \App\Support\Enum\UserStatus::lists(); 
        $status_class = \App\Support\Enum\UserStatus::bgclass(); 
        $badge_class = \App\Models\User::badgeclass();
    ?>
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row shadow bg-light p-1 rounded-1">
                    <form id="searchfrm" action="" method="GET" >
                        <input type="hidden" id="role" name="role" value="{{Request::get('role')?Request::get('role'):$page_role}}">
                        <div class="row">                         
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="date" value="{{Request::get('join')[0]??date('Y-01-01', strtotime('-3 year'))}}" id="join" name="join[]">
                            </div>  
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="date" value="{{Request::get('join')[1]??date('Y-m-d', strtotime('1 day'))}}" id="join" name="join[]">
                            </div> 
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="kind" name="kind">
                                        <option value="" @if (Request::get('kind') == '') selected @endif>{{__('agent.ID')}}</option>
                                        <option value="1" @if (Request::get('kind') == 1) selected @endif>{{__('agent.No')}}</option>
                                        <option value="2" @if (Request::get('kind') == 2) selected @endif>{{__('agent.Balance')}}</option>
                                        <option value="4" @if (Request::get('kind') == 4) selected @endif>{{__('agent.AccountHolder')}}</option>
                                        <option value="5" @if (Request::get('kind') == 5) selected @endif>{{__('agent.Contact')}}</option>
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div>                            
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="status" name="status">
                                        <option value="" @if (Request::get('status') == '') selected @endif>--{{__('agent.Status')}}--</option>
                                        <option value="1" @if (Request::get('status') == 1) selected @endif>{{__('Active')}}</option>
                                        <option value="2" @if (Request::get('status') == 2) selected @endif>{{__('Banned')}}</option>
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="order" name="order">
                                        <option value="" @if (Request::get('order') == '') selected @endif>{{__('agent.Ascending')}}</option>
                                        <option value="1" @if (Request::get('order') == 1) selected @endif>{{__('agent.Descending')}}</option>
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div>         
                            <div class="col-lg-1 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="text" value="{{Request::get('search')}}" id="search" name="search">
                            </div>       
                            <div class="col-lg-2 col-md-6 col-12 mt-2 px-1">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-6 pe-1">
                                        <button type="submit" class="form-control btn btn-primary">{{__('agent.Search')}}</button>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-6 ps-1">
                                        <a href="{{ argon_route('player.exportcsv') }}" class="form-control btn btn-warning">{{__('agent.ExcelSave')}}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="row mt-4">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">{{$roledes}}{{__('agent.List')}}</p>
                        </div>
                        <div class="float-end">
                            @if($deluser==1)
                            <div class="float-end px-1">
                                <button  id="btn_delete" onClick="deleteUsers();" class="btn btn-danger bnt-sm">{{__('Deleted')}}</button>
                            </div>
                            @endif
                            <div class="float-end px-1">
                                <button id="btn_disable" onClick="activeUsers(1);" class="btn btn-warning bnt-sm">{{__('Banned')}}</button>
                            </div>  
                            <div class="float-end px-1">
                                <button id="btn_active" onClick="activeUsers(0);" class="btn btn-success bnt-sm">{{__('Active')}}</button>
                            </div>  
                            <div class="float-end px-1">
                                @if(auth()->user()->role_id-1 == $page_role)
                                <a class="btn {{$badge_class[$page_role]}} bnt-sm" href="javascript:;" onClick="window.open('{{ argon_route('patner.create', ['role_id'=> $page_role, 'parent'=>auth()->user()->username]) }}', '','width=600, height=640, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');">{{ __(\App\Models\Role::where('level',$page_role)->first()->name)}} {{__('agent.New')}}</a>
                                @else
                                <a class="btn {{$badge_class[$page_role]}} bnt-sm" href="javascript:;" onClick="window.open('{{ argon_route('patner.create', ['role_id'=> $page_role]) }}', '','width=600, height=640, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');">{{ __(\App\Models\Role::where('level',$page_role)->first()->name)}} {{__('agent.New')}}</a>
                                @endif
                            </div>  
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">                    
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-center table-flush  mb-0" id="patnertlist">
                                <thead>
                                    <tr>
                                        <th class="w-auto p-0"><input type="checkbox" id="allCheck"></th>
                                        <th scope="col">{{__('agent.No')}}</th>
                                        <th scope="col">{{__('agent.ID')}}</th>
                                        <th scope="col">{{__('agent.Rolling')}}</th>
                                        @if($page_role < 7)
                                        <th scope="col">{{__('agent.ParentTree')}}</th>
                                        @endif
                                        <th scope="col">{{__('agent.Manage')}}</th>
                                        @if(auth()->user()->role_id>3)
                                        <th scope="col">{{__('agent.ManagerCount')}}</th>
                                        @endif
                                        <th scope="col">{{__('agent.UserCount')}}</th>
                                        <th scope="col">
                                            {{__('agent.AccountHolder')}}
                                            <hr class='my-0'>
                                            {{__('agent.Contact')}}
                                        </th>
                                        <th scope="col">
                                            {{__('agent.Balance')}}
                                            <hr class='my-0'>
                                            {{__('agent.Rolling')}}
                                        </th>
                                        <th scope="col">{{__('agent.TotalDW')}}</th>
                                        <th scope="col">{{__('agent.Log')}}</th>
                                        <th scope="col">{{__('agent.Status')}}</th>
                                        <th scope="col">{{__('agent.Notes')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                    <tr>
                                        @if($page_role < 7)
                                            @include('agent.partials.row', ['isparent'=>true])
                                        @else
                                            @include('agent.partials.row', ['isparent'=>false])
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $users->withQueryString()->links('vendor.pagination.argon') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
@push('js')
<script>
    $('#allCheck').change(function(){
        if($(this).prop('checked')){
            $('tbody tr td input[type="checkbox"]').each(function(){
                $(this).prop('checked', true);
            });
        }else{
            $('tbody tr td input[type="checkbox"]').each(function(){
                $(this).prop('checked', false);
            });
        }
    });
    function activeUsers(status)
    {
        if(status == 1){
            $("#btn_disable").css('pointer-events', 'none');
        }else{
            $("#btn_active").css('pointer-events', 'none');
        }
        var checkbox = $("input[name=user_CheckBox]:checked");
        var user_ids=[];
        checkbox.each(function(i) {
            var tr = checkbox.parent().parent().eq(i);
            var td = tr.children();
            user_ids.push(td.eq(1).text());
        });
        if(user_ids.length == 0){
            $("#btn_active").css('pointer-events', 'auto');
            $("#btn_disable").css('pointer-events', 'auto');
            alert("{!! __('agent.NoSelectedPartner') !!}");
            return;
        }
        $.ajax({
            url: "{{argon_route('player.active')}}",
            type: "GET",
            data: {ids:  user_ids, status:status},
            dataType: 'json',
            success: function (data) {                
                $("#btn_active").css('pointer-events', 'auto');
                $("#btn_disable").css('pointer-events', 'auto');
                if (data.error)
                {
                    alert(data.msg);
                }
                else
                {
                    $('#searchfrm').submit();
                }
                
            },
            error: function () {
            }
        });
    }
    function deleteUsers()
    {
        $("#btn_delete").css('pointer-events', 'none');
        var checkbox = $("input[name=user_CheckBox]:checked");
        var user_ids=[];
        checkbox.each(function(i) {
            var tr = checkbox.parent().parent().eq(i);
            var td = tr.children();
            user_ids.push(td.eq(1).text());
        });
        if(user_ids.length == 0){
            alert("{!! __('agent.NoSelectedPartner') !!}");
            $("#btn_delete").css('pointer-events', 'auto');
            return;
        }
        if(confirm("{!! __('agent.DeleteSelectedPartners') !!}")){
            $.ajax({
            url: "{{argon_route('player.delete')}}",
            type: "GET",
            data: {ids:  user_ids},
            dataType: 'json',
            success: function (data) {
                $("#btn_delete").css('pointer-events', 'auto');
                if (data.error)
                {
                    alert(data.msg);
                }
                else
                {
                    $('#searchfrm').submit();
                }
                
            },
            error: function () {
            }
        });
        }else{
            $("#btn_delete").css('pointer-events', 'auto');
        }
    }
    function refreshPlayerBalance(userid, dealid)
    {
        if(dealid==0){
            $('#uid_' + userid).text("{!! __('agent.RequestingBalance') !!}");
            $('#rfs_' + userid).css("pointer-events", "none");
            setTimeout(() => {
                    $('#rfs_' + userid).css("pointer-events", "auto"); 
                }, 10000);
        }else{
            $('#duid_' + userid).text("{!! __('agent.RequestingRolling') !!}");
            $('#drfs_' + userid).css("pointer-events", "none");
            setTimeout(() => {
                    $('#drfs_' + userid).css("pointer-events", "auto"); 
                }, 10000);
        }
        $.ajax({
            url: "{{argon_route('player.refresh')}}",
            type: "GET",
            data: {id:  userid, deal:dealid},
            dataType: 'json',
            success: function (data) {
                if (data.error)
                {
                    alert(data.msg);
                }
                else
                {
                    if(dealid==0){
                        $('#uid_' + userid).text(data.balance);
                    }else{
                        $('#duid_' + userid).text(data.balance);
                    }
                }
                
            },
            error: function () {
            }
        });
    }
</script>
@endpush
