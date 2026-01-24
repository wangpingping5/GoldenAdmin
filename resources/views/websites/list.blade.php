@extends('layouts.app',[
        'parentSection' => 'websitelist',
        'elementName' => 'WebsiteList'
    ])
@section('content')
    <div class="container-fluid py-4">
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">

                    <div class="collapse show">
                        <div class="card-body">
                            <div class="mt-4">      
                                <div class="border-0 mx-2 mb-sm-2">
                                    <div class="row">
                                        <div class="col">
                                            <div class="float-start"><h5 class="mb-0">{{__('WebsiteList')}}</h5></div>
                                            @if (auth()->user()->hasRole('admin'))
                                            <div class="float-end">
                                                <a onClick="deleteDomains();" class="btn btn-danger float-end me-2 rounded-0" id="btn_delete">{{__('Delete')}}</a>
                                                <a class="btn btn-primary float-end me-2 rounded-0" onclick="window.open('{{ argon_route('websites.edit',['id' => '', 'edit'=>false]) }}','{{__("agent.Domain")}} {{__("Add")}}','width=900, height=750, left=500, top=125, toolbar=no, menubar=no, scrollbars=no, resizable=yes');" >{{__('Add')}}</a>                                                
                                                 <!-- <a class="btn btn-primary" href="{{ argon_route('ipblock.add') }}">추가</a> -->
                                            </div>
                                            @endif                                        
                                        </div>
                                    </div>
                                </div>                          
                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush table-bordered">
                                        <thead class="thead-light ">
                                            <tr>
                                                <th scope="col" class="w-5 text-center"><input type="checkbox" id="allCheck"></th>
                                                <th scope="col" class="text-center">{{__('agent.ID')}}</th>
                                                <th scope="col" class="text-center">{{__('agent.Domain')}}</th>
                                                <th scope="col" class="text-center">{{__('agent.Title')}}</th>
                                                <th scope="col" class="text-center">{{__('agent.PageType')}}</th>
                                                <th scope="col" class="text-center">{{__('agent.AdminPageDomain')}}</th>
                                                <th scope="col" class="text-center">{{__('CoMaster')}}</th>
                                                <th scope="col" class="text-center">{{__('agent.DateTime')}}</th>
                                                <th scope="col" class="text-center">{{__('agent.Status')}}</th>
                                                <th scope="col"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @if (count($websites))
                                            @foreach ($websites as $website)
                                                @include('websites.partials.row')
                                            @endforeach
                                        @else
                                            <tr><td colspan="10">{{__('No Data')}}</td></tr>
                                        @endif                                        
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="dataTable-bottom">
                            </div>
                            
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
            $('tbody tr td input[name=domain_CheckBox]').each(function(){
                $(this).prop('checked', true);
            });
        }else{
            $('tbody tr td input[name=domain_CheckBox]').each(function(){
                $(this).prop('checked', false);
            });
        }
    });
    function toggle(id){
        var sats = 0;
        if(document.getElementById('switchChange' + id).innerHTML == {!! __('agent.GameDisable')!!}){
            document.getElementById('switchChange' + id).innerHTML = {!! __('agent.GameEnable')!!};
            document.getElementById('switchChange' + id).classList.add('text-primary');
            document.getElementById('switchChange' + id).classList.remove('text-danger');
            stats = 1;
        }else{
            document.getElementById('switchChange' + id).innerHTML = {!! __('agent.GameDisable')!!};
            document.getElementById('switchChange' + id).classList.add('text-danger');
            document.getElementById('switchChange' + id).classList.remove('text-primary');
            stats = 0;
        }
        $("#statusChange").css('pointer-events', 'none');
        $.ajax({
            url: "{{ argon_route('websites.status') }}",
            type: "GET",
            data: {website:  id,
                   status: stats},
            dataType: 'json',
            success: function (data) {
                $("#statusChange").css('pointer-events', 'auto');
                if (data.error)
                {
                    alert(data.msg);
                }
                else
                {
                }
                
            },
            error: function () {
            }
        });
    }
    
    function deleteDomains()
    {
        var checkbox = $("input[name=domain_CheckBox]:checked");
        var user_ids=[];
        checkbox.each(function(i) {
            var tr = checkbox.parent().parent().eq(i);
            var td = tr.children();
            user_ids.push(td.eq(1).text());
        });
        var alldata = user_ids;
        if(user_ids.length == 0){
            alert({!! __('agent.DomainMsg1')!!});
            return;
        }
        if(confirm({!! __('agent.DomainMsg2')!!})){            
            $("#btn_delete").css('pointer-events', 'none');
            $.ajax({
                url: "{{ argon_route('websites.delete') }}",
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
                        location.reload();
                    }
                    
                },
                error: function () {
                }
            });
        }
    }    
</script>
@endpush
