<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>

        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests"> -->

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __($elementName)}}</title>
        <!-- Favicon -->
        <link href="{{ asset('argon') }}/img/brand/favicon.png" rel="icon" type="image/png">
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300&display=swap" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

        <!-- Extra details for Live View on GitHub Pages -->

        <!-- Icons -->
        <link href="{{ asset('argon') }}/vendor/nucleo/css/nucleo.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <!-- Argon CSS -->
        <!-- <link type="text/css" href="{{ asset('argon') }}/css/argon.css?v=1.0.0" rel="stylesheet"> -->
        @stack('css')
        <link type="text/css" href="{{ asset('argon') }}/css/argon-dashboard.css?v=1.2.2" rel="stylesheet">
        <link type="text/css" href="{{ asset('argon') }}/css/star.css?v=1.0.0" rel="stylesheet">

    </head>
    @auth()
    <body class="g-sidenav-show bg-warning-opacity {{ $class ?? '' }} ">
    @endauth
        
    @guest()
    <body class="g-sidenav-show {{ $class ?? '' }} " style="background:url('{{ asset('argon') }}/img/default/argon_bg.jpg') center/cover no-repeat fixed #111;">
    @endguest
        @auth()
            <?php
                $user = auth()->user();
                $user_id = [];
                while ($user)
                {
                    if ($user->isInoutPartner())
                    {
                        $user_id[] = $user->id;
                    }
                    $user = $user->referral;
                }
                $superadminId = \App\Models\User::where('role_id',9)->first()->id;
                $notices = \App\Models\Notice::where(['active' => 1])->whereIn('type', ['all','partner'])->whereIn('user_id',$user_id)->get(); //for admin's popup
                $msgtype = 0;
                if (auth()->user()->hasRole('admin'))
                {
                    $unreadmsgs = [];
                }
                else
                {
                    $unreadmsgs = \App\Models\Message::where('user_id', auth()->user()->id)->whereNull('read_at')->get(); //unread message
                    if (count($unreadmsgs) > 0)
                    {
                        $msgtype = $unreadmsgs->first()->type;
                    }
                }
            ?>
            
            <form id="logout-form" action="#" method="POST" style="display: none;">
                @csrf
            </form>
            <!-- <div class="min-height-300 bg-danger position-absolute w-100"></div> -->
            @include('layouts.navbars.sidebar')
        @endauth

        <main class="main-content position-relative ps">            
            @auth()
                @include('layouts.navbars.navbar')
                @include('layouts.headers.messages')
                {{--
                @if (count($notices)>0)
                    @foreach ($notices as $notice)
                    <div class="noticeBar" style="left:{{$loop->index * 100}}px;top:{{40+$loop->index * 50}}px; display:none;" id="notification{{$notice->id}}">
                        <div>
                            <button type="button" class="btn btn-info mx-2 my-2" id="noticeDaily" onclick="closeNotification('{{$notice->id}}', false);">{{__('Hidefortoday')}}</button>
                            <button type="button" class="btn btn-warning mx-2 my-2" id="noticeOnce" onclick="closeNotification('{{$notice->id}}', true);">{{__('Close')}}</button>
                        </div>
                        <hr class="my-1">
                        <div class="content">
                            <?php echo $notice->content  ?></textarea>
                        </div>
                    </div>
                    @endforeach
                @endif
                --}}
                <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="msgalarm" aria-hidden="true" id="msgalarm">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{__('Notification')}}</h5>
                            <button type="button btn-dark" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <a href="{{argon_route('msg.list', ['type'=>$msgtype])}}" class="dropdown-item text-center text-primary font-weight-bold py-3"><h6 class="text-sm text-muted m-0"><strong class="text-primary">{{count($unreadmsgs)}}</strong> {{__('ReceivedNewMessage')}}</h6></a>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">{{__('Close')}}</button>
                        </div>
                        </div>
                    </div>
                </div>
            @endauth
            @yield('content')
        </main>
        {{--
        @auth()
            @include('layouts.navbars.set')
        @endauth
        --}}
        @include('layouts.footers.guest')

        <!-- <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script> -->

        
        <!--   Core JS Files   -->
        <script src="{{ asset('argon') }}/js/core/popper.min.js"></script>
        <script src="{{ asset('argon') }}/js/core/bootstrap.min.js"></script>
        <script src="{{ asset('argon') }}/js/plugins/perfect-scrollbar.min.js"></script>
        <script src="{{ asset('argon') }}/js/plugins/chartjs.min.js"></script>
        <script src="{{ asset('argon') }}/js/plugins/smooth-scrollbar.min.js"></script>
        <script src="{{ asset('argon') }}/js/plugins/choices.min.js"></script>
        <script src="{{ asset('argon') }}/js/plugins/quill.min.js"></script>
        <!-- Kanban scripts -->
        <!-- <script src="{{ asset('argon') }}/js/plugins/dragula/dragula.min.js"></script>
        <script src="{{ asset('argon') }}/js/plugins/jkanban/jkanban.js"></script>
        <script src="{{ asset('argon') }}/js/plugins/countup.min.js"></script>
        <script src="{{ asset('argon') }}/js/plugins/round-slider.min.js"></script> -->
        
        <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script> -->
        <script src="{{ asset('argon') }}/vendor/js-cookie/js.cookie.js"></script>
        <script src="{{ asset('argon') }}/vendor/js-cookie/js.cookie.js"></script>
        <script src="{{ asset('argon') }}/vendor/jquery.scrollbar/jquery.scrollbar.min.js"></script>
        <script src="{{ asset('argon') }}/vendor/jquery-scroll-lock/dist/jquery-scrollLock.min.js"></script>
        <script src="{{ asset('argon') }}/vendor/lavalamp/js/jquery.lavalamp.min.js"></script>

        <!-- Optional JS -->
        <script src="{{ asset('argon') }}/vendor/clipboard/dist/clipboard.min.js"></script>
        <script src="{{ asset('argon') }}/vendor/js-cookie/js.cookie.js"></script>
        <script src="{{ asset('argon') }}/vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
        <script src="{{ asset('argon') }}/js/delete.handler.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        @stack('js')

        <!-- Argon JS -->
        <script>
            var win = navigator.platform.indexOf('Win') > -1;
            if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = {
                damping: '0.5'
            }
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
            }
        </script>
        <script src="{{ asset('argon') }}/js/argon-dashboard.js?v=1.2.1"></script>
        <script>
        @if (Auth::check() )
            $( document ).ready(function() {
                @if (count($notices)>0)
                    @foreach ($notices as $notice)
                        var prevTime = localStorage.getItem("hidenotification" + {{$notice->id}});
                        if (prevTime && Date.now() - prevTime < 24 * 3600 * 1000) {
                            $("#notification{{$notice->id}}").hide();
                        }
                        else{
                            $("#notification{{$notice->id}}").show();
                        }
                    @endforeach
                @endif
                @if (count($unreadmsgs) > 0)
                    $('#msgalarm').modal("show");
                @endif
                @if (!auth()->user()->hasRole('admin') && auth()->user()->isInoutPartner())
                    var updateTime = 3000;
                    var apiUrl="{{argon_route('common.inoutlist')}}";
                    var timeout;
                    var lastRequest = 0;
                    var audio_in = new Audio("{{ url('/back/audio/deposit.mp3')}}");
                    var audio_out = new Audio("{{ url('/back/audio/withdraw.mp3')}}");
                    var user_join = new Audio("{{ url('/back/audio/newjoin.mp3')}}");
                    var new_msg = new Audio("{{ url('/back/audio/newmessage.mp3')}}");
                    var updateInOutRequest = function (callback) {
                        if (true) {
                            var timestamp = + new Date();
                            $.ajax({
                                url: apiUrl,
                                type: "GET",
                                data: {'ts':timestamp,
                                    'last':lastRequest, 
                                    'id': 
                                    @if (Auth::check())
                                        {{auth()->user()->id}} },
                                    @else
                                    0},
                                    @endif
                                dataType: 'json',
                                success: function (data) {
                                    var inouts=data;
                                    lastRequest = inouts['now'];
                                    if (inouts['add'] > 0)
                                    {
                                        if (inouts['rating'] > 0)
                                        {
                                            audio_in.play();
                                        }
                                        $("#in_newmark").text('('+inouts['add']+')');
                                        $("#in_newmark").show();
                                    }
                                    if (inouts['out'] > 0)
                                    {
                                        if (inouts['rating'] > 0)
                                        {
                                            audio_out.play();
                                        }
                                        $("#out_newmark").text('('+inouts['out']+')');
                                        $("#out_newmark").show();
                                    }
                                    if (inouts['join'] > 0)
                                    {
                                        if (inouts['rating'] > 0)
                                        {
                                            user_join.play();
                                        }
                                        $("#user_newmark").show();
                                        $("#join_newmark").text('('+inouts['join']+')');
                                        $("#join_newmark").show();
                                    }
                                    else
                                    {
                                        // $("#user_newmark").hide();
                                        // $("#join_newmark").hide();
                                    }


                                    if (inouts['add'] == 0 && inouts['out'] == 0)
                                    {
                                        // $("#in_newmark").hide();
                                        // $("#out_newmark").hide();
                                    }

                                    if (inouts['msg'] > 0)
                                    {
                                        if (inouts['rating'] > 0)
                                        {
                                            new_msg.play();
                                        }
                                        $("#unreadmsgcount").text('('+inouts['msg']+')');
                                        $("#unreadmsgcount").show();
                                    }
                                    timeout = setTimeout(updateInOutRequest, updateTime);
                                    if (callback != null) callback();
                                },
                                error: function () {
                                    timeout = setTimeout(updateInOutRequest, updateTime);
                                    if (callback != null) callback();
                                }
                            });
                        }else {
                            clearTimeout(timeout);
                        }
                    };
                    timeout = setTimeout(updateInOutRequest, updateTime);
                @endif
            });
            function closeNotification(notice,onlyOnce) {
                if (onlyOnce) {

                }
                else {
                    localStorage.setItem("hidenotification" + notice, Date.now());
                }

                $("#notification" + notice).hide();
            }
            $('#ratingOn').click(function (event) {
                var rating = 0;
                if($('#rateText').text() == 'X'){
                    rating = 1;
                }
                $.ajax({
                        url: "{argon_route('common.inoutlist')}}",
                        type: "GET",
                        data: {'rating': rating },
                        dataType: 'json',
                        success: function (data) {
                            if (rating == 0)
                            {
                                $('#rateText').text('X');
                            }
                            else
                            {
                                $('#rateText').text('');
                            }
                        }
                    });
            });
        @endif
        </script>
    </body>
</html>