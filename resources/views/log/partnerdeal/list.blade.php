@extends('layouts.app',[
        'parentSection' => 'loglist2',
        'elementName' => 'PartnerDeal'
    ])
@section('content')
<div class="container-fluid py-4">
	<div class="row mb-4">
        <div class="col-lg-12">
            <div class="card p-4 text-center">
                <p class="mb-0 text-dark font-weight-bold">
                    <span class="text-primary">{{Request::get('join')[0]??date('Y-m-d H:i', strtotime('-1 hours'))}} ~ {{Request::get('join')[1]??date('Y-m-d H:i')}}</span>: </br> 
                    {{__('agent.TotalBetAmount')}} : <span class="text-success">{{number_format($total['bet'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;
                    {{__('agent.TotalWinAmount')}} : <span class="text-info">{{number_format($total['win'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;
                    {{__('agent.TotalRollingAmount')}} : <span class="text-danger">{{number_format($total['deal'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;
                    {{__('agent.TotalNetWin/Loss')}} : <span class="text-danger">{{number_format($total['ggr'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}					
                </p>                 
            </div>
        </div>
    </div>
	<div class="row mb-4">
		<div class="col-12">
			<div class="card">
				<div class="collapse show">
					
					
					<div class="card-body">
						<div class="row shadow bg-light p-1 rounded-1">
							<form action="" id="searchfrm" method="GET">
								<div class="row"> 
									<div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
										<input class="form-control" type="datetime-local" value="{{Request::get('join')[0]??date('Y-m-d\TH:i', strtotime('-1 hours'))}}" id="join" name="join[]">
									</div>  
									<div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
										<input class="form-control" type="datetime-local" value="{{Request::get('join')[1]??date('Y-m-d\TH:i')}}" id="join" name="join[]">
									</div>                  
									<div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
										<div class="input-group input-group-alternative">
											<select class="form-control" id="userID" name="userID">
												<option value="" @if (Request::get('userID') == "") selected @endif>--{{__('agent.All')}}--</option>
												<option value="user" @if (Request::get('userID') == 'user') selected @endif>{{__('Partner')}}</option>
												<option value="player" @if (Request::get('userID') == 'player') selected @endif>{{__('Players')}}</option>
												<option value="game" @if (Request::get('userID') == 'game') selected @endif>{{__('Game')}}</option>
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
												<button type="submit" class="form-control btn btn-primary" id="searchsubmit">{{__('agent.Search')}}</button>
											</div>
										</div>
									</div>
								</div>
							</form>
						</div>

						<div class=" mt-4 mb-sm-2">
							<div class="row">
								<div class="col">
									<div class="float-start"><h5>{{__('PartnerDeal')}}</h5></div>								                                       
								</div>
							</div>
						</div>

						<div class="table-responsive">
							<table class="table align-items-center text-center table-flush table-bordered">
								<thead class="thead-light">
									<tr>
										<th scope="col" class="text-center">{{__('agent.No')}}</th>
										<th scope="col" class="text-center">{{__('Partners')}}</th>                                                
										<th scope="col" class="text-center">{{__('Players')}}</th>
										<th scope="col" class="text-center">{{__('Game')}}</th>
										<th scope="col" class="text-center">{{__('Provider')}}</th>
										<th scope="col" class="text-center">
                                            {{__('agent.BetAmount')}}
                                            <hr class='my-0'>
                                            {{__('agent.WinAmount')}}
                                            <hr class='my-0'>
                                            {{__('agent.RollingAmount')}}</th>
										<th scope="col" class="text-center">{{__('agent.DateTime')}}</th>
									</tr>
								</thead>
								<tbody>
									@if (count($statistics))
										@foreach ($statistics as $stat)
											@include('log.partnerdeal.partials.row')
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


@push('js')
<script>
	function userClick(username){
		$('#userID').val('player').prop('selected',true);
		var searchInput = document.querySelector('input[name="search"]');
		searchInput.value = username;
		$('#searchfrm').submit();
	}
</script>
@endpush