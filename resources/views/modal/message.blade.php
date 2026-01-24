<html>
    <head>
    <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{__("agent.SendMessage")}}</title>
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
                    <div class="col-md-12 col-lg-8 col-12">
                        <div class="card">
                            @include('layouts.headers.messages')
                            <h5 class="modal-title pt-4 ps-4">{{__("agent.SendMessage")}}</h5>
                            <div class="row px-5 py-4">
                                <form action="{{argon_route('common.message.send')}}" class="border" method="POST">
                                @csrf
                                    <input type="hidden" value="{{$user->id}}" name="user_id">
                                    <input type="hidden" value="0" name="ref_id">
                                    <input type="hidden" value="0" name="type">
                                    <div class="col">
                                        <div class="row">
                                            <div class="col-md-8  col-12">
                                                <div class="row">
                                                    <div class="align-items-center row py-2">
                                                        <div class="col-3  text-center">
                                                            <label class="form-control-label mb-0">{{__("agent.Maker")}}</label>
                                                        </div>
                                                        <div class="col-9">
                                                            <input class="form-control" type="text" value="{{auth()->user()->username}}" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="align-items-center row py-2">
                                                        <div class="col-3  text-center">
                                                            <label class="form-control-label mb-0">{{__("agent.Receiver")}}</label>
                                                        </div>
                                                        <div class="col-9">
                                                            <input class="form-control" type="text" value="{{$user->username}}" disabled>
                                                        </div>
                                                    </div>
                                                    <div class="align-items-center row py-2">
                                                        <div class="col-3  text-center">
                                                            <label class="form-control-label mb-0">{{__("agent.Title")}}</label>
                                                        </div>
                                                        <div class="col-9">
                                                            <input class="form-control" type="text" value="" id="title" name="title">
                                                        </div>
                                                    </div>
                                                    <div class="align-items-center row py-2">
                                                        <div class="col-3  text-center">
                                                            <label class="form-control-label mb-0">{{__("agent.Content")}}</label>
                                                        </div>
                                                        <div class="col-9">
                                                            <textarea id="content" name="content" class="form-control" rows="6"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-12 py-2">
                                                <select class="form-control col-12" size="16" id="selectbox" name="selectbox" onchange="chageLangSelect()">
                                                    <option value="" selected>--{{__("agent.Select")}}--</option>
                                                    @foreach ($msgtemps as $msgtemp)
                                                        <option value="{{$msgtemp->content}}">{{$msgtemp->title}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col border-top">
                                        <div class="text-center row py-2 justify-content-center">
                                            <div class="col-md-2 col-0"></div>
                                            <button type="submit" class="btn btn-primary mb-0 col-md-4 col-5 mx-2">{{__("agent.Send")}}</button>
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
        window.opener.location.href = window.opener.location;
        window.close();        
    });
    function chageLangSelect(){
        var msgtempSelect = document.getElementById("selectbox");
        var selectValue = msgtempSelect.options[msgtempSelect.selectedIndex].value;
        $('#content').val(selectValue);
    }
</script>
    </body>
</html>
