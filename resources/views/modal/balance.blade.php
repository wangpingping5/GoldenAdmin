<html>
    <head>
    <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{__('agent.DW')}}</title>
        @stack('css')
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
            <div class="container-fluid">
                <div class="row my-4 justify-content-center">
                    <div class="col-md-12 col-lg-6 col-12">
                        <div class="card">
                            @include('layouts.headers.messages')
                            <h5 class="modal-title pt-4 ps-4">{{__('agent.DW')}}</h5>
                            <div class="row px-5 py-4">
                                @if ($user->hasRole('user'))
                                <label class="form-control-label text-danger"> {{__('agent.BeforeDWTerminalGameMsg')}} </label>
                                @endif
                                <form action="{{argon_route('common.balance.store')}}" class="border" method="POST" id="frmbalance">
                                @csrf
                                    <input type="hidden" value="{{$user->id}}" name="user_id">
                                    <input type="hidden" value="add" name="type" id="type">
                                    <div class="align-items-center row border-bottom py-2">
                                        <div class="col-3  text-center">
                                            <label class="form-control-label mb-0">{{__('agent.Name')}}</label>
                                        </div>
                                        <div class="col-9">
                                            <span class="btn btn-success btn-sm {{$badge_class[$user->role_id]}} mb-0">{{__($user->role->name)}}[{{$user->username}}]</span>
                                        </div>
                                    </div>
                                    <div class="align-items-center row  border-bottom py-2">
                                        <div class="col-3 text-center">
                                            <label class="form-control-label mb-0">{{__('agent.Balance')}}</label>
                                        </div>
                                        <div class="col-9">
                                        <span  id="adduserbalance">
                                        {{$user->hasRole('manager') ? number_format($user->shop->balance) : number_format($user->balance)}}
                                        </span>
                                        @if ($user->hasRole('user'))
                                        &nbsp;<a class="btn btn-success btn-sm mb-0" href="{{argon_route('player.terminate', ['id' => $user->id])}}" data-method="DELETE"
                                                data-confirm-title="{{__('agent.Confirm')}}"
                                                data-confirm-text="{{__('agent.TerminalGameMsg')}}"
                                                data-confirm-delete="{{__('agent.Confirm')}}"
                                                data-confirm-cancel="{{__('agent.Cancel')}}"
                                                onClick="$(this).css('pointer-events', 'none');"
                                                >{{__('agent.TerminalGame')}}</a>
                                        @endif
                                        </div>
                                    </div>
                                    <div class="align-items-center row border-bottom py-2">
                                        <div class="col-3 text-center">
                                            <label class="form-control-label mb-0">{{__('agent.DW')}}</label>
                                        </div>
                                        <div class="col-9">
                                            <div class="row">
                                                <div class="form-check col-lg-2 col-md-2 col-4 ms-3">
                                                    <input class="form-check-input" type="radio" name="add" value="add" id="add" checked>
                                                    <label class="form-check-label" for="add">{{__('agent.Deposit')}}</label>
                                                </div>
                                                <div class="form-check col-lg-2 col-md-2 col-4">
                                                    <input class="form-check-input" type="radio" name="out" value="out" id="out">
                                                    <label class="form-check-label" for="out">{{__('agent.Withdraw')}}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="align-items-center row  border-bottom py-2">
                                        <div class="col-3 text-center">
                                            <label class="form-control-label mb-0">{{__('agent.Amount')}}</label>
                                        </div>
                                        <div class="col-9">
                                            <input class="form-control" type="text" value="" id="addamount" name="amount">
                                        </div>
                                    </div>
                                    <div class="align-items-center row  border-bottom py-2">
                                        <div class="col-3 align-items-center text-center">
                                            <label class="form-control-label mb-0">{{__('agent.Memo')}}</label>
                                        </div>
                                        <div class="col-9">
                                            <input class="form-control" type="text" value="" id="reason" name="reason">
                                        </div>
                                    </div>
                                    <div class="text-center row py-2 justify-content-center">
                                        <div class="col-md-2 col-0"></div>
                                        <button type="button" id="btnSubmit" class="btn btn-primary mb-0 col-md-4 col-5 mx-2">{{__('agent.DW')}}</button>
                                        <button type="button" class="btn btn-danger mb-0 col-md-4 col-5 mx-2" id="frmclose">{{__('Close')}}</button>
                                        <div class="col-md-2 col-0"></div>
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
        window.opener.location.href = window.opener.location;
        window.close();        
    });
    $('#btnSubmit').click(function () {
        $(this).attr('disabled', 'disabled');
        $('form#frmbalance').submit();
    });
    $('#add').change(function(){
        $('#type').val("add");
        $('#out').prop("checked", false)
    });
    $('#out').change(function(){
        $('#type').val("out");
        $('#add').prop("checked", false)
    });
</script>
    </body>
</html>
