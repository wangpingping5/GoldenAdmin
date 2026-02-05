@extends('layouts.app',[
        'parentSection' => 'player',
        'elementName' => 'Player Create'
    ])
@section('content')
    
<div class="container-fluid py-3">
    <div class="row">
        <div class="col col-lg-6 m-auto">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h3 class="mb-0">{{__('Player Create')}}</h3>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" autocomplete="off">
                        @csrf
                        <div class="pl-lg-4">
                            <div class="form-group{{ $errors->has('username') ? ' has-danger' : '' }}">
                                <label class="form-control-label" for="username">{{__('agent.ID')}}</label>
                                <div class="d-flex">
                                    @if(auth()->user()->role_id < 8)
                                    <div class="col-md-8 float-left">
                                        <input type="text" name="username" id="username" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" value="" required>
                                    </div>
                                    <div class="col-md-4 px-2">
                                        <button type="button" class="btn btn-danger my-0" id="btncheckId">{{__('agent.DuplicateConfirm')}}</button>
                                    </div>
                                    @else
                                    <div class="col-md-12 float-left">
                                        <input type="text" name="username" id="username" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" value="" required>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group{{ $errors->has('parent') ? ' has-danger' : '' }}">
                                <label class="form-control-label" for="parent">{{__('agent.Stores')}}</label>
                                <input type="text" name="parent" id="parent" class="form-control{{ $errors->has('parent') ? ' is-invalid' : '' }}" value="{{auth()->user()->username}}" required>
                            </div>
                            <div class="form-group{{ $errors->has('phone') ? ' has-danger' : '' }}">
                                <label class="form-control-label" for="phone">{{__('agent.Contact')}}</label>
                                <input type="text" name="phone" id="phone" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }}" value="" >
                            </div>
                            <div class="form-group{{ $errors->has('bank_name') ? ' has-danger' : '' }}">
                                <label class="form-control-label" for="bank_name">{{__('agent.Bank')}}</label>
                                @php
                                    $banks = array_combine(\App\Models\User::$values['banks'], \App\Models\User::$values['banks']);
                                @endphp
                                {!! Form::select('bank_name', $banks, '', ['class' => 'form-control', 'id' => 'bank_name']) !!}		
                            </div>
                            <div class="form-group{{ $errors->has('account_no') ? ' has-danger' : '' }}">
                                <label class="form-control-label" for="account_no">{{__('agent.AccountNumber')}}</label>
                                <input type="text" name="account_no" id="account_no" class="form-control" value="" >
                            </div>
                            <div class="form-group{{ $errors->has('recommender') ? ' has-danger' : '' }}">
                                <label class="form-control-label" for="recommender">{{__('agent.AccountHolder')}}</label>
                                <input type="text" name="recommender" id="recommender" class="form-control" value="" >
                            </div>
                            <div class="form-group table-responsive">
                                <label class="form-control-label" for="percent">{{__('agent.Rolling')}}(%)</label>
                                <table class="table table-bordered align-items-center text-center table-flush p-0 mb-0">
                                    <tbody>
                                        <tr>
                                            <td>{{__('slot')}}</td>
                                            <td><input type="text" name="deal_percent" id="deal_percent" class="form-control" value="0"></td>
                                        </tr>
                                        <tr>
                                            <td>{{__('live')}}</td>
                                            <td><input type="text" name="table_deal_percent" id="table_deal_percent" class="form-control" value="0"></td>
                                        </tr>
                                        <tr>
                                            <td>{{__('sports')}}</td>
                                            <td ><input type="text" name="sports_deal_percent" id="sports_deal_percent" class="form-control" value="0"></td>
                                        </tr>
                                        <tr>
                                            <td>{{__('BoardGame')}}</td>
                                            <td ><input type="text" name="card_deal_percent" id="card_deal_percent" class="form-control" value="0"></td>
                                        </tr>
                                        {{--
                                        <tr>
                                            <td>파워볼 단폴</td>
                                            <td><input type="text" name="pball_single_percent" id="pball_single_percent" class="form-control" value="0"></td>
                                        </tr>
                                        <tr>
                                            <td>파워볼 조합</td>
                                            <td><input type="text" name="pball_comb_percent" id="pball_comb_percent" class="form-control" value="0"></td>
                                        </tr>
                                        --}}
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-group{{ $errors->has('password') ? ' has-danger' : '' }}">
                                <label class="form-control-label" for="password">{{__('Password')}}</label>
                                <input type="password" name="password" id="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" placeholder="" value="" required>
                            </div>
                            <div class="form-group{{ $errors->has('password_confirmation') ? ' has-danger' : '' }}">
                                <label class="form-control-label" for="password_confirmation">{{__('ConfirmPassword')}}</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control{{ $errors->has('password_confirmation') ? ' is-invalid' : '' }}" placeholder="" value="" required>
                            </div>

                            <div class="text-center">
                                <button type="button" onclick="submitCheck(this.form)" class="btn btn-success mt-4 col-6">{{__('Player Create')}}</button>
                            </div>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@push('js')
<script>
$('#btncheckId').click(function () {
    userid = $('#username').val();
    $.ajax({
            url: "{{argon_route('user.checkid')}}",
            type: "GET",
            data: {id:  userid, role_id:1},
            dataType: 'json',
            success: function (data) {
                if (data.error)
                {
                    alert("{!!__('agent.MultiUserMsg7')!!}");
                }
                else
                {
                    if (data.ok == 1)
                    {
                        alert("{!!__('agent.MultiUserMsg8')!!}");
                    }
                    else
                    {
                        alert("{!!__('agent.MultiUserMsg9')!!}");
                    }
                }
            },
            error: function () {
            }
        });
});
var doubleSubmitFlag = false;
function submitCheck(form){
    userid = $('#username').val();
    if(doubleSubmitFlag == true){
        alert(userid + 'Creating.');
        return false;
    }else{
        doubleSubmitFlag = true;
        form.submit();
    }
}
</script>
@endpush