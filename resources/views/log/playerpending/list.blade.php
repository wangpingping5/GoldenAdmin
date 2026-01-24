@extends('layouts.app',[
        'parentSection' => 'loglist4',
        'elementName' => 'PlayerPending'
    ])
@section('content')
<div class="container-fluid py-4">
	<div class="row mb-4">
		<div class="col-12">
			<div class="card">
				<div class="collapse show">										
					<div class="card-body">						
						<div class=" mt-4 mb-sm-2">
							<div class="row">
								<div class="col">
									<div class="float-start"><h5>{{__('PlayerPending')}}</h5></div>								                                       
								</div>
							</div>
						</div>

						<div class="table-responsive">
							<table class="table align-items-center text-center table-flush table-bordered">
								<thead class="thead-light">
									<tr>
										<th scope="col" class="text-center">{{__('Players')}}</th>
										<th scope="col" class="text-center">{{__('Provider')}}</th>                                                
										<th scope="col" class="text-center">{{__('Game')}}</th>
										<th scope="col" class="text-center">{{__('agent.BetAmount')}}</th>
										<th scope="col" class="text-center">{{__('agent.DateTime')}}</th>
										<th scope="col" class="text-center">{{__('agent.Status')}}</th>
										@if (auth()->user()->hasRole('admin'))
										<th scope="col" class="text-center"></th>
										@endif
									</tr>
								</thead>
								<tbody>
									@if (count($statistics))
										@foreach ($statistics as $stat)
										<?php
											$warningclass = "";
											$warningtime = strtotime("-5 minutes");
											if ($warningtime > strtotime($stat->date_time))
											{
												$warningclass = "background-color : orange;";
											}
										?>
										<tr style="{{$warningclass}}">
											@include('log.playerpending.partials.row')
										</tr>
										@endforeach
									@else
										<tr><td colspan='7'>{{__('No Data')}}</td></tr>
									@endif
								</tbody>
							</table>
						</div>
						<div class="dataTable-bottom">
							{{ $statistics->withQueryString()->links('vendor.pagination.argon') }}     							
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@stop

