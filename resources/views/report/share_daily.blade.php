@extends('layouts.app',[
        'parentSection' => 'sharedaily',
        'elementName' => 'Report Share'
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
                    <span class="text-primary">{{Request::get('dates')[0]??date('Y-m-d', strtotime('-1 days'))}} ~ {{Request::get('dates')[1]??date('Y-m-d')}}</span>: 까지의</br> 
                    배팅: <span class="text-success">{{number_format($total['bet'])}}</span>원&nbsp;&nbsp;&nbsp;
                    당첨:<span class="text-warning">{{number_format($total['win'])}}</span>원&nbsp;&nbsp;&nbsp;
                    받치기배팅:<span class="text-info">{{number_format($total['sharebet'])}}</span>원&nbsp;&nbsp;&nbsp;
                    받치기당첨:<span class="text-danger">{{number_format($total['sharewin'])}}</span>원&nbsp;&nbsp;&nbsp;           
                    받치기롤링:<span class="text-dark">{{number_format($total['sharedeal'])}}</span>원&nbsp;&nbsp;&nbsp;           
                    롤링전환:<span class="text-dark">{{number_format($total['dealout'])}}</span>원</p>  
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
                                <input class="form-control" type="date" value="{{Request::get('dates')[0]??date('Y-m-d')}}" id="dates" name="dates[]">
                            </div>  
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="date" value="{{Request::get('dates')[1]??date('Y-m-d')}}" id="dates" name="dates[]">
                            </div> 
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <select class="form-control" id="partner_id" name="partner_id">
                                    <option value="" @if (Request::get('partner_id') == '') selected @endif>{{__('agent.All')}}</option>
                                </select>
                            </div>  
                            <div class="col-lg-1 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="text" value="{{Request::get('partner')}}" id="partner" name="partner">
                            </div>       
                            <div class="col-lg-2 col-md-6 col-12 mt-2 px-1">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-6 pe-1">
                                        <button type="submit" class="form-control btn btn-primary">검색하기</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="row mt-4">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">받치기 기간정산</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">                    
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-center text-sm table-flush  mb-0" id="dailydwtotal">
                                <thead>
                                    <tr>
                                        <th scope="col">기간내 합계</th>
                                        <th scope="col">총배팅금</th>
                                        <th scope="col">총당첨금</th>
                                        <th scope="col">총벳원금</th>
                                        <th scope="col">총롤링금</th>
                                        <th scope="col">총롤링전환</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{$total['daterange']}}</td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-success px-2 py-1 mb-1 rounded-0">배팅</a></td>
                                                        <td class="text-end w-70">{{number_format($total['bet'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-danger px-2 py-1 mb-1 rounded-0">파트너배팅금</a></td>
                                                        <td class="text-end w-70">{{number_format($total['betlimit'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-primary px-2 py-1 mb-1 rounded-0">받치기배팅금</a></td>
                                                        <td class="text-end w-70"> {{number_format($total['sharebet'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-success px-2 py-1 mb-1 rounded-0">당첨</a></td>
                                                        <td class="text-end w-70">{{number_format($total['win'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-danger px-2 py-1 mb-1 rounded-0">파트너당첨금</a></td>
                                                        <td class="text-end w-70">{{number_format($total['winlimit'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-primary px-2 py-1 mb-1 rounded-0">받치기당첨금</a></td>
                                                        <td class="text-end w-70"> {{number_format($total['sharewin'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-success px-2 py-1 mb-1 rounded-0">벳윈</a></td>
                                                        <td class="text-end w-70">{{number_format($total['bet']-$total['win'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-danger px-2 py-1 mb-1 rounded-0">파트너벳윈</a></td>
                                                        <td class="text-end w-70">{{number_format($total['betlimit']-$total['winlimit'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-primary px-2 py-1 mb-1 rounded-0">받치기벳윈</a></td>
                                                        <td class="text-end w-70"> {{number_format($total['sharebet']-$total['sharewin'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-success px-2 py-1 mb-1 rounded-0">롤링</a></td>
                                                        <td class="text-end w-70">{{number_format($total['deallimit']+$total['sharedeal'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-danger px-2 py-1 mb-1 rounded-0">파트너롤링금</a></td>
                                                        <td class="text-end w-70">{{number_format($total['deallimit'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-primary px-2 py-1 mb-1 rounded-0">받치기롤링금</a></td>
                                                        <td class="text-end w-70"> {{number_format($total['sharedeal'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>{{number_format($total['dealout'])}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>                        
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">받치기일별정산</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">  
                    <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-sm text-center table-flush  mb-0" id="dailylist">
                            <thead>
                                    <tr>
                                        <th scope="col">파트너아이디</th>
                                        <th scope="col">날짜</th>
                                        <th scope="col">배팅금</th>
                                        <th scope="col">당첨금</th>
                                        <th scope="col">벳원금</th>
                                        <th scope="col">롤링금</th>
                                        <th scope="col">롤링전환</th>
                                    </tr>
                                </thead>
                                <tbody class="list">
                                    @include('report.partials.childs_sharedaily')
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer py-4">
                            {{ $summary->withQueryString()->links('vendor.pagination.argon') }}
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
   var table = $("#dailylist");
    $("#dailylist").treetable({ 
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
                url: "{{argon_route('share.report.childdaily')}}?id="+node.id
                }).done(function(html) {
                    var rows = $(html).filter("tr");
                    table.treetable("loadBranch", node, rows);
                    $('#waitAjax').hide();
            });
        }
    });
</script>
@endpush
