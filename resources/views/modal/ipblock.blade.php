<html>
    <head>
    <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{$edit?'IP '.__("agent.Change") : 'IP '. __("agent.Add")}}</title>
        @stack('css')
        <link type="text/css" href="{{ asset('argon') }}/css/argon-dashboard.css?v=1.0.0" rel="stylesheet">
    </head>
    <body class="g-sidenav-show content  ">
        <div class="container-fluid">
            <div class="row">
                <div class="col col-lg-6 m-auto">
                    <div class="card">
                        <div class="card-header border-0" id="headingOne">
                            <div class="row align-items-center box">
                                <div class="col-8">
                                    <h5 class="mb-0 title">{{$edit?'IP '.__("agent.Change") : 'IP '. __("agent.Add")}}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{$edit?argon_route('ipblock.update', $ipblock->id) : argon_route('ipblock.store')}}" class='ipconfirm' id="user-form" method="POST" >
                            @csrf
                            
                            <div class="pl-lg-4">
                                <div class="form-group {{ $errors->has('ipaddress') ? ' has-danger' : '' }}">
                                    @if ($errors->count()>0)
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <span class="alert-text text-white">@foreach($errors->all() as $error)
                                                {{ $error }}
                                            @endforeach</span>
                                        </div>
                                    @endif                                    
                                    @if(session()->has('success'))
                                        <div class="col-md-4">                                            
                                            <div class="modal" id="modal-notification" tabindex="-1" role="dialog" aria-labelledby="modal-notification" aria-hidden="true" style="display: block;">
                                                <div class="modal-dialog modal-danger max-width-300" role="document">
                                                    <div class="modal-content" style="height:200px;">
                                                        <div class="modal-header bg-secondary" style="height:50px;">
                                                            <h6 class="modal-title" id="modal-title-notification">{{__('Notification')}}</h6>
                                                        </div>
                                                        <div class="modal-body" style="padding:0;height:100px;">
                                                            <div class="py-3 text-center">
                                                                <i class="ni ni-bell-55 ni-3x"></i>
                                                                <h6 class="text-primary mt-4" id="alertname">{{Session::get('success')[0]}}</h6>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <a class="badge badge-pill badge-md bg-primary text-white confirm" id="confirm" href="#">{{__("agent.Confirm")}}</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-light opacity-5 w-sm-100 h-100 ms-0 position-fixed start-0 top-0"></div>
                                        </div>
                                    @endif
                                    <label class="form-control-label" for="ip_address"><span class="text-danger"> {{__("agent.AccessIPMsg")}}</span></label>
                                    <textarea id="ip_address" name="ip_address" class="form-control" rows="5">{{$ipblock? $ipblock->ip_address:''}}</textarea>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary mt-4 col-6 submitbtn" id="submitbtn">{{$edit?'IP '.__("agent.Change") : 'IP '. __("agent.Add")}}</button>
                                </div>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script type="text/javascript">
    $('#confirm').click(function(){
        window.opener.location.href = window.opener.location;
        window.close();        
    });
</script>
