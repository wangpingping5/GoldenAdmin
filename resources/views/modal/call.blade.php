<html>
    <head>
    <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{__('agent.NewCall')}}</title>
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
                            <h5 class="modal-title pt-4 ps-4">{{__('agent.NewCall')}}</h5>
                            <div class="row px-5 py-4">
                                <label class="form-control-label text-danger"> {{__('agent.NewCallMsg')}} </label>
                                <form action="{{argon_route('game.call.store')}}" class="border" method="POST" id="frmcall">
                                @csrf
                                    <div class="align-items-center row  border-bottom py-2">
                                        <div class="col-3 text-center">
                                            <label class="form-control-label mb-0">{{__('agent.ID')}}</label>
                                        </div>
                                        <div class="col-9">
                                            <input class="form-control" type="text" value="" id="username" name="username">
                                        </div>
                                    </div>
                                    <div class="align-items-center row  border-bottom py-2">
                                        <div class="col-3 text-center">
                                            <label class="form-control-label mb-0">{{__('agent.Amount')}}</label>
                                        </div>
                                        <div class="col-9">
                                            <input class="form-control" type="text" value="" id="total_bank" name="total_bank">
                                        </div>
                                    </div>
                                    <div class="text-center row py-2 justify-content-center">
                                        <div class="col-md-2 col-0"></div>
                                        <button type="button" id="btnSubmit"  onclick="submitCheck()" class="btn btn-primary mb-0 col-md-4 col-5 mx-2">{{__('agent.New')}}</button>
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
        window.close();        
    });
    function submitCheck(){
        $('#btnSubmit').css('pointer-events', 'none');
        var frmData = $('#frmcall').serialize();
        $.ajax({
                url: "{{argon_route('game.call.store')}}",
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
                        window.opener.location.href = "{{argon_route('game.call.list')}}";
                        window.close();
                    }
                },
                error: function (data) {
                    alert('There is no correct');
                    $('#btnSubmit').css('pointer-events', 'auto');
                }
            });
    }
</script>
    </body>
</html>
