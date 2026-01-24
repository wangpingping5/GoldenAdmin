<html>
    <head>
    <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{__('agnet.PartnerMove')}}</title>
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
            <div class="container-fluid py-3">
                <div class="row">
                    <div class="col col-lg-6 m-auto">
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h3 class="mb-0">{{__('agnet.PartnerMove')}}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST"  id="frmmove">
                                @csrf
                                    <input type="hidden" value="{{$user->id}}" name="user_id">
                                    <div class="form-group row">
                                        <div class="col-5 text-center">
                                            {{__('agnet.Name')}}
                                        </div>
                                        <div class="col-7">
                                        <span class="btn btn-primary m-0 rounded-0" style="background-color: #20c997;" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$user->parents(auth()->user()->role_id, $user->role_id+1)!!}"> {{$user->username}}</span>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-5 text-center">
                                            {{__('agnet.Level')}}
                                        </div>
                                        <div class="col-7">
                                            {{__($user->role->name)}}
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="col-5 text-center">
                                            {{__('agnet.Balance')}}
                                        </div>
                                        <div class="col-7">
                                            @if ($user->hasRole('manager'))
                                            {{number_format($user->shop->balance)}}
                                            @else
                                            {{number_format($user->balance)}}
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="form-group row">
                                        <div class="col-5 text-center">
                                            {{__('agnet.Parent')}}
                                        </div>
                                        <div class="col-7">
                                            <input class="form-control col-8" type="text" value="" id="parent" name="parent">
                                        </div>
                                    </div>
                                    <div class="text-center row py-2 justify-content-center">
                                        <button type="button" id="btnSubmit"  onclick="submitCheck(this.form)" class="btn btn-primary mb-0 col-md-4 col-5 mx-2">{{__('agnet.Move')}}</button>
                                        <button type="button" class="btn btn-danger mb-0 col-md-4 col-5 mx-2" id="frmclose">{{__('Close')}}</button>
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
        var frmData = $('#frmmove').serialize();
        $.ajax({
                url: "{{argon_route('patner.movestore')}}",
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
                        window.opener.location.href = "{{argon_route('patner.list')}}";
                        window.close();
                    }
                },
                error: function (data) {
                    alert({!! __('agent.NoCorrectData')!!});
                    $('#btnSubmit').css('pointer-events', 'auto');
                }
            });
    }
</script>
    </body>
</html>
