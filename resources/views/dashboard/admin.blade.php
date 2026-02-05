@extends('layouts.app',[
        'parentSection' => 'dashboard',
        'elementName' => 'Dashboard'
    ])
@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card  mb-4">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">{{__('TodayTotalDeposit')}}</p>
                                        <h5 class="font-weight-bolder text-success">
                                        {{number_format($stats['todayin'], 2)}}
                                        </h5>
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">{{__('TodayTotalWithdraw')}}</p>
                                        <h5 class="font-weight-bolder text-danger">
                                        {{number_format($stats['todayout'], 2)}}
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end text-center">
                                    <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                                        <i class="ni ni-credit-card text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="progress progress-xs">
                                        <div class="progress-bar bg-uppercase" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mt-2 mb-0">
                                    <div class="numbers">
                                        <p class="text-sm text-uppercase mb-0 font-weight-bold">{{__('TodayTotalDW')}} : <span class="h5 text-danger">{{number_format($stats['todaydw'], 2)}}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card  mb-4">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">{{__('TodayTotalBet')}}</p>
                                        <h5 class="font-weight-bolder text-success">
                                        {{number_format($todaysummary?$todaysummary->totalbet:0, 2)}}
                                        </h5>
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">{{__('TodayTotalWin')}}</p>
                                        <h5 class="font-weight-bolder text-danger">
                                        {{number_format($todaysummary?$todaysummary->totalwin:0, 2)}}
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end text-center">
                                    <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                                        <i class="ni ni-money-coins text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="progress progress-xs">
                                        <div class="progress-bar bg-uppercase" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mt-2 mb-0">
                                    <div class="numbers">
                                        <p class="text-sm text-uppercase mb-0 font-weight-bold">{{__('TodayTotalBetWin')}} : <span class="h5 text-danger">{{number_format($stats['todaybetwin'], 2)}}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card  mb-4">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">{{__('MonthlyTotalDeposit')}}</p>
                                        <h5 class="font-weight-bolder text-success">
                                        {{number_format($stats['monthin'], 2)}}
                                        </h5>
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">{{__('MonthlyTotalWithdraw')}}</p>
                                        <h5 class="font-weight-bolder text-danger">
                                        {{number_format($stats['monthout'], 2)}}
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end text-center">
                                    <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                                        <i class="ni ni-credit-card text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="progress progress-xs">
                                        <div class="progress-bar bg-uppercase" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mt-2 mb-0">
                                    <div class="numbers">
                                        <p class="text-sm text-uppercase mb-0 font-weight-bold">{{__('MonthlyTotalDW')}} : <span class="h5 text-danger">{{number_format($stats['monthin'] - $stats['monthout'], 2)}}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="card  mb-4">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">{{__('MonthlyTotalBet')}}</p>
                                        <h5 class="font-weight-bolder text-success">
                                        {{number_format($stats['monthbet'], 2)}}
                                        </h5>
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">{{__('MonthlyTotalWin')}}</p>
                                        <h5 class="font-weight-bolder text-danger">
                                        {{number_format($stats['monthwin'], 2)}}
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4 text-end text-center">
                                    <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                                        <i class="ni ni-money-coins text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="progress progress-xs">
                                        <div class="progress-bar bg-uppercase" role="progressbar" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mt-2 mb-0">
                                    <div class="numbers">
                                        <p class="text-sm text-uppercase mb-0 font-weight-bold">{{__('MonthlyTotalBetWin')}} : <span class="h5 text-danger">{{number_format($stats['monthbet'] - $stats['monthwin'], 2)}}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-lg-4 col-md-8 col-12">
                    <div class="card shadow mb-3">
                        <div class="card-header bg-transparent">
                            <div class="row align-items-center">
                                <div class="col">
                                    <p class="text-sm text-uppercase mb-0 font-weight-bold">{{__('BetWinSettlement')}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="chart">
                                <canvas id="betwinchart" class="chart-canvas" height="300px"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-8 col-12">
                    <div class="card shadow mb-3">
                        <div class="card-header bg-transparent">
                            <div class="row align-items-center">
                                <div class="col">
                                    <p class="text-sm text-uppercase mb-0 font-weight-bold">{{__('DWSettlement')}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="chart">
                                <canvas id="inoutchart" class="chart-canvas" height="300px"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-8 col-12">
                    <div class="card shadow mb-3">
                        <div class="card-header bg-transparent">
                            <div class="row align-items-center">
                                <div class="col">
                                    <p class="text-sm text-uppercase mb-0 font-weight-bold">{{__('BetWinByGame')}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="chart">
                                <canvas id="gamechart" class="chart-canvas" height="300px"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
@php

    $date_start = date("m-d", strtotime('-30 days'));
    $date_end = date("m-d");
    $labels = [];
    $wins = [];
    $bets = [];
    $totalin = [];
    $totalout = [];
    

    for($i=1; $i<=30; $i++){
        $label = date("m-d", strtotime(-30 + $i . ' days'));
        $labels[] = $label;
        $wins[$label] = 0;
        $bets[$label] = 0;
        $totalin[$label] = 0;
        $totalout[$label] = 0;
    }

    foreach($monthsummary AS $stat){
        $label = date("m-d", strtotime($stat->date));
        if( isset($wins[$label]) ){
            $wins[$label] += $stat->totalwin;
        }
        if( isset($bets[$label]) ){
            $bets[$label] += $stat->totalbet;
        }
    }
    foreach($monthInout AS $stat){
        $label = date("m-d", strtotime($stat->date));
        if( isset($totalin[$label]) ){
            $totalin[$label] += $stat->totalin;
        }
        if( isset($totalout[$label]) ){
            $totalout[$label] += $stat->totalout;
        }
    }

    $cat_labels = [];
    $cat_bet = [];

    foreach($monthcategory AS $cat){
        $label = $cat->title;
        $cat_labels[] = $label;
        $cat_bet[] = $cat->totalbet;
    }

    
@endphp
@push('js')
    <script>
        window.chartColors = {
            red: '#FF6384',
            orange: '#FF9F40',
            yellow: '#FFCD56',
            green: '#4BCB4B',
            blue: '#5e72e4',
            purple: '#a8b8d8',
            grey: '#5e72e4'
        };
        // Chart.scaleService.updateScaleDefaults('linear', {
        //     ticks: {
        //         min: 0
        //     }
        // });
        var config = {
            type: 'line',
            data: {
                labels: ["{!! implode('","', $labels) !!}"],
                datasets: [{
                    label: "{!! __('Win') !!}",
                    tension: 0.4,
                    borderWidth: 0,
                    pointRadius: 2,
                    pointBackgroundColor: window.chartColors.red,
                    borderColor: window.chartColors.red,
                    borderWidth: 3,
                    backgroundColor: window.chartColors.red,
                    data: [@foreach($wins AS $win) {{ $win }}, @endforeach],
                    maxBarThickness: 6,
                    fill: false,
                }, {
                    label: "{!! __('Bet') !!}",
                    tension: 0.4,
                    borderWidth: 0,
                    pointRadius: 2,
                    pointBackgroundColor: window.chartColors.blue,
                    borderColor: window.chartColors.blue,
                    borderWidth: 3,
                    backgroundColor: window.chartColors.blue,
                    maxBarThickness: 6,
                    fill: false,
                    data: [@foreach($bets AS $bet) {{ $bet }}, @endforeach],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                legend: {
                    display: false,
                }
                },
                interaction: {
                intersect: false,
                mode: 'index',
                },
                scales: {
                    y: {
                        grid: {
                            drawBorder: false,
                            display: true,
                            drawOnChartArea: true,
                            drawTicks: false,
                            borderDash: [5, 5]
                        },
                        ticks: {
                            display: true,
                            padding: 10,
                            color: '#b2b9bf',
                            font: {
                                size: 11,
                                family: "Open Sans",
                                style: 'normal',
                                lineHeight: 2
                            },
                        }
                    },
                    x: {
                        grid: {
                            drawBorder: false,
                            display: true,
                            drawOnChartArea: true,
                            drawTicks: true,
                            borderDash: [5, 5]
                        },
                        ticks: {
                            display: true,
                            color: '#b2b9bf',
                            padding: 10,
                            font: {
                                size: 11,
                                family: "Open Sans",
                                style: 'normal',
                                lineHeight: 2
                            },
                        }
                    },
                },
                
            }
        };
        var inoutconfig = {
            type: 'line',
            data: {
                labels: ["{!! implode('","', $labels) !!}"],
                datasets: [{
                    label: "{!! __('Withdraw') !!}",
                    tension: 0.4,
                    borderWidth: 0,
                    pointRadius: 2,
                    pointBackgroundColor: window.chartColors.red,
                    borderColor: window.chartColors.red,
                    borderWidth: 3,
                    backgroundColor: window.chartColors.red,
                    data: [@foreach($totalout AS $out) {{ $out }}, @endforeach],
                    maxBarThickness: 6,
                    fill: false,
                }, {
                    label: "{!! __('Deposit') !!}",
                    tension: 0.4,
                    borderWidth: 0,
                    pointRadius: 2,
                    pointBackgroundColor: window.chartColors.blue,
                    borderColor: window.chartColors.blue,
                    borderWidth: 3,
                    backgroundColor: window.chartColors.blue,
                    maxBarThickness: 6,
                    fill: false,
                    data: [@foreach($totalin AS $in) {{ $in }}, @endforeach],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                legend: {
                    display: false,
                }
                },
                interaction: {
                intersect: false,
                mode: 'index',
                },
                scales: {
                    y: {
                        grid: {
                            drawBorder: false,
                            display: true,
                            drawOnChartArea: true,
                            drawTicks: false,
                            borderDash: [5, 5]
                        },
                        ticks: {
                            display: true,
                            padding: 10,
                            color: '#b2b9bf',
                            font: {
                                size: 11,
                                family: "Open Sans",
                                style: 'normal',
                                lineHeight: 2
                            },
                        }
                    },
                    x: {
                        grid: {
                            drawBorder: false,
                            display: true,
                            drawOnChartArea: true,
                            drawTicks: true,
                            borderDash: [5, 5]
                        },
                        ticks: {
                            display: true,
                            color: '#b2b9bf',
                            padding: 10,
                            font: {
                                size: 11,
                                family: "Open Sans",
                                style: 'normal',
                                lineHeight: 2
                            },
                        }
                    },
                },
                
            }
        };
        var dataset = {
            label: "게임사별 베팅",
            weight: 9,
            cutout: 0,
            tension: 0.9,
            pointRadius: 2,
            borderWidth: 1,
            backgroundColor: [
                window.chartColors.green, 
                window.chartColors.red,  
                window.chartColors.yellow,
                window.chartColors.purple,
                window.chartColors.blue
            ],
            borderColor: '#17c1e8',
            data: [{{implode(',', $cat_bet)}}]
        }

        var labels=["{!! implode('","', $cat_labels) !!}"]; 
        
        var datasets={ datasets:[dataset], labels:labels }

        var configpie = {
            type: 'pie',
            data: datasets, //데이터 셋 
            options: {
                plugins: {
                    legend: {
                        display: true,
                        position: 'right',
                        labels: {
                            generateLabels: function(chart) {
                                const dataset = chart.data.datasets[0]; // 첫 번째 데이터셋
                                return chart.data.labels.map((label, i) => ({
                                    text: `${label}: ${dataset.data[i] == 0?dataset.data[i].toLocaleString("ko-KR"):dataset.data[i].toString().length > 9?Math.floor(dataset.data[i] / 100000000).toLocaleString("ko-KR") + "억":dataset.data[i].toString().length == 9?(dataset.data[i] / 100000000).toFixed(1) + "억":dataset.data[i].toString().length > 6?(Math.floor(dataset.data[i] / 10000)).toLocaleString("ko-KR") + "만":dataset.data[i].toString().length == 6?(dataset.data[i] / 10000).toFixed(1) + "만":dataset.data[i].toLocaleString("ko-KR")}원`,
                                    fillStyle: dataset.backgroundColor[i],
                                    strokeStyle: dataset.backgroundColor[i],
                                    hidden: chart.getDatasetMeta(0).data[i].hidden,
                                    index: i
                                }));
                            }
                        }
                    }
                }
            }
        }

        window.onload = function() {
            var ctx = document.getElementById('betwinchart').getContext('2d');
            window.myLine = new Chart(ctx, config);
            var ctx1 = document.getElementById('inoutchart').getContext('2d');
            window.myLine1 = new Chart(ctx1, inoutconfig);

            var ctx2 = document.getElementById('gamechart').getContext('2d');
            window.myLine2 = new Chart(ctx2, configpie);
        };

    </script>
@endpush