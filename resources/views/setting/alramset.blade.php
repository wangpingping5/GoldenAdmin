@extends('layouts.app',[
        'parentSection' => 'alramset',
        'elementName' => 'Alarm Set'
    ])
@section('content')
<div class="container-fluid py-3">
    <?php 
        $badge_class = \App\Models\User::badgeclass();
    ?>
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row mt-4">
                    <div class="col-md-12 col-lg-12">
                        <div class="row">
                            <p class="text-dark h5">{{__('Alarm Set')}}</p>
                            <form action="{{argon_route('msg.monitor')}}" method="POST"  id="form">
                            @csrf
                            <input type="hidden" id="recommender" value="{{auth()->user()->recommender}}">
                                <div class="table-responsive col-12">
                                    <table class="table table-bordered align-items-center text-start table-flush  mb-0" id="datalist">
                                        <tbody>
                                            <tr>
                                                <td>{{__('agent.HighOddsWinAlert')}}</td>
                                                <td><input class="form-control col-8" type="text" value="{{$data['MaxOdd']}}" id="max_odd" name="max_odd"></td>
                                            </tr>
                                            <tr>
                                                <td>{{__('agent.BigWinAlert')}}</td>
                                                <td><input class="form-control col-8" type="text" value="{{$data['MaxWin']}}" id="max_win" name="max_win"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="text-center">
                                                    <button type="submit" class="btn btn-primary mb-0 px-5" >{{__('Setting')}}</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@stop
@push('js')
@endpush
