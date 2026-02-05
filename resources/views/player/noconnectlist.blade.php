@extends('layouts.app',[
        'parentSection' => 'noconnectlist',
        'elementName' => 'No Connect List'
    ])
@section('content')
<div class="container-fluid py-3">
    <?php 
        $statuses = \App\Support\Enum\UserStatus::lists(); 
        $status_class = \App\Support\Enum\UserStatus::bgclass(); 
    ?>
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row shadow bg-light p-1 rounded-1">
                    <form id="searchfrm" action="" method="GET" >
                        <input type="hidden" value="" name="diffday" id="diffday">
                        <div class="row">       
                            <div class="col-12">
                                <div class="align-items-center">
                                    <button type="button" onClick="frmsubmid(15);" class="btn btn-primary mb-0  px-3 py-2 rounded-0">15Days</button>
                                    <button type="button" onClick="frmsubmid(30);" class="btn btn-primary mb-0  px-3 py-2 rounded-0">30Days</button>
                                    <button type="button" onClick="frmsubmid(60);" class="btn btn-primary mb-0  px-3 py-2 rounded-0">60Days</button>
                                    <button type="button" onClick="frmsubmid(90);" class="btn btn-primary mb-0  px-3 py-2 rounded-0">90Days</button>
                                    <button type="button" onClick="frmsubmid(120);" class="btn btn-primary mb-0  px-3 py-2 rounded-0">120Days</button>
                                    <button type="button" onClick="frmsubmid(-1);" class="btn btn-primary mb-0  px-2 py-2 rounded-0">{{ __('agent.NeverLoggedIn')}}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="row mt-4">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">{{ __('No Connect List')}}</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="float-end">
                            @if($deluser==1)
                            <div class="float-end px-1">
                                <button  id="btn_delete" onClick="deleteUsers();" class="btn btn-danger bnt-sm">{{__('Deleted')}}</button>
                            </div>
                            @endif
                            <div class="float-end px-1">
                                <button id="btn_disable" onClick="activeUsers(1);" class="btn btn-warning bnt-sm">{{__('Banned')}}</button>
                            </div>  
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">                    
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-center table-flush  mb-0" id="datalist">
                                <thead>
                                    <tr>
                                        <th class="w-auto p-0"><input type="checkbox" id="allCheck"></th>
                                        <th scope="col">{{__('agent.No')}}</th>
                                        <th scope="col">{{__('agent.Level')}}</th>
                                        <th scope="col">{{__('agent.ID')}}</th>
                                        <th scope="col">{{__('agent.AccountHolder')}}</th>
                                        <th scope="col">{{__('agent.Contact')}}</th>
                                        <th scope="col">{{__('agent.Balance')}}</th>
                                        <th scope="col">{{__('agent.Rolling')}}</th>
                                        <th scope="col">{{__('agent.Deposit')}}</th>
                                        <th scope="col">{{__('agent.Withdraw')}}</th>
                                        <th scope="col">{{__('agent.RegisterDateTime')}}</th>
                                        <th scope="col">{{__('agent.RecentlyLoginDateTime')}}</th>
                                        <th scope="col">{{__('agent.Status')}}</th>
                                        <th scope="col">{{__('Message')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($users) > 0)
                                        @foreach ($users as $user)
                                            <tr>
                                                @include('player.partials.noconnect_row')
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="14">{{__('No Data')}}</td></tr>
                                    @endif
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
            alert("{!!__('agent.NoSelectedUser')!!}");
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
            alert("{!!__('agent.NoSelectedUser')!!}");
            $("#btn_delete").css('pointer-events', 'auto');
            return;
        }
        if(confirm("{!!__('agent.DeleteSelectedUsers')!!}")){
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
    function frmsubmid(diff)
    {
        $('#diffday').val(diff)
        $('#searchfrm').submit();
    }
</script>
@endpush
