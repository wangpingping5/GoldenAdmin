<html>
    <head>
    <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        @if(Request::get('role_id'))
        <title>{{\App\Models\Role::where('level',$role_id)->first()->description}} {{__('agent.New')}}</title>
        @else
        <title>{{__('Partners')}} {{__('agent.New')}}</title>
        @endif
        @stack('css')
        <link href="{{ asset('argon') }}/vendor/nucleo/css/nucleo.css" rel="stylesheet">
        <link href="{{ asset('argon') }}/vendor/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
        <link type="text/css" href="{{ asset('argon') }}/css/argon-dashboard.css?v=1.0.0" rel="stylesheet">
    </head>
    <body class="g-sidenav-show content  ">
        <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
        <script src="{{ asset('argon') }}/js/delete.handler.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <main class="main-content position-relative ps">
            <?php 
                $badge_class = \App\Models\User::badgeclass();
            ?>
            <div class="container-fluid py-3">
                <div class="row">
                    <div class="col col-lg-6 m-auto">
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        @if(Request::get('role_id'))    
                                        <h3 class="mb-0">{{__(\App\Models\Role::where('level',$role_id)->first()->name)}} {{__('agent.New')}}</h3>
                                        @else
                                        <h3 class="mb-0">{{__('Partners')}} {{__('agent.New')}}</h3>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="post" autocomplete="off" id="frmcreate">
                                    @csrf
                                    <div class="pl-lg-4">
                                        <div class="form-group{{ $errors->has('username') ? ' has-danger' : '' }}">
                                            <label class="form-control-label" for="username">{{__('agent.ID')}}</label>
                                            <div class="d-flex">
                                                @if(auth()->user()->role_id < 8)
                                                <div class="col-md-8 col-6 float-left">
                                                    <input type="text" name="username" id="username" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" value="" required>
                                                </div>
                                                <div class="col-md-4 col-6 px-2">
                                                    <button type="button" class="btn btn-danger my-0" id="btncheckId">{{__('agent.DuplicateConfirm')}}</button>
                                                </div>
                                                @else
                                                <div class="col-md-12 col-12 float-left">
                                                    <input type="text" name="username" id="username" class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" value="" required>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        @if(Request::get('role_id'))                                       
                                        <input type="hidden" name="role_id" id="role_id" value="{{$role_id}}">
                                        @else 
                                        <div class="form-group">
                                            <label class="form-control-label" for="role_id">{{__('agent.Level')}}</label>
                                            <div class="input-group input-group-alternative">
                                                <select class="form-control" id="role_id" name="role_id">
                                                    @for ($level=3;$level<auth()->user()->role_id;$level++)
                                                    <option value="{{$level}}" @if (Request::get('role_id') == $level) selected @endif> {{__(\App\Models\Role::find($level)->name)}}</option>
                                                    @endfor
                                                </select>
                                                <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="form-group{{ $errors->has('parent') ? ' has-danger' : '' }}">
                                            @if(Request::get('role_id'))
                                            <label class="form-control-label" for="parent">{{ __(\App\Models\Role::where('level',$role_id+1)->first()->name)}} {{__('agent.ID')}}</label>
                                            <input type="text" name="parent" id="parent" class="form-control{{ $errors->has('parent') ? ' is-invalid' : '' }}" value="{{Request::get('parent')??''}}" {{Request::get('parent')?'readonly':''}}>
                                            @else
                                            <label class="form-control-label" for="parent">{{__('agent.Parent')}}</label>
                                            <input type="text" name="parent" id="parent" class="form-control{{ $errors->has('parent') ? ' is-invalid' : '' }}" value="{{Request::get('parent')??''}}" {{Request::get('parent')?'readonly':''}}>
                                            @endif
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
                                                    <tr>
                                                        <td>{{__('slot')}}{{__('GGR')}}</td>
                                                        <td><input type="text" name="ggr_percent" id="ggr_percent" class="form-control" value="0"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>{{__('live')}}{{__('GGR')}}</td>
                                                        <td><input type="text" name="table_ggr_percent" id="table_ggr_percent" class="form-control" value="0"></td>
                                                    </tr>
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
                                        <div class="text-center row py-2 justify-content-center">
                                            <div class="col-md-2 col-0"></div>
                                            <button type="button" id="btnSubmit"  onclick="submitCheck(this.form)" class="btn btn-primary mb-0 col-md-4 col-5 mx-2">{{__('New')}}</button>
                                            <button type="button" class="btn btn-danger mb-0 col-md-4 col-5 mx-2" id="frmclose">{{__('Close')}}</button>
                                            <div class="col-md-2 col-0"></div>
                                        </div>
                                    </div>
                                </form>                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
<script type="text/javascript">
    $('#frmclose').click(function(){
        window.close();        
    });
    $('#btncheckId').click(function () {
        userid = $('#username').val();
        role_id = $('#role_id').val();
        $.ajax({
                url: "{{argon_route('user.checkid')}}",
                type: "GET",
                data: {id:  userid, role_id:role_id},
                dataType: 'json',
                success: function (data) {
                    if (data.error)
                    {
                        alert({!!__('agent.MultiUserMsg7')!!});
                    }
                    else
                    {
                        if (data.ok == 1)
                        {
                            alert({!!__('agent.MultiUserMsg8')!!});
                        }
                        else
                        {
                            alert({!!__('agent.MultiUserMsg9')!!});
                        }
                    }
                },
                error: function () {
                }
            });
    });
    function submitCheck(){
        $('#btnSubmit').css('pointer-events', 'none');
        var frmData = $('#frmcreate').serialize();
        $.ajax({
                url: "{{argon_route('patner.store')}}",
                type: "POST",
                data: frmData,
                dataType: 'json',
                success: function (data) {
                    if (data.error)
                    {
                        $('#btnSubmit').css('pointer-events', 'auto');
                        alert(data.msg);
                    }
                    else
                    {
                        confirm(data.msg);
                        window.opener.location.href = "{{argon_route('common.profile')}}" + "?id=" + data.id;
                        window.close();
                    }
                },
                error: function (data) {
                    alert({!!__('agent.NoCorrectData')!!});
                    $('#btnSubmit').css('pointer-events', 'auto');
                }
            });
    }
</script>
    </body>
</html>
