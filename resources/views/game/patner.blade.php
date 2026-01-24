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
                        <div class="row">        
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="role" name="role">
                                        <option value="" @if (Request::get('role') == '') selected @endif>{{__('agent.All')}}</option>
                                        @for ($level=3;$level<=auth()->user()->role_id;$level++)
                                        <option value="{{$level}}" @if (Request::get('role') == $level) selected @endif> {{__(\App\Models\Role::find($level)->name)}}</option>
                                        @endfor
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div>                   
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="kind" name="kind">
                                        <option value="" @if (Request::get('kind') == '') selected @endif>{{__('agent.ID')}}</option>
                                        <option value="1" @if (Request::get('kind') == 1) selected @endif>{{__('agent.GameName')}}</option>
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
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
                                        <th scope="col">{{__('Partners')}}</th>
                                        <th scope="col">{{__('agent.GameName')}}</th>
                                        <th scope="col">{{__('agent.GameCount')}}</th>
                                        <th scope="col">{{__('agent.GameProvider')}}</th>
                                        <th scope="col">{{__('agent.ActiveManager')}}</th>
                                        <th scope="col">{{__('agent.InactiveManager')}}</th>
                                        <th scope="col">{{__('agent.OperatingManager')}}</th>
                                        <th scope="col">{{__('agent.MaintenanceManager')}}</th>
                                        <th scope="col">{{__('agent.AllApproved')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($users) > 0)
                                        @foreach ($users as $user)
                                            <tr>
                                            @if (count($categories)>0)
                                                <td rowspan="{{$categories->count()}}"> 
                                                <a href="#" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!! $user->parents(auth()->user()->role_id) !!}">
                                                    {{ $user->username }} <span class="btn {{$badge_class[$user->role_id]}} px-2 py-1 mb-1 mx-1 rounded-0}}">{{__($user->role->name)}}</span>
                                                </a>
                                                </td>
                                                @include('game.partials.row_patner')
                                            @endif
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="9">{{__('No Data')}}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $categories->withQueryString()->links('vendor.pagination.argon') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
@push('js')

@endpush
