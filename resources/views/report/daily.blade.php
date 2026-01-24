@extends('layouts.app',[
        'parentSection' => 'reportdaily',
        'elementName' => 'Report Daily'
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
                    <span class="text-primary">{{Request::get('dates')[0]??date('Y-m-d')}} ~ {{Request::get('dates')[1]??date('Y-m-d')}}</span>: </br> 
                    {{__('agent.BankDeposit')}}: <span class="text-success">{{number_format($total['totalin'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;&nbsp;&nbsp;
                    {{__('agent.BankWithdrawal')}}:<span class="text-warning">{{number_format($total['totalout'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;&nbsp;&nbsp;
                    {{__('agent.ManualDeposit')}}:<span class="text-info">{{number_format($total['moneyin'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;&nbsp;&nbsp;
                    {{__('agent.ManualWithdrawal')}}:<span class="text-danger">{{number_format($total['moneyout'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}</p>                 
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
                                        <th scope="col" rowspan="2" class="w-auto px-1 py-2">{{__('agent.ID')}}</th>
                                        <th scope="col" rowspan="2" class="w-auto px-1 py-2">{{__('agent.DateTime')}}</th>
                                        <th scope="col" colspan="2" class="w-auto px-1 py-2">{{__('agent.TotalDW')}}</th> 
                                        <th scope="col" rowspan="2" class="w-auto px-2 py-2">{{__('deal_out')}}</th> 
                                        <th scope="col" rowspan="2" class="w-auto px-2 py-2">{{__('agent.Balance')}}</th> 
                                        <th scope="col" colspan="5" class="w-auto px-1 py-2">{{__('live')}}</th> 
                                        <th scope="col" colspan="5" class="w-auto px-1 py-2">{{__('slot')}}</th> 
                                        @if(config('app.logo')!='poseidon02')
                                        <th scope="col" colspan="5" class="w-auto px-1 py-2">{{__('sports')}}</th> 
                                        <th scope="col" colspan="5" class="w-auto px-1 py-2">{{__('BoardGame')}}</th> 
                                        <th scope="col" colspan="5" class="w-auto px-1 py-2">{{__('pball')}}</th> 
                                        @endif
                                        <th scope="col" colspan="5" class="w-auto px-1 py-2">{{__('Total')}}</th>
                                    </tr>
                                    <tr>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.PaymentAccount')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Manual')}}</th>
                                        <!-- 카지노 -->
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissWin").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                        <!-- 슬롯 -->
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">(('.__("MissWin").'))</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                        @if(config('app.logo')!='poseidon02')
                                        <!-- 스포츠 -->
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">(('.__("MissWin").'))</span>':''!!}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th>  
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                        <!-- 보드 -->
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">(('.__("MissWin").'))</span>':''!!}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th>  
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                        <!-- 파워볼 -->
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">(('.__("MissWin").'))</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th>  
                                        @endif
                                        <!-- 합계 -->
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">(('.__("MissWin").'))</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($total['id'] != '')
                                    <tr data-tt-id="{{$total['user_id'] }}~{{$total['daterange']}}" data-tt-parent-id="{{$total['user_id']}}" data-tt-branch="{{$total['role_id']>3?'true':'false'}}">
                                    @else
                                    <tr >
                                    @endif
                                        <td><a class="text-primary" href="javascript:;" onclick="userClick({{$total['role_id']}}, '{{$total['id']}}')"><span class="btn {{$badge_class[$total['role_id']]}} px-2 py-1 mb-0 rounded-0">[{{__(\App\Models\Role::find($total['role_id'])->name)}}]</span></br>{{$total['id']}}</a></td>
                                        <td>{{$total['daterange']}}</td>
                                        <!-- 계좌충환전 -->
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Deposit')}}</a></td>
                                                        <td class="text-end w-70">{{number_format($total['totalin'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('agent.Withdraw')}}</a></td>
                                                        <td class="text-end w-70">{{number_format($total['totalout'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('Summary')}}</a></td>
                                                        <td class="text-end w-70"> {{number_format($total['totalin'] - $total['totalout'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <!-- 수동충환전 -->
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Deposit')}}</a></td>
                                                        <td class="text-end w-70">{{number_format($total['moneyin'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('agent.Withdraw')}}</a></td>
                                                        <td class="text-end w-70">{{number_format($total['moneyout'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><a href="javascript:;" class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('Summary')}}</a></td>
                                                        <td class="text-end w-70"> {{number_format($total['moneyin'] - $total['moneyout'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <!-- 롤링전환 -->
                                        <td >{{number_format($total['dealout'])}}</td>
                                        <td >{{number_format($total['balance']+$total['childsum'])}}</td>
                                        <!-- 카지노 -->
                                        @if(isset($total['betwin']['live']))
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['live']['totalbet']) . '/<span class="text-warning">('. number_format($total['betwin']['live']['totaldealbet']) .')</span>':number_format($total['betwin']['live']['totaldealbet'])!!}
                                        </td>
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['live']['totalwin']) . '/<span class="text-warning">('. number_format($total['betwin']['live']['totaldealwin']) .')</span>':number_format($total['betwin']['live']['totaldealwin'])!!}
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['live']['total_deal'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['live']['total_mileage'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['live']['total_deal']-$total['betwin']['live']['total_mileage'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['live']['total_ggr'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['live']['total_ggr_mileage'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['live']['total_ggr']-$total['betwin']['live']['total_ggr_mileage'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['live']['totalbet'] - $total['betwin']['live']['totalwin'] - ($total['betwin']['live']['total_deal']>0?$total['betwin']['live']['total_deal']:$total['betwin']['live']['total_mileage'])) . '/<span class="text-warning">('. number_format($total['betwin']['live']['totaldealbet'] - $total['betwin']['live']['totaldealwin'] -($total['betwin']['live']['total_deal']>0?$total['betwin']['live']['total_deal']:$total['betwin']['live']['total_mileage'])) .')</span>':number_format($total['betwin']['live']['totaldealbet'] - $total['betwin']['live']['totaldealwin'] -($total['betwin']['live']['total_deal']>0?$total['betwin']['live']['total_deal']:$total['betwin']['live']['total_mileage']))!!}
                                        </td>
                                        @else
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        @endif

                                        <!-- 슬롯 -->
                                        @if(isset($total['betwin']['slot']))
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['slot']['totalbet']) . '/<span class="text-warning">('. number_format($total['betwin']['slot']['totaldealbet']) .')</span>':number_format($total['betwin']['slot']['totaldealbet'])!!}
                                        </td>
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['slot']['totalwin']) . '/<span class="text-warning">('. number_format($total['betwin']['slot']['totaldealwin']) .')</span>':number_format($total['betwin']['slot']['totaldealwin'])!!}
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['slot']['total_deal'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['slot']['total_mileage'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['slot']['total_deal']-$total['betwin']['slot']['total_mileage'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['slot']['total_ggr'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['slot']['total_ggr_mileage'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['slot']['total_ggr']-$total['betwin']['slot']['total_ggr_mileage'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['slot']['totalbet'] - $total['betwin']['slot']['totalwin'] - ($total['betwin']['slot']['total_deal']>0?$total['betwin']['slot']['total_deal']:$total['betwin']['slot']['total_mileage'])) . '/<span class="text-warning">('. number_format($total['betwin']['slot']['totaldealbet'] - $total['betwin']['slot']['totaldealwin'] -($total['betwin']['slot']['total_deal']>0?$total['betwin']['slot']['total_deal']:$total['betwin']['slot']['total_mileage'])) .')</span>':number_format($total['betwin']['slot']['totaldealbet'] - $total['betwin']['slot']['totaldealwin'] -($total['betwin']['slot']['total_deal']>0?$total['betwin']['slot']['total_deal']:$total['betwin']['slot']['total_mileage']))!!}
                                        </td>
                                        @else
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        @endif
                                        @if(config('app.logo')!='poseidon02')
                                        <!-- 스포츠 -->
                                        @if(isset($total['betwin']['sports']))
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['sports']['totalbet']) . '/<span class="text-warning">('. number_format($total['betwin']['sports']['totaldealbet']) .')</span>':number_format($total['betwin']['sports']['totaldealbet'])!!}
                                        </td>
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['sports']['totalwin']) . '/<span class="text-warning">('. number_format($total['betwin']['sports']['totaldealwin']) .')</span>':number_format($total['betwin']['sports']['totaldealwin'])!!}
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['sports']['total_deal'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['sports']['total_mileage'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['sports']['total_deal']-$total['betwin']['sports']['total_mileage'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['sports']['total_ggr'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['sports']['total_ggr_mileage'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['sports']['total_ggr']-$total['betwin']['sports']['total_ggr_mileage'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['sports']['totalbet'] - $total['betwin']['sports']['totalwin'] - ($total['betwin']['sports']['total_deal']>0?$total['betwin']['sports']['total_deal']:$total['betwin']['sports']['total_mileage'])) . '/<span class="text-warning">('. number_format($total['betwin']['sports']['totaldealbet'] - $total['betwin']['sports']['totaldealwin'] -($total['betwin']['sports']['total_deal']>0?$total['betwin']['sports']['total_deal']:$total['betwin']['sports']['total_mileage'])) .')</span>':number_format($total['betwin']['sports']['totaldealbet'] - $total['betwin']['sports']['totaldealwin'] -($total['betwin']['sports']['total_deal']>0?$total['betwin']['sports']['total_deal']:$total['betwin']['sports']['total_mileage']))!!}
                                        </td>
                                        @else
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        @endif

                                        <!-- 보드 -->
                                        @if(isset($total['betwin']['card']))
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['card']['totalbet']) . '/<span class="text-warning">('. number_format($total['betwin']['card']['totaldealbet']) .')</span>':number_format($total['betwin']['card']['totaldealbet'])!!}
                                        </td>
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['card']['totalwin']) . '/<span class="text-warning">('. number_format($total['betwin']['card']['totaldealwin']) .')</span>':number_format($total['betwin']['card']['totaldealwin'])!!}
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['card']['total_deal'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['card']['total_mileage'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['card']['total_deal']-$total['betwin']['card']['total_mileage'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['card']['total_ggr'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['card']['total_ggr_mileage'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['card']['total_ggr']-$total['betwin']['card']['total_ggr_mileage'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['card']['totalbet'] - $total['betwin']['card']['totalwin'] - ($total['betwin']['card']['total_deal']>0?$total['betwin']['card']['total_deal']:$total['betwin']['card']['total_mileage'])) . '/<span class="text-warning">('. number_format($total['betwin']['card']['totaldealbet'] - $total['betwin']['card']['totaldealwin'] -($total['betwin']['card']['total_deal']>0?$total['betwin']['card']['total_deal']:$total['betwin']['card']['total_mileage'])) .')</span>':number_format($total['betwin']['card']['totaldealbet'] - $total['betwin']['card']['totaldealwin'] -($total['betwin']['card']['total_deal']>0?$total['betwin']['card']['total_deal']:$total['betwin']['card']['total_mileage']))!!}
                                        </td>
                                        @else
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        @endif
                                        
                                        <!-- 파워볼 -->
                                        @if(isset($total['betwin']['pball']))
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['pball']['totalbet']) . '/<span class="text-warning">('. number_format($total['betwin']['pball']['totaldealbet']) .')</span>':number_format($total['betwin']['pball']['totaldealbet'])!!}
                                        </td>
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['pball']['totalwin']) . '/<span class="text-warning">('. number_format($total['betwin']['pball']['totaldealwin']) .')</span>':number_format($total['betwin']['pball']['totaldealwin'])!!}
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['pball']['total_deal'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['pball']['total_mileage'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['pball']['total_deal']-$total['betwin']['pball']['total_mileage'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['pball']['total_ggr'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['pball']['total_ggr_mileage'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['pball']['total_ggr']-$total['betwin']['pball']['total_ggr_mileage'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['pball']['totalbet'] - $total['betwin']['pball']['totalwin'] - ($total['betwin']['pball']['total_deal']>0?$total['betwin']['pball']['total_deal']:$total['betwin']['pball']['total_mileage'])) . '/<span class="text-warning">('. number_format($total['betwin']['pball']['totaldealbet'] - $total['betwin']['pball']['totaldealwin'] -($total['betwin']['pball']['total_deal']>0?$total['betwin']['pball']['total_deal']:$total['betwin']['pball']['total_mileage'])) .')</span>':number_format($total['betwin']['pball']['totaldealbet'] - $total['betwin']['pball']['totaldealwin'] -($total['betwin']['pball']['total_deal']>0?$total['betwin']['pball']['total_deal']:$total['betwin']['pball']['total_mileage']))!!}
                                        </td>
                                        @else
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        @endif
                                        @endif
                                        <!-- 합계 -->
                                        @if(isset($total['betwin']['total']))
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['total']['totalbet']) . '/<span class="text-warning">('. number_format($total['betwin']['total']['totaldealbet']) .')</span>':number_format($total['betwin']['total']['totaldealbet'])!!}
                                        </td>
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['total']['totalwin']) . '/<span class="text-warning">('. number_format($total['betwin']['total']['totaldealwin']) .')</span>':number_format($total['betwin']['total']['totaldealwin'])!!}
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['total']['total_deal'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['total']['total_mileage'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['total']['total_deal']-$total['betwin']['total']['total_mileage'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            <table>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.All')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['total']['total_ggr'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.Childs')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['total']['total_ggr_mileage'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Self')}}</span></td>
                                                        <td class="text-end w-90">{{ number_format($total['betwin']['total']['total_ggr']-$total['betwin']['total']['total_ggr_mileage'])}}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td>
                                            {!!$isInOutPartner?number_format($total['betwin']['total']['totalbet'] - $total['betwin']['total']['totalwin'] - ($total['betwin']['total']['total_deal']>0?$total['betwin']['total']['total_deal']:$total['betwin']['total']['total_mileage'])) . '/<span class="text-warning">('. number_format($total['betwin']['total']['totaldealbet'] - $total['betwin']['total']['totaldealwin'] -($total['betwin']['total']['total_deal']>0?$total['betwin']['total']['total_deal']:$total['betwin']['total']['total_mileage'])) .')</span>':number_format($total['betwin']['total']['totaldealbet'] - $total['betwin']['total']['totaldealwin'] -($total['betwin']['total']['total_deal']>0?$total['betwin']['total']['total_deal']:$total['betwin']['total']['total_mileage']))!!}
                                        </td>
                                        @else
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>0</td>
                                        @endif
                                    </tr>
                                </tbody>
                            </table>
                        </div>                        
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">{{__('DailySettlement')}}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">  
                    <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-sm text-center table-flush  mb-0" id="dailydwlist">
                            <thead>
                                    <tr>
                                        <th scope="col" rowspan="2" class="w-auto px-1 py-2">{{__('agent.ID')}}</th>
                                        <th scope="col" rowspan="2" class="w-auto px-1 py-2">{{__('agent.DateTime')}}</th>
                                        <th scope="col" colspan="2" class="w-auto px-1 py-2">{{__('agent.TotalDW')}}</th> 
                                        <th scope="col" rowspan="2" class="w-auto px-2 py-2">{{__('agent.ConvertRolling')}}</th> 
                                        <th scope="col" rowspan="2" class="w-auto px-2 py-2">{{__('agent.Balance')}}</th> 
                                        <th scope="col" colspan="5" class="w-auto px-1 py-2">{{__('live')}}</th> 
                                        <th scope="col" colspan="5" class="w-auto px-1 py-2">{{__('slot')}}</th> 
                                        @if(config('app.logo')!='poseidon02')
                                        <th scope="col" colspan="5" class="w-auto px-1 py-2">{{__('sports')}}</th> 
                                        <th scope="col" colspan="5" class="w-auto px-1 py-2">{{__('BoardGame')}}</th> 
                                        <th scope="col" colspan="5" class="w-auto px-1 py-2">{{__('pball')}}</th> 
                                        @endif
                                        <th scope="col" colspan="5" class="w-auto px-1 py-2">{{__('Total')}}</th>
                                        @if (auth()->user()->hasRole('admin'))
                                        <th scope="col" rowspan="2" class="w-auto px-1 py-2">{{__('ProfitMargin')}}</th>
                                        @endif
                                    </tr>
                                    <tr>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.PaymentAccount')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Manual')}}</th>
                                        <!-- 카지노 -->
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissWin").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                        <!-- 슬롯 -->
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">(('.__("MissWin").'))</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                        @if(config('app.logo')!='poseidon02')
                                        <!-- 스포츠 -->
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">(('.__("MissWin").'))</span>':''!!}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th>  
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                        <!-- 보드 -->
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">(('.__("MissWin").'))</span>':''!!}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th>  
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                        <!-- 파워볼 -->
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">(('.__("MissWin").'))</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                        @endif 
                                        <!-- 합계 -->
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}{!!$isInOutPartner?'/<span class="text-warning">('.__("MissBet").')</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}{!!$isInOutPartner?'/<span class="text-warning">(('.__("MissWin").'))</span>':''!!}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Rolling')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('GGR')}}</th> 
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th> 
                                    </tr>
                                </thead>
                                <tbody class="list">
                                    @if (count($summary) > 0)
                                    @include('report.partials.childs_daily', ['child_role_id'=>0])
                                    @else
                                    <tr><td colspan="37">{{__('No Data')}}</td></tr>
                                    @endif
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
