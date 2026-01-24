@extends('layouts.app',[
        'parentSection' => 'charge',
        'elementName' => 'Charge Request'
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
                            <p class="text-dark h5">{{__('Charge Request')}}</p>
                            <form action="" method="POST"  id="form">
                            @csrf
                            <input type="hidden" id="recommender" value="{{auth()->user()->recommender}}">
                                <div class="table-responsive col-12">
                                    <table class="table table-bordered align-items-center text-start table-flush  mb-0" id="datalist">
                                        <tbody>
                                            <tr>
                                                <td>{{__('agent.Level')}}</td>
                                                <td><span class="btn {{$badge_class[auth()->user()->role_id]}} mb-0 rounded-0">{{__(auth()->user()->role->name)}}</span></td>
                                            </tr>
                                            <tr>
                                                <td>{{__('agent.Balance')}}</td>
                                                <td>
                                                    @if (auth()->user()->hasRole('manager'))
                                                        {{number_format(auth()->user()->shop->balance)}}
                                                    @else
                                                        {{number_format(auth()->user()->balance)}}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>{{__('agent.Amount')}}</td>
                                                <td>
                                                    <div>
                                                        <input class="form-control col-8" type="text" value="0" id="amount" name="amount">
                                                        <p></p>
                                                        <button type="button" class="btn btn-success mb-1 changeAmount" data-value="50000">50k</button>
                                                        <button type="button" class="btn btn-success mb-1 changeAmount" data-value="100000">100k</button>
                                                        <button type="button" class="btn btn-success mb-1 changeAmount" data-value="200000">200k</button>
                                                        <button type="button" class="btn btn-success mb-1 changeAmount" data-value="500000">500k</button>
                                                        <button type="button" class="btn btn-success mb-1 changeAmount" data-value="1000000">1M</button>
                                                        <button type="button" class="btn btn-primary mb-1 changeAmount" data-value="0">{{__('agent.Reset')}}</button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>{{__('agent.PaymentAccount')}}</td>
                                                <td>
                                                    <span>{{auth()->user()->bankInfo(true)}}</span> 
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>{{__('agent.PaymentAccount')}}</td>
                                                <td>
                                                    <div>
                                                        <span class="m-auto" id="bankinfo">{{__('agent.PleaseConfirmButton')}}</span> 
                                                        <button type="button" class="btn btn-warning mb-1" id="togglebankinfo">{{__('agent.ConfirmPaymentAccount')}}</button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                                    <div class="text-center">
                                                        <button type="button" class="btn btn-primary mb-0" id="doSubmit" onclick="deposit_balance();">{{__('agent.DepositRequest')}}</button>
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
                <div class="row mt-4">
                    <p class="text-dark h6">{{__('agent.RecentlryDepositRequestHistory')}}</p>
                </div>
                @include('dw.partials.recent_history')
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
    var depositAccountRequested = {{$needRequestAccount}};
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


    $('#togglebankinfo').click(function() {
        var money = $('#amount').val();
        var accountname = $('#recommender').val();
        $.ajax({
            type: 'GET',
            url: "{{argon_route('common.depositAccount')}}",
            data: { money: money, account:accountname },
            cache: false,
            async: false,
            success: function (data) {
                if (data.error) {
                    show_alarm(data.msg);
                    if (data.code == '001') {
                        location.reload(true);
                    }
                    else if (data.code == '002') {
                        $('#withdraw_money').focus();
                    }
                    else if (data.code == '003') {
                        $('#recommender').focus();
                    }
                    return;
                }
                $("#bankinfo").html(data.msg);
                depositAccountRequested = true;
                if ('url' in data)
                {
                    var leftPosition, topPosition;
                    width = 600;
                    height = 1000;
                    leftPosition = (window.screen.width / 2) - ((width / 2) + 10);
                    topPosition = (window.screen.height / 2) - ((height / 2) + 50);
                    wndGame = window.open(data.url, "Deposit",
                    "status=no,height=" + height + ",width=" + width + ",resizable=yes,left="
                    + leftPosition + ",top=" + topPosition + ",screenX=" + leftPosition + ",screenY="
                    + topPosition + ",toolbar=no,menubar=no,scrollbars=no,location=no,directories=no");
                }
                
            },
            error: function (err, xhr) {
                show_alarm(err.responseText);
            }
        });       
    });

    function deposit_balance() {
        if (!depositAccountRequested)
        {
            show_alarm({!!__('PleaseConfirmDepositAccount')!!});
            return;
        }
        $("#doSubmit").attr('disabled', 'disabled');
        var money = $('#amount').val();
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: 'POST',
            url: "{{argon_route('common.deposit')}}",
            data: { money: money },
            cache: false,
            async: false,
            success: function (data) {
                if (data.error) {
                    show_alarm(data.msg);
                    $("#doSubmit").attr('disabled', false);
                    if (data.code == '001') {
                        // location.reload(true);
                    }
                    else if (data.code == '002') {
                        $('#amount').focus();
                    }
                    else if (data.code == '003') {
                        $('#amount').val('0');
                    }
                    return;
                }
                show_alarm({!!__('DepositRequestFinish')!!}, function() { location.reload(true);});
            },
            error: function (err, xhr) {
                show_alarm(err.responseText);
                $("#doSubmit").attr('disabled', false);
            }
        });
    }
</script>
@endpush
