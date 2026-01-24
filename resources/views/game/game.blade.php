@extends('layouts.app',[
        'parentSection' => 'gamepatner',
        'elementName' => 'Game Partner'
    ])
@section('content')
<div class="container-fluid py-3">
    <?php 
        $statuses = \App\Support\Enum\UserStatus::lists(); 
        $status_class = \App\Support\Enum\UserStatus::bgclass(); 
        $badge_class = \App\Models\User::badgeclass();
    ?>
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row shadow bg-light p-1 rounded-1">
                    <form id="searchfrm" action="" method="GET" >
                        <input type="hidden" value="{{$user->id}}" id="user_id" name="user_id">
                        <input type="hidden" value="{{$category->id}}" id="cat_id" name="cat_id">
                        <div class="row">                        
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <select class="form-control" id="kind" name="kind">
                                    <option value="" @if (Request::get('kind') == '') selected @endif>{{__('agent.GameName')}}</option>
                                </select>
                            </div>          
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="text" value="{{Request::get('search')}}" id="search" name="search">
                            </div>       
                            <div class="col-lg-3 col-md-6 col-12 mt-2 px-1">
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-6 pe-1">
                                        <button type="submit" class="form-control btn btn-primary">{{__('agent.Search')}}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="row mt-4">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">{{__('Game Partner')}}</p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">                    
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-center table-flush  mb-0" id="datalist">
                                <thead>
                                    <tr>
                                        <th scope="col">{{__('agent.PartnerProvider')}}</th>
                                        <th scope="col">{{__('agent.GameName')}}</th>
                                        <th scope="col">{{__('agent.ActiveManager')}}</th>
                                        <th scope="col">{{__('agent.InactiveManager')}}</th>
                                        <th class="text-right">{{__('agent.AllApproved')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($games) > 0)
                                        <tr>
                                            <td rowspan="{{$games->count()}}"> 
                                                <a href="#" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!! $user->parents(auth()->user()->role_id) !!}">
                                                    {{ $user->username }} <span class="btn {{$badge_class[$user->role_id]}} px-2 py-1 mb-1 mx-1 rounded-0}}">{{__($user->role->name)}}</span>
                                                    <p>
                                                    {{$category->title}}
                                                </a>
                                            </td>
                                            @include('game.partials.row_game')
                                        </tr>
                                    @else
                                        <tr>
                                            <td colspan="5">{{__('No Data')}}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $games->withQueryString()->links('vendor.pagination.argon') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
@push('js')
<script>
    function toggleview(cat_id)
    {
        $("#chkview" + cat_id).css('pointer-events', 'none');
        var checked = $("#chkview" + cat_id).is(':checked');
        var view = 0;
        if(checked == true){
            $("#spview" + cat_id).text({!!__('agent.GameActive')!!});
            $("#spview" + cat_id).attr('class', 'text-green');
            view = 1;
        }else{
            $("#spview" + cat_id).text({!!__('agent.GameInactive')!!});
            $("#spview" + cat_id).attr('class', 'text-danger');
        }
        $.ajax({
            url: "{{argon_route('game.domain.status')}}",
            type: "GET",
            data: {cat_id:  cat_id, view:view},
            dataType: 'json',
            success: function (data) {                
                $("#chkview" + cat_id).css('pointer-events', 'auto');
                if (data.error)
                {
                    alert(data.msg);
                    if(checked == true){
                        $("#chkview" + cat_id).prop('checked', false);
                        $("#spview" + cat_id).text({!!__('agent.GameInactive')!!});
                        $("#spview" + cat_id).attr('class', 'text-danger');
                    }else{
                        $("#chkview" + cat_id).prop('checked', true);
                        $("#spview" + cat_id).text({!!__('agent.GameActive')!!});
                        $("#spview" + cat_id).attr('class', 'text-green');
                    }
                }
                else
                {
                    
                }
                
            },
            error: function () {
            }
        });
    }
    function togglestatus(cat_id)
    {
        $("#chkstatus" + cat_id).css('pointer-events', 'none');
        var checked = $("#chkstatus" + cat_id).is(':checked');
        var status = 0;
        if(checked == true){
            $("#spstatus" + cat_id).text({!!__('agent.GameEnable')!!});
            $("#spstatus" + cat_id).attr('class', 'text-green');
            status = 1;
        }else{
            $("#spstatus" + cat_id).text({!!__('agent.GameDisable')!!});
            $("#spstatus" + cat_id).attr('class', 'text-danger');
        }
        $.ajax({
            url: "{{argon_route('game.domain.status')}}",
            type: "GET",
            data: {cat_id:  cat_id, status:status},
            dataType: 'json',
            success: function (data) {                
                $("#chkstatus" + cat_id).css('pointer-events', 'auto');
                if (data.error)
                {
                    alert(data.msg);
                    if(checked == true){
                        $("#chkstatus" + cat_id).prop('checked', false);
                        $("#spstatus" + cat_id).text({!!__('agent.GameEnable')!!});
                        $("#spstatus" + cat_id).attr('class', 'text-danger');
                    }else{
                        $("#chkstatus" + cat_id).prop('checked', true);
                        $("#spstatus" + cat_id).text({!!__('agent.GameDisable')!!});
                        $("#spstatus" + cat_id).attr('class', 'text-green');
                    }
                }
                else
                {
                    
                }
                
            },
            error: function () {
            }
        });
    }
</script>
@endpush
