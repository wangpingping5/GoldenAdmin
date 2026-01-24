@extends('layouts.app',[
        'parentSection' => 'gamedomain',
        'elementName' => 'Game Domain'
    ])
@section('content')
<div class="container-fluid py-3">
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
                        <div class="row">                        
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="kind" name="kind">
                                        <option value="" @if (Request::get('kind') == '') selected @endif>{{__('agent.Domain')}}</option>
                                        <option value="1" @if (Request::get('kind') == 1) selected @endif>{{__('agent.CoMasterName')}}</option>
                                        <option value="2" @if (Request::get('kind') == 2) selected @endif>{{__('agent.GameName')}}</option>
                                        @if (auth()->user()->hasRole('admin'))
                                        <option value="3" @if (Request::get('kind') == 3) selected @endif>{{__('agent.GameProvider')}}</option>
                                        @endif
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div>          
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="text" value="{{Request::get('search')}}" id="search" name="search">
                            </div>       
                            <div class="col-lg-3 col-md-6 col-12 mt-2 px-1">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-6 pe-1">
                                        <button type="submit" class="form-control btn btn-primary">{{__('agent.Search')}}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="row mt-4">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">{{__('Game Domain')}}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">                    
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-center table-flush  mb-0" id="datalist">
                                <thead>
                                    <tr>
                                        <th scope="col">{{__('agent.Domain')}}</th>
                                        <th scope="col">{{__('agent.CoMasterName')}}</th>
                                        <th scope="col">{{__('agent.Gamename')}}</th>
                                        <th scope="col">{{__('agent.GameProvider')}}</th>
                                        @if (auth()->user()->hasRole('admin'))
                                        <th scope="col" style="width:80px">{{__('agent.ActiveInactive')}}</th>
                                        @endif
                                        <th scope="col" style="width:80px">{{__('agent.Status')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($sites) > 0)
                                        @foreach ($sites as $site)
                                            <tr>
                                                <?php
                                                    $categories = $site->categories;
                                                    if (!auth()->user()->hasRole('admin'))
                                                    {
                                                        if(auth()->user()->isInoutPartner() && isset(auth()->user()->sessiondata()['swiching']) && auth()->user()->sessiondata()['swiching']==1)
                                                        {
                                                            $categories = $categories->filter(function($item){
                                                                return $item->view==1 || $item->title=='Pragmatic Play';
                                                            });
                                                        }
                                                        else
                                                        {
                                                            $categories = $categories->where('view',1);
                                                        }
                                                        $categories = $categories->where('parent', 0);
                                                    }
                                                    if (Request::get('kind') == 2 && Request::get('search') != '')
                                                    {
                                                        $categories = $categories->where('title', Request::get('search'));
                                                    }
                                                    if (auth()->user()->hasRole('admin') && Request::get('kind') == 3 && Request::get('search') != '')
                                                    {
                                                        $categories = $categories->where('provider', Request::get('search'));
                                                    }
                                                ?>
                                                @if (count($categories)>0)
                                                    <td rowspan="{{$categories->count()}}"> 
                                                        {{ $site->title }} 
                                                        <p>
                                                        <a class="text-primary" href="{{$site->domain}}">{{$site->domain}}</a>
                                                    </td>
                                                    <td rowspan="{{$categories->count()}}"> 
                                                        @if(isset($site->admin))
                                                        <span class="btn {{$badge_class[$site->admin->role_id]}} px-2 py-1 mb-1 mx-1 rounded-0"  data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{__($site->admin->role->name)}}">{{$site->admin->username}}</span>
                                                        @else
                                                        {{__('agent.Unknown')}}
                                                        @endif
                                                    </td>
                                                    @include('game.partials.row_domain', ['categories' => $categories])
                                                @else
                                                <td > 
                                                    {{ $site->title }} 
                                                    <p>
                                                    <a class="text-primary" href="{{$site->domain}}">{{$site->domain}}</a>
                                                </td>
                                                <td > 
                                                    @if(isset($site->admin))
                                                    <span class="btn {{$badge_class[$site->admin->role_id]}} px-2 py-1 mb-1 mx-1 rounded-0"  data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{__($site->admin->role->name)}}">{{$site->admin->username}}</span>
                                                    @else
                                                    {{__('agent.Unknown')}}
                                                    @endif
                                                </td>
                                                @if (auth()->user()->hasRole('admin'))
                                                    <td colspan="4">{{__('No Data')}}</td>
                                                @else
                                                    <td colspan="3">{{__('No Data')}}</td>
                                                @endif
                                                @endif
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                        @if (auth()->user()->hasRole('admin'))
                                            <td colspan="6">{{__('No Data')}}</td>
                                        @else
                                            <td colspan="5">{{__('No Data')}}</td>
                                        @endif    
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $sites->withQueryString()->links('vendor.pagination.argon') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
@push('js')
<script>
    function toggleview(cat_id)
    {
        $("#chkview" + cat_id).css('pointer-events', 'none');
        var checked = $("#chkview" + cat_id).is(':checked');
        var view = 0;
        if(checked == true){
            $("#spview" + cat_id).text('{!!__("agent.GameActive")!!}');
            $("#spview" + cat_id).attr('class', 'text-green');
            view = 1;
        }else{
            $("#spview" + cat_id).text('{!!__("agent.GameInactive")!!}');
            $("#spview" + cat_id).attr('class', 'text-danger');
        }
        $.ajax({
            url: "{{argon_route('game.domain.status')}}",
            type: "GET",
            data: {cat_id:  cat_id, view:view},
            dataType: 'json',
            success: function (data) {                
                $("#chkview" + cat_id).css('pointer-events', 'auto');
                if (data.error)
                {
                    alert(data.msg);
                    if(checked == true){
                        $("#chkview" + cat_id).prop('checked', false);
                        $("#spview" + cat_id).text('{!!__("agent.GameInactive")!!}');
                        $("#spview" + cat_id).attr('class', 'text-danger');
                    }else{
                        $("#chkview" + cat_id).prop('checked', true);
                        $("#spview" + cat_id).text('{!!__("agent.GameActive")!!}');
                        $("#spview" + cat_id).attr('class', 'text-green');
                    }
                }
                else
                {
                    
                }
                
            },
            error: function () {
            }
        });
    }
    function togglestatus(cat_id)
    {
        $("#chkstatus" + cat_id).css('pointer-events', 'none');
        var checked = $("#chkstatus" + cat_id).is(':checked');
        var status = 0;
        if(checked == true){
            $("#spstatus" + cat_id).text('{!!__("agent.GameEnable")!!}');
            $("#spstatus" + cat_id).attr('class', 'text-green');
            status = 1;
        }else{
            $("#spstatus" + cat_id).text('{!!__("agent.GameDisable")!!}');
            $("#spstatus" + cat_id).attr('class', 'text-danger');
        }
        $.ajax({
            url: "{{argon_route('game.domain.status')}}",
            type: "GET",
            data: {cat_id:  cat_id, status:status},
            dataType: 'json',
            success: function (data) {                
                $("#chkstatus" + cat_id).css('pointer-events', 'auto');
                if (data.error)
                {
                    alert(data.msg);
                    if(checked == true){
                        $("#chkstatus" + cat_id).prop('checked', false);
                        $("#spstatus" + cat_id).text('{!!__("agent.GameEnable")!!}');
                        $("#spstatus" + cat_id).attr('class', 'text-danger');
                    }else{
                        $("#chkstatus" + cat_id).prop('checked', true);
                        $("#spstatus" + cat_id).text('{!!__("agent.GameDisable")!!}');
                        $("#spstatus" + cat_id).attr('class', 'text-green');
                    }
                }
                else
                {
                    
                }
                
            },
            error: function () {
            }
        });
    }
</script>
@endpush
