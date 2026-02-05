<html>
    <head>
    <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{__('agent.MultiUserAdd')}}</title>
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
                                        <h3 class="mb-0">{{__('agent.MultiUserAdd')}}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST"  id="frmmulticreate" enctype="multipart/form-data">
                                    @csrf
                                    <table class="table table-bordered align-items-center table-flush p-0 mb-0">
                                        <tbody>
                                            <tr>
                                                <td><label>{{__('agent.UploadFile')}} </label></td>
                                                <td><input type="file" name="uploadFile" id="uploadFile" class="form-control" value="" ></td>
                                            </tr>
                                            <tr>
                                                <td><label>{{__('agent.SampleFileDown')}}</label></td>
                                                <td><label><a class="text-primary" href="/back/user_upload.xls"> [{{__('agent.Download')}}] </a></label></td>
                                            </tr>
                                            <tr>
                                                <td rowspan="3"><label>{{__('agent.MultiUserMsg1')}}</label></td>
                                                <td><label class="form-control-label text-danger">※ {{__('agent.MultiUserMsg2')}}</label></td>
                                            </tr>
                                            <tr>
                                                <td><label class="form-control-label text-danger">※ {{__('agent.MultiUserMsg3')}}</label></td>
                                            </tr>
                                            <tr>
                                                <td><label class="form-control-label text-danger">※ {{__('agent.Level')}} : 1</label></td>
                                            </tr>
                                        </tbody>    
                                    </table>
                                    <div class="text-center row py-2 justify-content-center">
                                        <button type="button" id="btnSubmit"  onclick="submitCheck(this.form)" class="btn btn-primary mb-0 col-md-4 col-5 mx-2">{{__('agent.Add')}}</button>
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
    function submitCheck(form){
        $('#btnSubmit').css('pointer-events', 'none');
        var formData = new FormData();
		var inputFile = $("input[name='uploadFile']");
		var files = inputFile[0].files;
		console.log(files);
		
		for(var i =0;i<files.length;i++){
			formData.append("uploadFile", files[i]);
		}
        $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{argon_route('player.multistore')}}",
                processData : false,
			    contentType : false,
                type: "POST",
                data: formData,
                success: function (data) {
                    if (data.error)
                    {
                        $('#btnSubmit').css('pointer-events', 'auto');
                        alert(data.msg);
                    }
                    else
                    {
                        var err_users = data.err_users;
                        if(err_users.length > 0){
                            if(err_users.length == 1){
                                confirm("["+ err_users[0] + "] " + "{!!__('agent.MultiUserMsg4')!!}");
                            }else{
                                confirm("["+ err_users.join() + "] " + "{!!__('agent.MultiUserMsg5')!!}");
                            }                            
                        }else{
                            confirm("{!!__('agent.MultiUserMsg6')!!}");
                        }
                        
                        window.opener.location.href = "{{argon_route('player.list')}}";
                        window.close();
                    }
                },
                error: function (data) {
                    alert("{!!__('agent.NoCorrectData')!!}");
                    $('#btnSubmit').css('pointer-events', 'auto');
                }
            });
    }
</script>
    </body>
</html>
