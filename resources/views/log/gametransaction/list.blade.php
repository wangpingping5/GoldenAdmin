@extends('layouts.app',[
        'parentSection' => 'loglist7',
        'elementName' => 'Game Transaction'
    ])
@section('content')
<div class="container-fluid py-4">
	<div class="row mb-4">
        <div class="col-lg-12">
            <div class="card p-4 text-center">
                <p class="mb-0 text-dark font-weight-bold">
                    <span class="text-primary">{{Request::get('join')[0]??date('Y-m-d 00:00')}} ~ {{Request::get('join')[1]??date('Y-m-d H:i')}}</span>:</br> 
                    {{__('agent.TotalDeposit')}} : <span class="text-success">{{number_format($total['add'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;
                    {{__('agent.TotalWithdraw')}} : <span class="text-info">{{number_format($total['out'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}
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
										<input class="form-control" type="datetime-local" value="{{Request::get('join')[0]??date('Y-m-01\T00:00')}}" id="join" name="join[]">
									</div>  
									<div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
										<input class="form-control" type="datetime-local" value="{{Request::get('join')[1]??date('Y-m-d\TH:i')}}" id="join" name="join[]">
									</div>                  
									<div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
										<select class="form-control" id="userID" name="userID">
											<option value="name" @if (Request::get('userID') == "name") selected @endif>{{__('agent.ID')}}</option>
										</select>
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
									<div class="float-start"><h5>{{__('Game Transaction')}}</h5></div>								                                       
								</div>
							</div>
						</div>

						<div class="table-responsive">
							<table class="table align-items-center text-center table-flush table-bordered">
								<thead class="thead-light">
									<tr>
										<th scope="col" class="text-center">{{__('agent.No')}}</th>
										<th scope="col" class="text-center">{{__('agent.ID')}}</th>                                                
										<th scope="col" class="text-center">{{__('agent.PreviousBalance')}}</th>
										<th scope="col" class="text-center">{{__('agent.NewBalance')}}</th>
										<th scope="col" class="text-center">{{__('agent.Amount')}}</th>
										<th scope="col" class="text-center">{{__('agent.Type')}}</th>
										<th scope="col" class="text-center">{{__('agent.DateTime')}}</th>
									</tr>
								</thead>
								<tbody>
									@if (count($statistics))
										@foreach ($statistics as $stat)
											@include('log.gametransaction.partials.row')
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
		$('#userID').val('username').prop('selected',true);
		var searchInput = document.querySelector('input[name="search"]');
		searchInput.value = username;
		$('#searchfrm').submit();
	}
</script>
@endpush


