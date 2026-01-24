@extends('layouts.app',[
        'parentSection' => 'reportgame',
        'elementName' => 'Report Game'
    ])
@push('css')
<link type="text/css" href="{{ asset('argon') }}/css/jquery.treetable.css" rel="stylesheet">
<link type="text/css" href="{{ asset('argon') }}/css/jquery.treetable.theme.default.css" rel="stylesheet">
@endpush
@section('content')
<?php
    $isInOutPartner = auth()->user()->isInOutPartner();
    $badge_class = \App\Models\User::badgeclass();
?>
<div class="container-fluid py-3">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card p-4 text-center">
                <p class="mb-0 text-dark font-weight-bold">
                    <span class="text-primary">{{Request::get('dates')[0]??date("Y-m-1")}} ~ {{Request::get('dates')[1]??date('Y-m-d')}}</span>:</br> 
                    {{__('agent.TotalBetAmount')}}: <span class="text-success">{{number_format($totalsummary[0]->totalbet)}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;&nbsp;&nbsp;
                    {{__('agent.TotalWinAmount')}}:<span class="text-warning">{{number_format($totalsummary[0]->totalwin)}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;&nbsp;&nbsp;
                    {{__('agent.TotalNetWin/Loss')}}:<span class="text-info">{{number_format($totalsummary[0]->totalbet - $totalsummary[0]->totalwin)}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;&nbsp;&nbsp;
                    {{__('agent.TotalRollingAmount')}}:<span class="text-danger">{{number_format($totalsummary[0]->totaldeal)}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}</p>                 
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row shadow bg-light p-1 rounded-1">
                    <form id="searchfrm" action="" method="GET" >
                        <input class="form-control" type="hidden" value="{{Request::get('cat_id')}}" id="cat_id"  name="cat_id">
                        <input class="form-control" type="hidden" value="{{Request::get('user_id')}}" id="user_id"  name="user_id">
                        <div class="row">                         
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="date" value="{{Request::get('dates')[0]??date('Y-m-01')}}" id="dates" name="dates[]">
                            </div>  
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="date" value="{{Request::get('dates')[1]??date('Y-m-d')}}" id="dates" name="dates[]">
                            </div> 
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <select class="form-control" id="gametype" name="gametype">
                                    <option value="" @if (Request::get('gametype') == '') selected @endif>{{__('agent.GameName')}}</option>
                                </select>
                            </div>  
                            <div class="col-lg-1 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="text" value="{{Request::get('game')}}" id="game" name="game">
                            </div>       
                            <div class="col-lg-2 col-md-6 col-12 mt-2 px-1">
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
                            <p class="text-dark h5">{{__('Summary')}}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">                    
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-center text-sm table-flush  mb-0" id="dailydwtotal">
                                <thead>
                                    <tr>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.DateTime')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.ID')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.GameName')}}</th> 
                                        <th scope="col" class="w-auto px-2 py-2">{{__('Bet')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                    </tr>
                                </thead>
                                <tbody>
                                @if (count($totalstatics) > 0)
                                    @foreach ($totalstatics as $stat)
                                        @if ($loop->index == 0)
                                            <tr><td rowspan="{{count($totalstatics)}}"></td>
                                        @else
                                            <tr>
                                        @endif
                                        @include('report.partials.row_game_details', ['total' => true])
                                        </tr>
                                    @endforeach
                                @else
                                    <tr><td colspan="7">{{__('No Data')}}</td></tr>
                                @endif
                                </tbody>
                            </table>
                        </div>                        
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">{{__('GameSettlement')}}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">  
                    <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-sm text-center table-flush  mb-0" id="dailydwlist">
                            <thead>
                                    <tr> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.DateTime')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.ID')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.GameName')}}</th> 
                                        <th scope="col" class="w-auto px-2 py-2">{{__('Bet')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                    </tr>
                                </thead>
                                <tbody class="list">
                                @if (count($categories) > 0)
                                    @foreach ($categories as $category)
                                    <tr>
                                        <td rowspan="{{count($category['cat'])}}"> {{ $category['date'] }}</td>
                                            @include('report.partials.row_game_details', ['total' => false])
                                        </tr>
                                    @endforeach
                                @else
                                    <tr><td colspan="7">{{__('No Data')}}</td></tr>
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div id="waitAjax" class="loading" style="margin-left: 0px; display:none;">
                    <img src="{{asset('argon')}}/img/theme/loading.gif">
                </div>
            </div>
        </div>
    </div>
</div>
@stop
@push('js')
<script src="{{ asset('argon') }}/js/jquery.treetable.js"></script>
<script>
   var table = $("#dailydwlist");
    $("#dailydwlist").treetable({ 
        expandable: true ,
        onNodeCollapse: function() {
            var node = this;
            table.treetable("unloadBranch", node);
        },
        onNodeExpand: function() {
            var node = this;
            table.treetable("unloadBranch", node);
            $('#waitAjax').show();
            $.ajax({
                async: true,
                url: "{{argon_route('report.childdaily.dw', 'dw')}}?id="+node.id
                }).done(function(html) {
                    var rows = $(html).filter("tr");
                    table.treetable("loadBranch", node, rows);
                    $('#waitAjax').hide();
            });
        }
    });

    var table1 = $("#dailydwtotal");
    $("#dailydwtotal").treetable({ 
        expandable: true ,
        onNodeCollapse: function() {
            var node = this;
            table1.treetable("unloadBranch", node);
        },
        onNodeExpand: function() {
            var node = this;
            table1.treetable("unloadBranch", node);
            $('#waitAjax').show();
            $.ajax({
                async: true,
                url: "{{argon_route('report.childdaily.dw', 'dw')}}?id="+node.id
                }).done(function(html) {
                    var rows = $(html).filter("tr");
                    table1.treetable("loadBranch", node, rows);
                    $('#waitAjax').hide();
            });
        }
    });
	function userClick(role_id, username){
        if(role_id < {{auth()->user()->role_id}}){
		    $('#role').val(role_id).prop('selected',true);
        }
		$('#partner').val(username);
		$('#searchfrm').submit();
	}
</script>
@endpush
