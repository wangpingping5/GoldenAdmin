@extends('layouts.app',[
        'parentSection' => 'statistics',
        'elementName' => 'Statistics'
    ])
@section('content')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card bg-white mb-4">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-success font-weight-bold opacity-7">CPU</p>
                                        <h5 class="text-success font-weight-bolder mb-0">
                                        {{number_format($serverstat['cpu'],2)}}
                                        </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-success shadow text-center rounded-circle">
                                    <i class="fas fa-chart-bar text-white text-lg opacity-10" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-12">
                <div class="card bg-white mb-4">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-warning font-weight-bold opacity-7">RAM</p>
                                    <h5 class="text-warning font-weight-bolder mb-0">
                                    {{number_format($serverstat['ram'],2)}}%
                                    </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-warning shadow text-center rounded-circle">
                                    <i class="fas fa-chart-area text-white text-lg opacity-10" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-12">
                <div class="card bg-white mb-4">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-primary font-weight-bold opacity-7">DISK</p>
                                    <h5 class="text-primary font-weight-bolder mb-0">
                                    {{number_format($serverstat['disk'],2)}}%
                                    </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-primary shadow text-center rounded-circle">
                                    <i class="fas fa-chart-pie text-white text-lg opacity-10" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 col-12">
                <div class="card bg-white mb-4">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-info font-weight-bold opacity-7">{{__('agent.NetworkConnectionCount')}}</p>
                                    <h5 class="text-info font-weight-bolder mb-0">
                                    {{$serverstat['connections']}}
                                    </h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-info shadow text-center rounded-circle">
                                    <i class="ni ni-ui-04 text-white text-lg opacity-10" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header bg-transparent">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0">{{__('agent.ServerResourceStatus')}}</h4>
                            </div>
                        </div>                        
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div >
                                    @foreach ($agents as $agkey => $agvalue)
                                        
                                        @if ($agvalue > 100000)
                                            <p><span class="description"><i class='fas fa-battery-full text-primary'></i> {{$agkey}} {{__('agent.Balance')}}:</span>&nbsp;
                                            <span class="result badge badge-pill badge-md bg-success">{{number_format(floatval($agvalue))}} </span></p>
                                        @else
                                            <p><span class="description"><i class='fas fa-battery-quarter text-danger'></i> {{$agkey}} {{__('agent.Balance')}}:</span>&nbsp;
                                            <span class="result badge badge-pill badge-md bg-danger">{{number_format(floatval($agvalue))}} </span></p>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>    
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header bg-transparent">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0">{{__('agent.ProviderBalanceStatus')}}</h4>
                            </div>
                        </div>                        
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                
                                    @if (floatval($serverstat['ramused'] / $serverstat['ramtotal'] * 100)  >= 98)
                                        <p><span class="description"><i class='fas fa-tools text-danger'></i> RAM Available:</span> 
                                        <span class="result badge badge-pill badge-md bg-danger"><?php echo $serverstat['ramavailable']; ?> GB</span>
                                    @else
                                        <p><span class="description"><i class='fas fa-tools text-success'></i> RAM Available:</span> 
                                        <span class="result badge badge-pill badge-md bg-success"><?php echo $serverstat['ramavailable']; ?> GB</span>
                                    @endif                                    
                                </p>
                                <p><span class="description"><i class='fas fa-tools text-primary'></i> RAM Used:</span> <span class="result badge badge-pill badge-md bg-primary"><?php echo $serverstat['ramused']; ?> GB</span></p>
                                <p><span class="description"><i class='fas fa-tools text-primary'></i> RAM Total:</span> <span class="result badge badge-pill badge-md bg-primary"><?php echo $serverstat['ramtotal']; ?> GB</span></p>
                            </div>
                            <div class="col-6">
                                 
                                    @if (floatval($serverstat['diskused'] / $serverstat['disktotal'] * 100)  >= 98)
                                        <p><span class="description"><i class='fas fa-hdd text-danger'></i> Hard Disk Free:</span>
                                        <span class="result badge badge-pill badge-md bg-danger"><?php echo $serverstat['diskfree']; ?> GB</span></p>
                                    @else
                                        <p><span class="description"><i class='fas fa-hdd text-success'></i> Hard Disk Free:</span>
                                        <span class="result badge badge-pill badge-md bg-success"><?php echo $serverstat['diskfree']; ?> GB</span>
                                    @endif  
                                
                                <p><span class="description"><i class='fas fa-hdd text-primary'></i> Hard Disk Used:</span> <span class="result badge badge-pill badge-md bg-primary"><?php echo $serverstat['diskused']; ?> GB</span></p>
                                <p><span class="description"><i class='fas fa-hdd text-primary'></i> Hard Disk Total:</span> <span class="result badge badge-pill badge-md bg-primary"><?php echo $serverstat['disktotal']; ?> GB</span></p>
                            </div>
                            
                        </div>   
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-transparent">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-0">{{__('agent.ServerLog')}}</h4>
                            </div>
                        </div>                        
                    </div>
                    <div class="card-body">
                        <!-- Chart -->
                        <div>
                            <p>{{__('agent.FileSize')}} - {{number_format($filesize/1000)}}KB</p>
                            <textarea id="content" name="content" rows="10" cols="150" style="width:100%;">{{$strinternallog}}</textarea>
                            <a href="{{argon_route('system.logreset')}}"><button type="button" class="btn btn-primary">{{__('agent.LogFileReset')}}</button></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

