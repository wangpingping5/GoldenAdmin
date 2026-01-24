
@extends('layouts.app',[
    'parentSection' => $msgParentTitle,
    'elementName' => $msgElementTitle
])

@push('css')
<link type="text/css" href="{{ asset('argon') }}/css/jquery.treetable.css" rel="stylesheet">
<link type="text/css" href="{{ asset('argon') }}/css/jquery.treetable.theme.default.css" rel="stylesheet">
@endpush
@section('content')
        <div class="modal fade-out" id="openMsgModal" role="dialog" tabindex="-1" aria-hidden="true" >
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class=" modal-content">
                        <div class="modal-header">
                        <h4 class="modal-title">{{__('Message')}}{{__('agent.Content')}}</h4>

                            <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table align-items-left table-flush">
                                <tbody>
                                    <tr>
                                        <td>{{__('agent.Sender')}}</td>
                                        <td><span id="msgWriter"></span></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('agent.Parent')}}</td>
                                        <td><span id="msgWriterParent" class="text-justify"></span></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('agent.Title')}}</td>
                                        <td><span id="msgTitle"></span></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('agent.Content')}}</td>
                                        <td><span id="msgContent" class="text-justify "></span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary"  data-bs-dismiss="modal" onclick="refreshPage();">{{__('agent.Confirm')}}</button>
                        </div>
                </div>
            </div>
        </div>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="collapse show">
                        <div class="card-body">
                            <div class="row shadow bg-light p-1 rounded-1">
                                <form action="" id="searchfrm" method="GET">
                                    <input type="hidden" name="type" id="type" value="{{Request::get('type')}}"></input>
                                    <div class="row">                  
                                        <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                            <select class="form-control" id="searchlist" name="searchlist">
                                                <option value="" @if (Request::get('searchlist') == '') selected @endif>{{__('agent.No')}}</option>
                                                <option value="1" @if (Request::get('searchlist') == 1) selected @endif>{{__('agent.Sender')}}</option>
                                                <option value="2" @if (Request::get('searchlist') == 2) selected @endif>{{__('agent.Receiver')}}</option>                                                
                                                <option value="3" @if (Request::get('searchlist') == 3) selected @endif>{{__('agent.Title')}}</option>
                                                <option value="4" @if (Request::get('searchlist') == 4) selected @endif>{{__('agent.Type')}}</option>
                                            </select>
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
                                            <div class="float-start"><h5 class="mb-0">{{$type==1?__('cs'):__('Message-list')}}</h5></div>
                                            @if (auth()->user()->isInoutPartner())
                                            <div class="float-end">
                                                <a onClick="deleteMsgs();" class="btn btn-danger float-end rounded-0 mx-2" id="btn_delete">{{__('Deleted')}}</a>
                                                @if (\Request::get('type') == 0)
                                                    <a href="{{ argon_route('msg.create', ['type'=>\Request::get('type')]) }}" class="btn btn-primary float-end rounded-0">{{__('agent.Send')}}</a>
                                                @endif
                                            </div>
                                            @endif                                        
                                        </div>
                                    </div>
                                </div>                          
                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush table-bordered"  id="msglist">
                                        <thead class="thead-light ">
                                            <tr>
                                                <th scope="col" class="w-5 text-center"><input type="checkbox" id="allCheck"></th>
                                                <th scope="col" class="text-center">{{__('agent.No')}}</th>
                                                <th scope="col" class="w-10 text-center">{{__('agent.Sender')}}</th>                                                
                                                <th scope="col" class="w-8 text-center">{{__('agent.Type')}}</th>
                                                <th scope="col" class="w-20 text-center">{{__('agent.Title')}}</th>
                                                <th scope="col" class="w-10 text-center">{{__('agent.Receiver')}}</th>
                                                <th scope="col" class="text-center">{{__('agent.MakeDateTime')}}</th>
                                                <th scope="col" class="text-center">{{__('agent.ReadDateTime')}}</th>
                                                <th scope="col" class="text-center">{{__('agent.ReadCount')}}</th>                                                
                                                <th scope="col" class="text-center">{{__('agent.Status')}}</th>                                             
                                                <th scope="col" class="text-center">{{__('agent.Notes')}}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @include('notices.messages.partials.childs')
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-2">{{ $msgs->withQueryString()->links('vendor.pagination.argon') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> 
@stop


@push('js')
<script src="{{ asset('argon') }}/js/jquery.treetable.js"></script>
<script>
    var table = $("#msglist");
    $("#msglist").treetable({ 
        expandable: true
        });
    $("#msglist").treetable("expandAll");

    $('#allCheck').change(function(){
        if($(this).prop('checked')){
            $('tbody tr td input[name=msg_CheckBox]').each(function(){
                $(this).prop('checked', true);
            });
        }else{
            $('tbody tr td input[name=msg_CheckBox]').each(function(){
                $(this).prop('checked', false);
            });
        }
    });
    function deleteMsgs()
    {
        var checkbox = $("input[name=msg_CheckBox]:checked");
        var user_ids=[];
        checkbox.each(function(i) {
            var tr = checkbox.parent().parent().eq(i);
            var td = tr.children();
            user_ids.push(td.eq(1).text());
        });
        var alldata = user_ids;
        if(user_ids.length == 0){
            alert({!!__('agent.NoticeMsg5')!!});
            return;
        }
        if(confirm({!!__('agent.NoticeMsg6')!!})){            
            $("#btn_delete").css('pointer-events', 'none');
            $.ajax({
                url: "{{ argon_route('msg.delete') }}",
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

    function viewMsg(obj) {
        var content = obj.getAttribute('data-bs-msg');
        var writer = obj.getAttribute('data-bs-writer');
        var parent = obj.getAttribute('data-bs-parent');
        var title = obj.getAttribute('data-bs-title');

        var idx = obj.getAttribute('data-bs-id');
        $.ajax({
            url: "{{argon_route('msg.readmsg')}}",
            type: "GET",
            data: {id: idx },
            success: function (data) {
                if (data.error)
                {
                    alert(data.msg);
                }
                else
                {
                }
            }
        });
        $('#msgContent').html(content);
        $('#msgWriter').html(writer);
        $('#msgWriterParent').html(parent.replaceAll('&#10;', '</br>'));
        $('#msgTitle').html(title);
    }

    function refreshPage(){
        $('#searchfrm').submit();
    }

</script>
@endpush