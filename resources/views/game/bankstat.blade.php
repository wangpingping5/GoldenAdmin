@extends('layouts.app',[
        'parentSection' => 'gamebank',
        'elementName' => 'Game Bank'
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
                <div class="row mt-4">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">{{__('Game Bank')}}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">                    
                        <form action="{{argon_route('game.banksetting')}}" method="POST" >
                        @csrf
                            <div class="form-group row justify-content-center">
                                <label for="minslot1" class="col-md-2 col-form-label form-control-label text-center">최소슬롯환수금1</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="minslot1" value="{{ $minslot1?$minslot1->value:'' }}" placeholder="">
                                </div>
                                <label for="maxslot1" class="col-md-2 col-form-label form-control-label text-center">최대슬롯환수금1</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="maxslot1" value="{{ $maxslot1?$maxslot1->value:'' }}" placeholder="">
                                </div>
                            </div>
                            <div class="form-group row justify-content-center">
                                <label for="minslot2" class="col-md-2 col-form-label form-control-label text-center">최소슬롯환수금2</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="minslot2" value="{{ $minslot2?$minslot2->value:'' }}" placeholder="">
                                </div>
                                <label for="maxslot2" class="col-md-2 col-form-label form-control-label text-center">최대슬롯환수금2</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="maxslot2" value="{{ $maxslot2?$maxslot2->value:'' }}" placeholder="">
                                </div>
                            </div>
                            <div class="form-group row justify-content-center">
                                <label for="minslot3" class="col-md-2 col-form-label form-control-label text-center">최소슬롯환수금3</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="minslot3" value="{{ $minslot3?$minslot3->value:'' }}" placeholder="">
                                </div>
                                <label for="maxslot3" class="col-md-2 col-form-label form-control-label text-center">최대슬롯환수금3</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="maxslot3" value="{{ $maxslot3?$maxslot3->value:'' }}" placeholder="">
                                </div>
                            </div>
                            <div class="form-group row justify-content-center">
                                <label for="minslot4" class="col-md-2 col-form-label form-control-label text-center">최소슬롯환수금4</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="minslot4" value="{{ $minslot4?$minslot4->value:'' }}" placeholder="">
                                </div>
                                <label for="maxslot4" class="col-md-2 col-form-label form-control-label text-center">최대슬롯환수금4</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="maxslot4" value="{{ $maxslot4?$maxslot4->value:'' }}" placeholder="">
                                </div>
                            </div>
                            <div class="form-group row justify-content-center">
                                <label for="minslot5" class="col-md-2 col-form-label form-control-label text-center">최소슬롯환수금5</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="minslot5" value="{{ $minslot5?$minslot5->value:'' }}" placeholder="">
                                </div>
                                <label for="maxslot5" class="col-md-2 col-form-label form-control-label text-center">최대슬롯환수금5</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="maxslot5" value="{{ $maxslot5?$maxslot5->value:'' }}" placeholder="">
                                </div>
                            </div>
                            <div class="form-group row justify-content-center">
                                <label for="minbonus1" class="col-md-2 col-form-label form-control-label text-center">최소보너스환수금1</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="minbonus1" value="{{ $minbonus1?$minbonus1->value:'' }}" placeholder="">
                                </div>
                                <label for="maxbonus1" class="col-md-2 col-form-label form-control-label text-center">최대보너스환수금1</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="maxbonus1" value="{{ $maxbonus1?$maxbonus1->value:'' }}" placeholder="">
                                </div>
                            </div>
                            <div class="form-group row justify-content-center">
                                <label for="minbonus2" class="col-md-2 col-form-label form-control-label text-center">최소보너스환수금2</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="minbonus2" value="{{ $minbonus2?$minbonus2->value:'' }}" placeholder="">
                                </div>
                                <label for="maxbonus2" class="col-md-2 col-form-label form-control-label text-center">최대보너스환수금2</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="maxbonus2" value="{{ $maxbonus2?$maxbonus2->value:'' }}" placeholder="">
                                </div>
                            </div>
                            <div class="form-group row justify-content-center">
                                <label for="minbonus3" class="col-md-2 col-form-label form-control-label text-center">최소보너스환수금3</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="minbonus3" value="{{ $minbonus3?$minbonus3->value:'' }}" placeholder="">
                                </div>
                                <label for="maxbonus3" class="col-md-2 col-form-label form-control-label text-center">최대보너스환수금3</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="maxbonus3" value="{{ $maxbonus3?$maxbonus3->value:'' }}" placeholder="">
                                </div>
                            </div>
                            <div class="form-group row justify-content-center">
                                <label for="minbonus4" class="col-md-2 col-form-label form-control-label text-center">최소보너스환수금4</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="minbonus4" value="{{ $minbonus4?$minbonus4->value:'' }}" placeholder="">
                                </div>
                                <label for="maxbonus4" class="col-md-2 col-form-label form-control-label text-center">최대보너스환수금4</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="maxbonus4" value="{{ $maxbonus4?$maxbonus4->value:'' }}" placeholder="">
                                </div>
                            </div>
                            <div class="form-group row justify-content-center">
                                <label for="minbonus5" class="col-md-2 col-form-label form-control-label text-center">최소보너스환수금5</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="minbonus5" value="{{ $minbonus5?$minbonus5->value:'' }}" placeholder="">
                                </div>
                                <label for="maxbonus5" class="col-md-2 col-form-label form-control-label text-center">최대보너스환수금5</label>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="maxbonus5" value="{{ $maxbonus5?$maxbonus5->value:'' }}" placeholder="">
                                </div>
                            </div>
                            <div class="form-group row justify-content-center">
                                <label for="reset_bank" class="col-md-2 col-form-label form-control-label text-center">리셋주기</label>
                                <div class="col-md-3">
                                {!! Form::select('reset_bank',
                                    ['1시간','2시간','6시간','12시간','매일'], $reset_bank?$reset_bank->value:0, ['class' => 'form-control', 'style' => 'width: 100%;', 'id' => 'reset_bank']) !!}
                                </div>
                                <div class="col-md-5"></div>
                            </div>
                                
                            <div class="form-group row justify-content-center">
                                <button type="submit" class="btn btn-primary mb-0 col-md-4 col-5 mx-2">설정</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
@push('js')
<script>
    function toggleview(cat_id)
    {
        $("#chkview" + cat_id).css('pointer-events', 'none');
        var checked = $("#chkview" + cat_id).is(':checked');
        var view = 0;
        if(checked == true){
            $("#spview" + cat_id).text('사용');
            $("#spview" + cat_id).attr('class', 'text-green');
            view = 1;
        }else{
            $("#spview" + cat_id).text('내림');
            $("#spview" + cat_id).attr('class', 'text-danger');
        }
        $.ajax({
            url: "{{argon_route('game.domain.status')}}",
            type: "GET",
            data: {cat_id:  cat_id, view:view},
            dataType: 'json',
            success: function (data) {                
                $("#chkview" + cat_id).css('pointer-events', 'auto');
                if (data.error)
                {
                    alert(data.msg);
                    if(checked == true){
                        $("#chkview" + cat_id).prop('checked', false);
                        $("#spview" + cat_id).text('내림');
                        $("#spview" + cat_id).attr('class', 'text-danger');
                    }else{
                        $("#chkview" + cat_id).prop('checked', true);
                        $("#spview" + cat_id).text('사용');
                        $("#spview" + cat_id).attr('class', 'text-green');
                    }
                }
                else
                {
                    
                }
                
            },
            error: function () {
            }
        });
    }
    function togglestatus(cat_id)
    {
        $("#chkstatus" + cat_id).css('pointer-events', 'none');
        var checked = $("#chkstatus" + cat_id).is(':checked');
        var status = 0;
        if(checked == true){
            $("#spstatus" + cat_id).text('운영');
            $("#spstatus" + cat_id).attr('class', 'text-green');
            status = 1;
        }else{
            $("#spstatus" + cat_id).text('점검');
            $("#spstatus" + cat_id).attr('class', 'text-danger');
        }
        $.ajax({
            url: "{{argon_route('game.domain.status')}}",
            type: "GET",
            data: {cat_id:  cat_id, status:status},
            dataType: 'json',
            success: function (data) {                
                $("#chkstatus" + cat_id).css('pointer-events', 'auto');
                if (data.error)
                {
                    alert(data.msg);
                    if(checked == true){
                        $("#chkstatus" + cat_id).prop('checked', false);
                        $("#spstatus" + cat_id).text('운영');
                        $("#spstatus" + cat_id).attr('class', 'text-danger');
                    }else{
                        $("#chkstatus" + cat_id).prop('checked', true);
                        $("#spstatus" + cat_id).text('점검');
                        $("#spstatus" + cat_id).attr('class', 'text-green');
                    }
                }
                else
                {
                    
                }
                
            },
            error: function () {
            }
        });
    }
</script>
@endpush
