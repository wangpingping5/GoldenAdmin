@extends('layouts.app',[
        'parentSection' => 'convertdeal',
        'elementName' => 'deal_out'
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
                    <div class="col-md-12 col-lg-12">
                        <div class="row">
                            <p class="text-dark h5">{{__('deal_out')}}</p>
                            <form action="" method="POST"  id="form">
                            @csrf
                            <input type="hidden" id="recommender" value="{{$user->recommender}}">
                                <div class="table-responsive col-12">
                                    <table class="table table-bordered align-items-center text-start table-flush  mb-0" id="datalist">
                                        <tbody>
                                            <tr>
                                                <td>{{__('agent.Level')}}</td>
                                                <td><span class="btn {{$badge_class[$user->role_id]}} mb-0 rounded-0">[{{__($user->role->name)}}] {{$user->username}}</span></td>
                                            </tr>
                                            <tr>
                                                <td>{{__('agent.Balance')}}</td>
                                                <td>
                                                    @if ($user->hasRole('manager'))
                                                        {{number_format($user->shop->balance)}}
                                                    @else
                                                        {{number_format($user->balance)}}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>{{__('agent.Rolling')}}</td>
                                                <td>
                                                    @if ($user->hasRole('manager'))
                                                        {{number_format($user->shop->deal_balance - $user->shop->mileage)}}
                                                    @else
                                                        {{number_format($user->deal_balance - $user->mileage)}}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>{{__('agent.Amount')}}</td>
                                                <td>
                                                    <div>
                                                        <input class="form-control col-8" type="text" value="0" id="amount" name="amount">
                                                        <p></p>
                                                        <button type="button" class="btn btn-success mb-1 changeAmount" data-value="50000">50K</button>
                                                        <button type="button" class="btn btn-success mb-1 changeAmount" data-value="100000">100K</button>
                                                        <button type="button" class="btn btn-success mb-1 changeAmount" data-value="200000">200K</button>
                                                        <button type="button" class="btn btn-success mb-1 changeAmount" data-value="500000">500K</button>
                                                        <button type="button" class="btn btn-success mb-1 changeAmount" data-value="1000000">1M</button>
                                                        <button type="button" class="btn btn-primary mb-1 changeAmount" data-value="0">{{__('agent.Reset')}}</button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                                    <div class="text-center">
                                                        <button type="button" class="btn btn-primary mb-0" id="doSubmit" onclick="convert_deal_balance({{$user->id}});">{{__('agent.ConvertRolling')}}</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="alarm" aria-hidden="true" id="alarm">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{__('Notification')}}</h5>
        <button type="button btn-dark" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="msgbody"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">{{__('Close')}}</button>
      </div>
    </div>
  </div>
</div>
@stop
@push('js')
<script>
    function show_alarm(msg, aftercallback)
    {
        $('#msgbody').html(msg);
        $('#alarm').on('hidden.bs.modal', function () {
                if (aftercallback) {
                    aftercallback();
                }
        });
        $('#alarm').modal("show");
    }
    $('.changeAmount').click(function(event){
        $v = Number($('#amount').val());
        if ($(event.target).data('value') == 0)
        {
            $('#amount').val(0);
        }
        else
        {
            $('#amount').val($v + $(event.target).data('value'));
        }
    });

    function convert_deal_balance(user_id) {
        $("#doSubmit").attr('disabled', 'disabled');
        var money = $('#amount').val();
        $.ajax({
            headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
            type: 'POST',
            url: "{{argon_route('common.convert_deal_balance')}}",
            data: { summ: money, user_id:user_id},
            cache: false,
            async: false,
            success: function (data) {
                if (data.error) {
                    $("#doSubmit").attr('disabled', false);
                    if (data.code == '001') {
                    }
                    else if (data.code == '002') {
                        $('#amount').focus();
                    }
                    else if (data.code == '003') {
                        $('#amount').val('0');
                    }
                    show_alarm(data.msg);
                    return;
                }
                show_alarm({!!__('agent.FinishedConvertRolling')!!}, function() { location.reload(true);});
            },
            error: function (err, xhr) {
                show_alarm(err.responseText);
                $("#doSubmit").attr('disabled', false);
            }
        });
    }
</script>
@endpush
