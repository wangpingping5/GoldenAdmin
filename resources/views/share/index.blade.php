@extends('layouts.app',[
        'parentSection' => 'sharelist',
        'elementName' => 'Share Setting'
    ])
@section('content')
<div class="container-fluid py-3">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card p-4 text-center">
                <p class="mb-0 text-dark font-weight-bold">총본사수: <span class="text-success">{{number_format($total['count'])}}</span>명&nbsp;&nbsp;&nbsp;총 받치기롤링금:<span class="text-info">{{number_format($total['deal'])}}</span>원</p>                 
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
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <select class="form-control" id="kind" name="kind">
                                    <option value="" @if (Request::get('kind') == '') selected @endif>총본사</option>
                                </select>
                            </div>        
                            <div class="col-lg-1 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="text" value="{{Request::get('search')}}" id="search" name="search">
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
                            <p class="text-dark h5">{{__('Share Setting')}}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">                    
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-center table-flush  mb-0" id="patnertlist">
                                <thead>
                                    <tr>
                                        <th scope="col">번호</th>
                                        <th scope="col">총본사</th>
                                        <th scope="col">보유금</th>
                                        <th scope="col">받치기롤링금</th>
                                        <th scope="col">상태</th>
                                        <th scope="col">비고</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($users) > 0)
                                        @foreach ($users as $user)
                                            <tr>
                                                @include('share.partials.row')
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="6">{{__('No Data')}}</td></tr>
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
            alert('선택된 파트너가 없습니다.');
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
            alert('선택된 파트너가 없습니다.');
            $("#btn_delete").css('pointer-events', 'auto');
            return;
        }
        if(confirm('선택된 모든파트너들을 삭제하겠습니까?')){
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
    function refreshPlayerBalance(userid, dealid)
    {
        if(dealid==0){
            $('#uid_' + userid).text('보유금요청중...');
            $('#rfs_' + userid).css("pointer-events", "none");
            setTimeout(() => {
                    $('#rfs_' + userid).css("pointer-events", "auto"); 
                }, 10000);
        }else{
            $('#duid_' + userid).text('롤링금요청중...');
            $('#drfs_' + userid).css("pointer-events", "none");
            setTimeout(() => {
                    $('#drfs_' + userid).css("pointer-events", "auto"); 
                }, 10000);
        }
        $.ajax({
            url: "{{argon_route('player.refresh')}}",
            type: "GET",
            data: {id:  userid, deal:dealid},
            dataType: 'json',
            success: function (data) {
                if (data.error)
                {
                    alert(data.msg);
                }
                else
                {
                    if(dealid==0){
                        $('#uid_' + userid).text(data.balance);
                    }else{
                        $('#duid_' + userid).text(data.balance);
                    }
                }
                
            },
            error: function () {
            }
        });
    }
</script>
@endpush
