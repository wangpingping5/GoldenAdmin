<html>
    <head>
    <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{__('agnet.Rolling')}}</title>
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
                $maxPercent = $user->maxPercent();
                $minPercent = $user->minPercent();
                $isSelf = $user->id==auth()->user()->id;
            ?>
            <div class="container-fluid">
                <div class="row my-4 justify-content-center">
                    <div class="col-md-12 col-lg-6 col-12">
                        <div class="card">
                            @include('layouts.headers.messages')
                            <h5 class="modal-title pt-4 ps-4">{{__('agnet.RollingSet')}}</h5>
                            <div class="row px-5 py-4">
                                <form action="{{argon_route('common.percent.update')}}" class="border" method="POST">
                                @csrf
                                    <input type="hidden" value="{{$user->id}}" name="user_id">
                                    <div class="col-12 mt-2">
                                        <h6 class="heading-small text-muted my-3">{{__('agnet.Rolling')}}</h6>
                                        <div class="row align-items-center border-bottom">
                                            <div class="col-md-4 col-lg-4 col-5 mb-2">
                                                <label class="form-control-label" id="deal_percent">{{__('slot')}}% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['deal_percent']}}-{{__("agent.Max")}}{{$maxPercent['deal_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-8 col-lg-8 col-7 mb-2">
                                                <input type="text" name="deal_percent" id="deal_percent" class="form-control" value="{{$user->deal_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-5 mb-2">
                                                <label class="form-control-label" id="table_deal_percent">{{__('live')}}% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['table_deal_percent']}}-{{__("agent.Max")}}{{$maxPercent['table_deal_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-8 col-lg-8 col-7 mb-2">
                                                <input type="text" name="table_deal_percent" id="table_deal_percent" class="form-control" value="{{$user->table_deal_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-5 mb-2">
                                                <label class="form-control-label" id="sports_deal_percent">{{__('sports')}}% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['sports_deal_percent']}}-{{__("agent.Max")}}{{$maxPercent['sports_deal_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-8 col-lg-8 col-7 mb-2">
                                                <input type="text" name="sports_deal_percent" id="sports_deal_percent" class="form-control" value="{{$user->sports_deal_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-5 mb-2">
                                                <label class="form-control-label" id="card_deal_percent">{{__('BoardGame')}}% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['card_deal_percent']}}-{{__("agent.Max")}}{{$maxPercent['card_deal_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-8 col-lg-8 col-7 mb-2">
                                                <input type="text" name="card_deal_percent" id="card_deal_percent" class="form-control" value="{{$user->card_deal_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            {{--
                                            <div class="col-md-4 col-lg-4 col-5 mb-2">
                                                <label class="form-control-label" id="pball_single_percent">파워볼 단폴% @if(!$isSelf)<span class="text-danger">(최소{{$minPercent['pball_single_percent']}}-최대{{$maxPercent['pball_single_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-8 col-lg-8 col-7 mb-2">
                                                <input type="text" name="pball_single_percent" id="pball_single_percent" class="form-control" value="{{$user->pball_single_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-5 mb-2">
                                                <label class="form-control-label" id="pball_comb_percent">파워볼 조합% @if(!$isSelf)<span class="text-danger">(최소{{$minPercent['pball_comb_percent']}}-최대{{$maxPercent['pball_comb_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-8 col-lg-8 col-7 mb-2">
                                                <input type="text" name="pball_comb_percent" id="pball_comb_percent" class="form-control" value="{{$user->pball_comb_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            --}}
                                        </div>    
                                        <h6 class="heading-small text-muted my-3">{{__('GGR')}}</h6>
                                        <div class="row align-items-center">
                                            <div class="col-md-4 col-lg-4 col-5 mb-2">
                                                <label class="form-control-label" id="ggr_percent">{{__('slot')}}{{__('GGR')}}% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['ggr_percent']}}-{{__("agent.Max")}}{{$maxPercent['ggr_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-8 col-lg-8 col-7 mb-2">
                                                <input type="text" name="ggr_percent" id="ggr_percent" class="form-control" value="{{$user->ggr_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-5 mb-2">
                                                <label class="form-control-label" id="table_ggr_percent">{{__('live')}}{{__('GGR')}}% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['table_ggr_percent']}}-{{__("agent.Max")}}{{$maxPercent['table_ggr_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-8 col-lg-8 col-7 mb-2">
                                                <input type="text" name="table_ggr_percent" id="table_ggr_percent" class="form-control" value="{{$user->table_ggr_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center row py-2 justify-content-center">
                                        <div class="col-md-2 col-0"></div>
                                        <button type="submit" class="btn btn-success mb-0 col-md-4 col-5 mx-2">{{__('agent.Change')}}</button>
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
