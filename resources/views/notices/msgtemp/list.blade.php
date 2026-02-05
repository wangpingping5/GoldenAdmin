@extends('layouts.app',[
        'parentSection' => 'msgtemp',
        'elementName' => 'MsgTemp'
])
@section('content')
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">

                    <div class="collapse show">
                        <div class="card-body">
                            <div class="mt-4">      
                                <div class="border-0 mx-2 mb-sm-2">
                                    <div class="row">
                                        <div class="col">
                                            <div class="float-start"><h5 class="mb-0">{{__('MsgTemp')}}</h5></div>
                                            @if (auth()->user()->isInoutPartner())
                                            <div class="float-end">
                                                <a onClick="deleteMsgs();" class="btn btn-danger float-end me-2 rounded-0" id="btn_delete">{{__('Deleted')}}</a>
                                                <a class="btn btn-primary float-end me-2 rounded-0" onclick="window.open('{{ argon_route('msgtemp.edit',['id' => '', 'edit'=>false]) }}','{{__("Add")}}','width=800, height=780, left=500, top=100, toolbar=no, menubar=no, scrollbars=no, resizable=yes');" >{{__('Add')}}</a>                                                
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
                                                <th scope="col" class="w-8 text-center">{{__('agent.No')}}</th>
                                                <th scope="col" class="text-center">{{__('agent.Title')}}</th>
                                                <th scope="col" class="text-center">{{__('OrderBy')}}</th>
                                                <th scope="col" class="text-center">{{__('agent.DateTime')}}</th>
                                                @if (auth()->user()->hasRole('admin'))
                                                    <th scope="col" class="w-15 text-center">{{__('agent.Maker')}}</th>
                                                @endif
                                                <th scope="col" class="w-8 text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @if (count($msgtemps))
                                            @foreach ($msgtemps as $msgtemp)
                                                @include('notices.msgtemp.partials.row')
                                            @endforeach
                                        @else
                                            <tr><td colspan="7">{{__('No Data')}}</td></tr>
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
            $('tbody tr td input[name=tmp_CheckBox]').each(function(){
                $(this).prop('checked', true);
            });
        }else{
            $('tbody tr td input[name=tmp_CheckBox]').each(function(){
                $(this).prop('checked', false);
            });
        }
    });
    
    function deleteMsgs()
    {
        var checkbox = $("input[name=tmp_CheckBox]:checked");
        var user_ids=[];
        checkbox.each(function(i) {
            var tr = checkbox.parent().parent().eq(i);
            var td = tr.children();
            user_ids.push(td.eq(1).text());
        });
        var alldata = user_ids;
        if(user_ids.length == 0){
            alert("{!!__('agent.NoticeMsg5')!!}");
            return;
        }
        if(confirm("{!!__('agent.NoticeMsg6')!!}")){            
            $("#btn_delete").css('pointer-events', 'none');
            $.ajax({
                url: "{{ argon_route('msgtemp.delete') }}",
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