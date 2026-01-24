@extends('layouts.app',[
        'parentSection' => 'dwhistory',
        'elementName' => 'DW History'
    ])
@section('content')
<div class="container-fluid py-3">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card p-4 text-center">
                <p class="mb-0 text-dark font-weight-bold">{{__('agent.TotalDeposit')}}: <span class="text-success">{{number_format($total['add'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;&nbsp;&nbsp;{{__('agent.TotalWithdraw')}}:<span class="text-warning">{{number_format($total['out'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}</p>                 
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
                        <div class="row">                         
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="datetime-local" value="{{Request::get('dates')[0]??date('Y-m-d\T00:00')}}" id="dates" name="dates[]">
                            </div>  
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="datetime-local" value="{{Request::get('dates')[1]??date('Y-m-d\TH:i')}}" id="dates" name="dates[]">
                            </div> 
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="type" name="type">
                                        <option value="" @if (Request::get('type') == '') selected @endif>{{__('agent.All')}}</option>
                                        <option value="add" @if (Request::get('type') == 'add') selected @endif>{{__('agent.Deposit')}}</option>
                                        <option value="out" @if (Request::get('type') == 'out') selected @endif>{{__('agent.Withdraw')}}</option>
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div>  
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="role" name="role">
                                        <option value="" @if (Request::get('role') == '') selected @endif>{{__('agent.All')}}</option>
                                        <option value="1" @if (Request::get('role') == 1) selected @endif>{{__('User')}}</option>
                                        @for ($level=3;$level<=auth()->user()->role_id;$level++)
                                        <option value="{{$level}}" @if (Request::get('role') == $level) selected @endif> {{__(\App\Models\Role::find($level)->name)}}</option>
                                        @endfor
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div>       
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="text" value="{{Request::get('partner')}}" id="partner" name="partner">
                            </div>       
                            <div class="col-lg-3 col-md-6 col-12 mt-2 px-1">
                                <div class="row align-items-center">
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
                            <p class="text-dark h5">{{__('agent.DWHistory')}}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">                    
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-center table-flush p-0 mb-0" id="datalist">
                                <thead>
                                    <tr>
                                        <th class="w-auto px-1 py-2">{{__('agent.No')}}</th>
                                        <th class="w-auto px-2 py-2">{{__('agent.Level')}}</th>
                                        <th class="w-auto px-2 py-2">{{__('agent.Parent')}}</th>
                                        <th class="w-auto px-2 py-2">{{__('agent.ID')}}</th>
                                        <th class="w-auto px-2 py-2">{{__('agent.Type')}}</th>
                                        <th class="w-auto px-2 py-2">{{__('agent.Amount')}}</th>
                                        <th class="w-auto px-2 py-2">{{__('agent.Balance')}}</th>
                                        <th class="w-auto px-2 py-2">{{__('agent.PaymentAccount')}}</th>
                                        <th class="w-auto px-2 py-2">{{__('agent.DateTime')}}</th>
                                        <th class="w-auto px-2 py-2">{{__('agent.TotalDW')}}</th>                                        
                                        <th class="w-auto px-2 py-2">{{__('agent.Status')}}</th>
                                        <th class="w-auto px-2 py-2">{{__('agent.BetInfo')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($in_out_logs) > 0)
                                        @foreach ($in_out_logs as $stat)
                                            <tr>
                                                @include('dw.partials.row_manage',['history'=>true])
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="12">{{__('No Data')}}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $in_out_logs->withQueryString()->links('vendor.pagination.argon') }}</div>
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
    function dwProcess(id, type)
    {
        if(type == 'add'){
            var message = {!!__('agent.DepositAllow?')!!};
        }else{
            var message = {!!__('agent.WithdrawAllow?')!!};
        }
        if(confirm(message)){
            $("#process"+id).css('pointer-events', 'none');
            $.ajax({
                url: "{{argon_route('ec.process')}}",
                type: "GET",
                data: {id:  id},
                dataType: 'json',
                success: function (data) {   
                    alert(data.msg);             
                    if (data.error)
                    {
                        $("#process"+id).css('pointer-events', 'auto');
                    }
                    else
                    {
                        $('#searchfrm').submit();
                    }
                    
                },
                error: function () {
                    $("#process"+id).css('pointer-events', 'auto');
                }
            });
        }
    }
    function dwWait()
    {
        $("#btn_wait").css('pointer-events', 'none');
        var checkbox = $("input[name=user_CheckBox]:checked");
        var stat_ids=[];
        checkbox.each(function(i) {
            var tr = checkbox.parent().parent().eq(i);
            var td = tr.children();
            stat_ids.push(td.eq(1).text());
        });
        if(stat_ids.length == 0){
            alert({!!__('agent.NoSelectedRecord')!!});
            $("#btn_wait").css('pointer-events', 'auto');
            return;
        }
        if(confirm({!!__('agent.ConvertWaitSelectedRecords')!!})){
            $.ajax({
                url: "{{argon_route('common.wait_in_out')}}",
                type: "GET",
                data: {ids:  stat_ids},
                dataType: 'json',
                success: function (data) {
                    alert(data.msg);
                    if (data.error)
                    {
                        $("#btn_wait").css('pointer-events', 'auto');
                        if(date.code == 1){
                            $('#searchfrm').submit();    
                        }
                    }
                    else
                    {
                        $('#searchfrm').submit();
                    }
                    
                },
                error: function () {
                    $("#btn_wait").css('pointer-events', 'auto');
                }
            });
        }else{
            $("#btn_wait").css('pointer-events', 'auto');
        }
    }
</script>
@endpush
