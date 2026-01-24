@extends('layouts.app',[
        'parentSection' => 'playerconnectedlist',
        'elementName' => 'Player Connected List'
    ])
@section('content')
<div class="container-fluid py-3">
    <?php 
        $statuses = \App\Support\Enum\UserStatus::lists(); 
        $status_class = \App\Support\Enum\UserStatus::bgclass(); 
    ?>
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row mt-4">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">{{ __('Player Connected List')}}</p>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col">                    
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-center table-flush  mb-0" id="datalist">
                                <thead>
                                    <tr>
                                        <th scope="col">{{__('agent.No')}}</th>
                                        <th scope="col">{{__('agent.Parent')}}</th>
                                        <th scope="col">{{__('agent.Level')}}</th>
                                        <th scope="col">{{__('agent.ID')}}</th>
                                        <th scope="col">{{__('agent.Balance')}}</th>
                                        <th scope="col">{{__('agent.Rolling')}}</th>
                                        <th scope="col">{{__('agent.Domain')}}</th>
                                        <th scope="col">{{__('agent.SendMessage')}}</th>
                                        <th scope="col">{{__('agent.BetHistory')}}</th>
                                        <th scope="col">{{__('Logout')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($users) > 0)
                                        @foreach ($users as $user)
                                            <tr>
                                                @include('player.partials.connected_row')
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="10">{{__('No Data')}}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $users->withQueryString()->links('vendor.pagination.argon') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop