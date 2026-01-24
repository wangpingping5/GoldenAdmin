@extends('layouts.app',[
        'parentSection' => 'reportuser',
        'elementName' => 'Report User'
])
@section('content')
<?php
    $isInOutPartner = auth()->user()->isInOutPartner();
    $badge_class = \App\Models\User::badgeclass();
?>
<div class="container-fluid py-3">    
    <div class="row mt-4">
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
                                    <select class="form-control" id="fieldtype" name="fieldtype">
                                        <option value="bet" @if (Request::get('fieldtype') == 'bet' || Request::get('fieldtype') == '') selected @endif>{{__('agent.BetAmount')}}</option>
                                        <option value="win" @if (Request::get('fieldtype') == 'win') selected @endif>{{__('agent.WinAmount')}}</option>
                                        <option value="betwin" @if (Request::get('fieldtype') == 'betwin') selected @endif>{{__('Margin')}}</option>
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div> 
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="fieldsort" name="fieldsort">
                                        <option value="desc" @if (Request::get('fieldsort') == 'desc' || Request::get('fieldsort') == '') selected @endif>{{__('Asc')}}</option>
                                        <option value="asc" @if (Request::get('fieldsort') == 'asc') selected @endif>{{__('Desc')}}</option>
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div>   
                            <div class="col-lg-1 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="text" value="{{Request::get('user')}}" id="user" name="user">
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
                            <p class="text-dark h5">{{__('UserSettlement')}}</p>
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
                                        <th scope="col" colspan="2" class="w-auto px-1 py-2">{{__('agent.TotalDW')}}</th> 
                                        <th scope="col" colspan="3" class="w-auto px-1 py-2">{{__('live')}}</th> 
                                        <th scope="col" colspan="3" class="w-auto px-1 py-2">{{__('slot')}}</th> 
                                        <th scope="col" colspan="3" class="w-auto px-1 py-2">{{__('sports')}}</th> 
                                        <th scope="col" colspan="3" class="w-auto px-1 py-2">{{__('BoardGame')}}</th> 
                                        <th scope="col" colspan="3" class="w-auto px-1 py-2">{{__('pball')}}</th> 
                                        <th scope="col" colspan="3" class="w-auto px-1 py-2">{{__('Total')}}</th>
                                    </tr>
                                    <tr>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.BankAccount')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('agent.Manual')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Bet')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Win')}}</th>
                                        <th scope="col" class="w-auto px-1 py-2">{{__('Margin')}}</th>
                                    </tr>
                                </thead>
                                <tbody class="list">
                                    @if (count($totalstatics) > 0)
                                        @foreach ($totalstatics as $adjustment)
                                            <tr>
                                            @include('report.partials.row_user')
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="19">{{__('No Data')}}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $totalstatics->withQueryString()->links('vendor.pagination.argon') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
