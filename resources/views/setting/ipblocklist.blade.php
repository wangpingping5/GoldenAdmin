@extends('layouts.app',[
        'parentSection' => 'ipblocklist',
        'elementName' => 'Ipblocks'
    ])
@section('content')
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="collapse show">
                        <div class="card-body">
                            <div class="row shadow bg-light p-1 rounded-1">
                                <form action="" id="searchfrm" method="GET">
                                    <div class="row">                  
                                        <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                            <div class="input-group input-group-alternative">
                                                    <select class="form-control" id="status" name="status">
                                                        <option value="1" @if (Request::get('status') == 1) selected @endif>{{__('Partners')}}</option>
                                                        <option value="2" @if (Request::get('status') == 2) selected @endif>{{__('agent.IP')}}</option>
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
                            <div class="mt-4">      
                                <div class="border-0 mx-2 mb-sm-2">
                                    <div class="row">
                                        <div class="col">
                                            <div class="float-start"><h5 class="mb-0">{{__('agent.IPBlockManage')}}</h5></div>
                                            @if (auth()->user()->isInoutPartner())
                                            <div class="float-end">
                                                <a onClick="deleteIPs();" class="btn btn-danger float-end rounded-0" id="btn_delete">{{__('Delete')}}</a>
                                                <a class="btn btn-primary float-end me-2 rounded-0" onclick="window.open('{{ argon_route('ipblock.add', ['edit'=>false]) }}', 'IP {{__("Add")}}','width=900, height=500, left=500, top=290, toolbar=no, menubar=no, scrollbars=no, resizable=yes');">{{__('Add')}}</a>           
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
                                                <th scope="col" class="text-center">{{__('Partners')}}</th>
                                                <th scope="col" class="w-60 text-center">IP</th>
                                                <th scope="col"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @if (count($ipblocks))
                                            @foreach ($ipblocks as $ipblock)
                                                @include('setting.partials.ipblock_row')
                                            @endforeach
                                        @else
                                        <tr><td colspan="12">{{__('No Data')}}</td></tr>
                                        @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="dataTable-bottom">
                                    @if (count($ipblocks))
                                    
                                    {{ $ipblocks->withQueryString()->links('vendor.pagination.argon') }}                                    
                                    @else
                                    
                                    @endif
                                
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
            $('tbody tr td input[name=ip_CheckBox]').each(function(){
                $(this).prop('checked', true);
            });
        }else{
            $('tbody tr td input[name=ip_CheckBox]').each(function(){
                $(this).prop('checked', false);
            });
        }
    });
    function deleteIPs()
    {
        var checkbox = $("input[name=ip_CheckBox]:checked");
        var user_ids=[];
        checkbox.each(function(i) {
            var tr = checkbox.parent().parent().eq(i);
            var td = tr.children();
            user_ids.push(td.eq(1).text());
        });
        var alldata = user_ids;
        if(user_ids.length == 0){
            alert('선택된 IP가 없습니다.');
            return;
        }
        if(confirm('선택된 모든 IP들을 삭제하겠습니까?')){            
            $("#btn_delete").css('pointer-events', 'none');
            $.ajax({
                url: "{{ argon_route('ipblock.delete') }}",
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
        }
    }
</script>
@endpush