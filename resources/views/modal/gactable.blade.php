<html>
    <head>
    <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>에볼루션 테이블설정</title>
        @stack('css')
        <link type="text/css" href="{{ asset('argon') }}/css/argon-dashboard.css?v=1.0.0" rel="stylesheet">
    </head>
    <body class="g-sidenav-show content  ">
        <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
        <script src="{{ asset('argon') }}/js/delete.handler.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <main class="main-content position-relative ps">
            <div class="container-fluid">
                <div class="row my-4 justify-content-center">
                    <div class="col-md-12 col-lg-6 col-12">
                        <div class="card">
                            @include('layouts.headers.messages')
                            <h5 class="modal-title pt-4 ps-4">에볼루션 테이블 일괄적용</h5>
                            <div class="row">
                                <div class="col">                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-items-center text-center table-flush p-0 mb-0" id="datalist">
                                            <thead>
                                                <tr>
                                                    <th class="w-auto px-1 py-2">에볼루션</th>
                                                    <th class="w-auto px-2 py-2">일괄설정</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>바카라</td>
                                                    <td>
                                                        <a href="javascript:;" id="btn_01" onClick="update_kind('baccarat', 1);"><button class="btn btn-success px-2 py-1 mb-0 rounded-0" >운영</button></a>
                                                        <a href="javascript:;" id="btn_02" onClick="update_kind('baccarat', 0);"><button class="btn btn-danger px-2 py-1 mb-0 rounded-0" >정지</button></a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>블랙잭</td>
                                                    <td>
                                                        <a href="javascript:;" id="btn_03" onClick="update_kind('blackjack', 1);"><button class="btn btn-success px-2 py-1 mb-0 rounded-0" >운영</button></a>
                                                        <a href="javascript:;" id="btn_04" onClick="update_kind('blackjack', 0);"><button class="btn btn-danger px-2 py-1 mb-0 rounded-0" >정지</button></a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>룰렛</td>
                                                    <td>
                                                        <a href="javascript:;" id="btn_05" onClick="update_kind('roulette', 1);"><button class="btn btn-success px-2 py-1 mb-0 rounded-0" >운영</button></a>
                                                        <a href="javascript:;" id="btn_06" onClick="update_kind('roulette', 0);"><button class="btn btn-danger px-2 py-1 mb-0 rounded-0" >정지</button></a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>기타</td>
                                                    <td>
                                                        <a href="javascript:;" id="btn_07" onClick="update_kind('other', 1);"><button class="btn btn-success px-2 py-1 mb-0 rounded-0" >운영</button></a>
                                                        <a href="javascript:;" id="btn_08" onClick="update_kind('other', 0);"><button class="btn btn-danger px-2 py-1 mb-0 rounded-0" >정지</button></a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" class="justify-content-center">
                                                        <button type="button" class="btn btn-primary mb-0 col-md-4 col-5 mx-2" id="frmclose">{{__('Close')}}</button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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
            function update_kind(type, view) {
                for(i = 1; i < 9; i++)
                {
                    $("#btn_0" + i).attr('disabled', 'disabled');
                }
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: 'POST',
                    url: "{{argon_route('game.gackind.update')}}",
                    data: { type: type, view:view },
                    cache: false,
                    async: false,
                    success: function (data) {
                        if (data.error) {
                            alert(data.msg);
                            for(i = 1; i < 9; i++)
                            {
                                $("#btn_0" + i).attr('disabled', 'auto');
                            }
                            return;
                        }
                        alert('테이블정보가 업데이트 되었습니다.');
                    },
                    error: function (err, xhr) {
                        alert(err.responseText);
                        for(i = 1; i < 9; i++)
                        {
                            $("#btn_0" + i).attr('disabled', 'auto');
                        }
                    }
                });
            }
        </script>
    </body>
</html>
