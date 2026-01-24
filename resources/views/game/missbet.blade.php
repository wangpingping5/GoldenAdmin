@extends('layouts.app',[
        'parentSection' => 'missbet',
        'elementName' => 'MissBet'
    ])
@section('content')
<div class="container-fluid py-3">
    <?php 
        $statuses = \App\Support\Enum\UserStatus::lists(); 
        $status_class = \App\Support\Enum\UserStatus::bgclass(); 
    ?>
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4 mb-3">
                <div class="row">
                    <div class="col-md-12 col-lg-12 border">
                        <div class="row m-1">
                            <p class="text-dark h6">설정</p>
                            <div class="table-responsive col-12">
                                <form action="{{argon_route('game.missbetupdate')}}" method="POST" >
                                @csrf
                                    <div class="form-group row">
                                        <div class="col-md-1">
                                        </div>
                                        <label for="slot_total_deal" class="col-md-2 col-form-label form-control-label text-center">슬롯 총배팅수</label>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" name="slot_total_deal" value="{{$data['slot_total_deal']}}" placeholder="">
                                        </div>
                                        <label for="slot_total_miss" class="col-md-2 col-form-label form-control-label text-center">슬롯 공배팅수</label>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" name="slot_total_miss" value="{{$data['slot_total_miss']}}" placeholder="">
                                        </div>
                                        <div class="col-md-1">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-md-1">
                                        </div>
                                        <label for="table_total_deal" class="col-md-2 col-form-label form-control-label text-center">카지노 총배팅수</label>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" name="table_total_deal" value="{{$data['table_total_deal']}}" placeholder="">
                                        </div>
                                        <label for="table_total_miss" class="col-md-2 col-form-label form-control-label text-center">카지노 공배팅수</label>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" name="table_total_miss" value="{{$data['table_total_miss']}}" placeholder="">
                                        </div>
                                        <div class="col-md-1">
                                        </div>
                                    </div>   
                                    <div class="form-group row justify-content-center">
                                        <button type="submit" class="btn btn-primary mb-0 col-md-4 col-5 mx-2" id="frmclose">설정</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>  
            <div class="card p-4">
                <div class="row shadow bg-light p-1 rounded-1">
                <form id="searchfrm" action="" method="GET" >
                        <div class="row">                        
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <select class="form-control" id="kind" name="kind">
                                    <option value="" @if (Request::get('kind') == '') selected @endif>매장이름</option>
                                </select>
                            </div>          
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="text" value="{{Request::get('search')}}" id="search" name="search">
                            </div>       
                            <div class="col-lg-3 col-md-6 col-12 mt-2 px-1">
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
                            <p class="text-dark h5">{{__('MissBet')}} 설정</p>
                        </div>
                        <div class="float-end">                            
                            <div class="float-end px-1">
                                <button id="btn_01" onClick="updatemissstatus('table', 0);" class="btn btn-warning bnt-sm">카지노금지</button>
                            </div>  
                            <div class="float-end px-1">
                                <button  id="btn_02" onClick="updatemissstatus('table', 1);" class="btn btn-success bnt-sm">카지노적용</button>
                            </div> 
                            <div class="float-end px-1">
                                <button id="btn_03" onClick="updatemissstatus('slot', 0);" class="btn btn-warning bnt-sm">슬롯금지</button>
                            </div>
                            <div class="float-end px-1">
                                <button id="btn_04" onClick="updatemissstatus('slot', 1);" class="btn btn-success bnt-sm">슬롯적용</button>
                            </div>   
                            <div class="float-end px-1">
                                <button id="btn_01" onClick="updatemissstatus('all', 0);" class="btn btn-warning bnt-sm">전체금지</button>
                            </div>  
                            <div class="float-end px-1">
                                <button  id="btn_02" onClick="updatemissstatus('all', 1);" class="btn btn-success bnt-sm">전체적용</button>
                            </div> 
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">                    
                        <div class="table-responsive" style="overflow-y: hidden">
                            <table class="table table-bordered align-items-center text-center table-flush  mb-0" id="datalist">
                                <thead>
                                    <tr>
                                        <th scope="col" rowspan="2" class="w-auto p-0"><input type="checkbox" id="allCheck"></th>
                                        <th scope="col" rowspan="2">번호</th>
                                        <th scope="col" rowspan="2">매장</th>
                                        <th scope="col" colspan="2">롤링(%)</th>
                                        @if (auth()->user()->hasRole('admin'))
                                        <th scope="col" colspan="2">난수</th>
                                        @endif
                                        <th scope="col" colspan="2">공배팅상태</th>
                                    </tr>
                                    <tr>
                                        <th scope="col">슬롯</th>
                                        <th scope="col">카지노</th>
                                        @if (auth()->user()->hasRole('admin'))
                                        <th scope="col">슬롯</th>
                                        <th scope="col">카지노</th>
                                        @endif
                                        <th scope="col">슬롯</th>
                                        <th scope="col">카지노</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($shops) > 0)
                                        @foreach ($shops as $shop)
                                            <tr>
                                                @include('game.partials.row_missbet')
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                        @if (auth()->user()->hasRole('admin'))
                                        <td colspan="9">{{__('No Data')}}</td>
                                        @else
                                        <td colspan="7">{{__('No Data')}}</td>
                                        @endif
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $shops->withQueryString()->links('vendor.pagination.argon') }}</div>
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
    function updateBtn(status)
    {
        for(var i = 1; i < 5; i++){
            $("#btn_0" + i).css('pointer-events', status);
        }
    }
    function updatemissstatus(type, status)
    {
        updateBtn('none');
        var checkbox = $("input[name=shop_CheckBox]:checked");
        var shop_ids=[];
        checkbox.each(function(i) {
            var tr = checkbox.parent().parent().eq(i);
            var td = tr.children();
            shop_ids.push(td.eq(1).text());
        });
        if(shop_ids.length == 0 && type != 'all'){
            updateBtn('auto');
            alert('선택된 매장이 없습니다.');
            return;
        }
        $.ajax({
            url: "{{argon_route('game.missbetstatus')}}",
            type: "GET",
            data: {type: type, ids:  shop_ids, status:status},
            dataType: 'json',
            success: function (data) {                
                updateBtn('auto');
                if (data.error == false)
                {
                    $('#searchfrm').submit();
                }
                alert(data.msg);
                
            },
            error: function () {
            }
        });
    }
</script>
@endpush
