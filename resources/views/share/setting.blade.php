@extends('layouts.app',[
        'parentSection' => 'sharelist',
        'elementName' => 'Share Setting'
    ])
@section('content')
<div class="container-fluid py-3">
    <?php 
        $statuses = \App\Support\Enum\UserStatus::lists(); 
        $status_class = \App\Support\Enum\UserStatus::bgclass(); 
        $badge_class = \App\Models\User::badgeclass();
    ?>
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card p-4 text-center">
                <div class="mb-0 text-dark font-weight-bold align-items-center">
                    <span class="btn {{$badge_class[$partner->role_id]}} px-2 py-1 mb-0 rounded-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="상부트리&#10;[{{__($partner->referral->role->name)}}] {{$partner->referral->username}}">[{{__($partner->role->name)}}] {{$partner->username}}</span>
                    &nbsp;&nbsp;&nbsp;받치기 롤링금 : <span class="text-success">{{number_format($partner->deal_balance)}}</span>
                    @if ($partner->id == auth()->user()->id)
                        &nbsp;<a class="btn btn-warning px-2 py-1 my-1 rounded-0" href="#" onclick="convert_deal_balance();">롤링금전환</a>
                    @endif 
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">{{__('Share Setting')}}</p>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col">                    
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-center table-flush  mb-0" id="patnertlist">
                                <thead>
                                    <tr>
                                        <th scope="col">게임사</th>
                                        <th scope="col">게임형식</th>
                                        <th scope="col">베팅판</th>
                                        <th scope="col">최소베팅금</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $type_counts = [
                                            'total' => 0
                                        ];
                                        foreach (\App\Models\ShareBetInfo::BET_TYPES as $type => $values)
                                        {
                                            $type_counts[$type] = count($values);
                                            $type_counts['total'] = $type_counts['total'] + count($values);
                                        }
                                    ?>
                                    @foreach ($categories as $cat)
                                    <form action="" method="POST"  id="form">
                                    @csrf
                                    <input type="hidden" value="{{$partner->id}}" name="user_id">
                                    <input type="hidden" value="{{$cat->original_id}}" name="cat_id">
                                    <tr>
                                        <td class="p-3" rowspan="{{$type_counts['total']}}">{{($cat->trans)?$cat->trans->trans_title:$cat->title}}</td>
                                        @foreach (\App\Models\ShareBetInfo::BET_TYPES as $type => $values)
                                            <?php $limitinfo = $values; ?>
                                            @if ($loop->index == 0)
                                            <td class="p-3" rowspan="{{$type_counts[$type]}}">{{__($type)}}</td>
                                            @endif
                                            @foreach ($sharebetinfos as $info)
                                                @if ($info->category_id == $cat->original_id)
                                                    <?php $partner_limitinfo = json_decode($info->limit_info, true); ?>
                                                @endif
                                            @endforeach
                                            
                                            @foreach ($limitinfo as $betname => $betvalue)
                                                @if ($loop->index > 0)
                                                <tr>
                                                @endif
                                                <td class="p-3">{{__($betname)}}</td>
                                                <td class="p-3">
                                                @if (isset($partner_limitinfo[$betname]))
                                                <?php $betvalue = $partner_limitinfo[$betname]; ?>
                                                @endif
                                                @if ($partner->id == auth()->user()->id)
                                                    {{number_format($betvalue)}}
                                                @else
                                                    <input type="text" name="{{$betname}}"  class="form-control" value="{{$betvalue}}">
                                                @endif
                                                </td>
                                                @if ($loop->index > 0)
                                                </tr>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </tr>
                                    @if ($partner->role_id < auth()->user()->role_id)
                                    <tr>
                                        <td colspan="4">
                                            <button type="button" class="btn btn-primary col-2 mb-0 rounded-0" id="doSubmit">설정</button>
                                        </td>
                                    </tr>
                                    @endif
                                    </form>
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
    $('#doSubmit').click(function () {
        $(this).attr('disabled', 'disabled');
        $('form#form').submit();
    });
    function convert_deal_balance() {
        $.ajax({
            type: 'POST',
            url: "{{argon_route('common.convert_deal_balance')}}",
            data: { summ: 0},
            cache: false,
            async: false,
            success: function (data) {
                if (data.error) {
                    alert(data.msg);
                    return;
                }
                alert('롤링금이 전환되었습니다.');
                location.reload(true);
            },
            error: function (err, xhr) {
                alert(err.responseText);
            }
        });
    }
</script>
@endpush
