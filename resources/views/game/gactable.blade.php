@extends('layouts.app',[
        'parentSection' => 'gactable',
        'elementName' => 'GAC Table'
    ])
@section('content')
<div class="container-fluid py-3">
    <?php 
        $statuses = \App\Support\Enum\UserStatus::lists(); 
        $status_class = \App\Support\Enum\UserStatus::bgclass(); 
        $badge_class = \App\Models\User::badgeclass();
    ?>
    
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">{{__('GAC Table')}}</p>
                        </div>
                        <div class="float-end">
                            <a  href="javascript:;" onClick="window.open('{{ argon_route('game.gackind') }}', '','width=600, height=400, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');" class="btn btn-primary btn-sm mb-0 rounded-0">일괄적용</a>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col">     
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-center table-flush  mb-0" id="patnertlist">
                            <thead class="thead-light">
                                <tr>
                                    <th scope="col">테이블이름</th>
                                    <th scope="col">상태</th>
                                    <th scope="col">비고</th>
                                </tr>
                            </thead>
                            <tbody class="list">
                                @foreach ($gactables as $table)
                                    <tr>
                                        <td>{{$table['title']}}</td>
                                        @if ($table['view']==0)
                                        <td>
                                            <span class="text-danger">정지</span>
                                        </td>
                                        <td>
                                            <a href="{{argon_route('game.gactable.update',['table'=>$table['gamecode'], 'view'=>1])}}" ><button class="btn btn-success px-3 py-1 mb-1 mx-1 rounded-0" >운영</button></a>
                                        </td>
                                        @else
                                        <td>
                                            <span class="text-success">운영</span>
                                        </td>
                                        <td>
                                        <a href="{{argon_route('game.gactable.update',['table'=>$table['gamecode'], 'view'=>0])}}" ><button class="btn btn-warning px-3 py-1 mb-1 mx-1 rounded-0" >정지</button></a>
                                        </td>
                                        @endif

                                    </tr>
                                @endforeach
                            </tbody>
                            </table>
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
    function open_set()
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
            alert('선택된 유저가 없습니다.');
            $("#btn_delete").css('pointer-events', 'auto');
            return;
        }
        if(confirm('선택된 모든유저들을 삭제하겠습니까?')){
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
</script>
@endpush
