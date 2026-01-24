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
                    {{__('agent.TotalRollingAmount')}}:<span class="text-danger">{{number_format($totalsummary[0]->total_deal)}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}</p> 
                                  
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row shadow bg-light p-1 rounded-1">
                    <form id="searchfrm" action="" method="GET" >
                        <div class="row">                         
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="date" value="{{Request::get('dates')[0]??date('Y-m-01')}}" id="dates" name="dates[]">
                            </div>  
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="date" value="{{Request::get('dates')[1]??date('Y-m-d')}}" id="dates" name="dates[]">
                            </div> 
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="gametype" name="gametype">
                                        <option value="" @if (Request::get('gametype') == '') selected @endif>{{__('agent.All')}}</option>
                                        <option value="live" @if (Request::get('gametype') == 'live') selected @endif>{{__('live')}}</option>
                                        <option value="slot" @if (Request::get('gametype') == 'slot') selected @endif>{{__('slot')}}</option>
                                        <option value="sports" @if (Request::get('gametype') == 'sports') selected @endif>{{__('sports')}}</option>
                                        <option value="pball" @if (Request::get('gametype') == 'pball') selected @endif>{{__('pball')}}</option>
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div>  
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="category_id" name="category_id">
                                        <option value="" @if (Request::get('category_id') == '') selected @endif>{{__('agent.All')}}</option>
                                        @foreach ($game_cats as $category)
                                        <option value="{{$category->original_id}}" @if (Request::get('category_id') == $category->original_id) selected @endif> {{$category->trans?$category->trans->trans_title:$category->title}}</option>
                                        @endforeach
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div> 
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="role" name="role">
                                        <option value="" @if (Request::get('role') == '') selected @endif>{{__('agent.All')}}</option>
                                        @for ($level=3;$level<auth()->user()->role_id;$level++)
                                        <option value="{{$level}}" @if (Request::get('role') == $level) selected @endif> {{__(\App\Models\Role::find($level)->name)}}</option>
                                        @endfor
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div>  
                            <div class="col-lg-1 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="text" value="{{Request::get('partner')}}" id="partner" name="partner">
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
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Provider')}}</th> 
                                        <th scope="col" class="w-auto px-2 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissWin").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th> 
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($totalstatics) > 0)
                                        @foreach ($totalstatics as $stat)
                                            @if ($loop->index == 0)
                                                <tr><td rowspan="{{count($totalstatics)+count($totalbyType)}}"></td>
                                            @else
                                                <tr>
                                            @endif
                                            @include('report.partials.row_game', ['total' => true])
                                            </tr>
                                        @endforeach
                                        @foreach ($totalbyType as $k => $stat)
                                            @if ($loop->index == 0)
                                                <tr><td rowspan="{{count($totalbyType)}}"><span class="text-red">{{__('TypeTotal')}}</span></td>
                                            @else
                                                <tr>
                                            @endif
                                            <td>{{__($k)}}</td>
                                            <td>
                                                {!!$isInOutPartner?number_format($totalbyType[$k]['totalbet']) . '/<span class="text-warning">('. number_format($totalbyType[$k]['totaldealbet']) .')</span>':number_format($totalbyType[$k]['totaldealbet'])!!}
                                            </td>
                                            <td>
                                                {!!$isInOutPartner?number_format($totalbyType[$k]['totalwin']) . '/<span class="text-warning">('. number_format($totalbyType[$k]['totaldealwin']) .')</span>':number_format($totalbyType[$k]['totaldealwin'])!!}
                                            </td>
                                            <td>
                                                <table>
                                                    <tbody>
                                                        <tr>
                                                            <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                            <td class="text-end w-90">{{ number_format($stat['total_deal'])}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                            <td class="text-end w-90">{{ number_format($stat['total_mileage'])}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                            <td class="text-end w-90">{{ number_format($stat['total_deal']-$stat['total_mileage'])}}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                            <td>
                                                <table>
                                                    <tbody>
                                                        <tr>
                                                            <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                            <td class="text-end w-90">{{ number_format($stat['total_ggr'])}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                            <td class="text-end w-90">{{ number_format($stat['total_ggr_mileage'])}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                            <td class="text-end w-90">{{ number_format($stat['total_ggr']-$stat['total_ggr_mileage'])}}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
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
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Provider')}}</th> 
                                        <th scope="col" class="w-auto px-2 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissWin").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th> 
                                    </tr>
                                </thead>
                                <tbody class="list">
                                @if (count($categories) > 0)
                                    @foreach ($categories as $category)
                                    <tr>
                                        <td rowspan="{{count($category['cat'])}}"> {{ $category['date'] }}</td>
                                            @include('report.partials.row_game', ['total' => false])
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
