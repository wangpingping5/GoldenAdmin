@extends('layouts.app',[
        'parentSection' => 'loglist1',
        'elementName' => 'PartnerTransaction'
    ])
@section('content')
<div class="container-fluid py-4">
	<div class="row mb-4">
        <div class="col-lg-12">
            <div class="card p-4 text-center">
                <p class="mb-0 text-dark font-weight-bold">
                    <span class="text-primary">{{Request::get('join')[0]??date('Y-m-01 00:00')}} ~ {{Request::get('join')[1]??date('Y-m-d H:i')}}</span>:</br> 
                    {{__('agent.TotalDeposit')}} : <span class="text-success">{{number_format($total['add'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;
                    {{__('agent.TotalWithdraw')}} : <span class="text-info">{{number_format($total['out'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}&nbsp;
                    {{__('agent.ConvertRolling')}} : <span class="text-danger">{{number_format($total['deal_out'])}}</span>{{\App\Models\User::USER_CURRENCY_SYMBOLS[auth()->user()->currency ?? 'USD']}}					
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
										<div class="input-group input-group-alternative">
											<select class="form-control" id="userID" name="userID">
												<option value="" @if (Request::get('userID') == "") selected @endif>--{{__('agent.All')}}--</option>
												<option value="id" @if (Request::get('userID') == 'id') selected @endif>{{__('agent.No')}}</option>
												<option value="user" @if (Request::get('userID') == 'user') selected @endif>{{__('Players')}}</option>
												<option value="admin" @if (Request::get('userID') == 'admin') selected @endif>{{__('agent.Payer')}}</option>
											</select>
											<span class="input-group-text"><i class="ni ni-bold-down"></i></span>
										</div>
									</div>  
									<div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
										<div class="input-group input-group-alternative">
											<select class="form-control" id="type" name="type">
												<option value="" @if (Request::get('type') == "") selected @endif>--{{__('agent.Type')}}--</option>
												<option value="add" @if (Request::get('type') == 'add') selected @endif>{{__('agent.Deposit')}}</option>
												<option value="out" @if (Request::get('type') == 'out') selected @endif>{{__('agent.Withdraw')}}</option>
												<option value="deal_out" @if (Request::get('type') == 'deal_out') selected @endif>{{__('agent.ConvertRolling')}}</option>
												<option value="ggr_out" @if (Request::get('type') == 'ggr_out') selected @endif>{{__('GGR')}}</option>
											</select>
											<span class="input-group-text"><i class="ni ni-bold-down"></i></span>
										</div>
									</div>  
									<div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
										<div class="input-group input-group-alternative">
											<select class="form-control" id="mode" name="mode">
												<option value="" @if (Request::get('mode') == "") selected @endif>--{{__('agent.Type')}}--</option>
												<option value="manual" @if (Request::get('mode') == 'manual') selected @endif>{{__('agent.Manual')}}</option>
												<option value="account" @if (Request::get('mode') == 'account') selected @endif>{{__('agent.BankAccount')}}</option>
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
									<div class="float-start"><h5>{{__('PartnerTransaction')}}</h5></div>								                                       
								</div>
							</div>
						</div>

						<div class="table-responsive">
							<table class="table align-items-center text-center table-flush table-bordered">
								<thead class="thead-light">
									<tr>
										<th scope="col" class="text-center">{{__('agent.No')}}</th>
										<th scope="col" class="text-center">{{__('Players')}}</th>                                                
										<th scope="col" class="text-center">{{__('agent.Payer')}}</th>
										<th scope="col" class="text-center">{{__('agent.Payer')}}{{__('agent.Balance')}}</th>
										<th scope="col" class="text-center">{{__('agent.PreviousBalance')}}</th>
										<th scope="col" class="text-center">{{__('agent.NewBalance')}}</th>
										<th scope="col" class="text-center">{{__('agent.Amount')}}</th>
										<th scope="col" class="text-center">{{__('agent.Type')}}</th>
										<th scope="col" class="text-center">{{__('agent.BankAccount')}}{{__('agent.Type')}}</th>
										<th scope="col" class="text-center">{{__('agent.Notes')}}</th>
										<th scope="col" class="text-center">{{__('agent.DateTime')}}</th>
									</tr>
								</thead>
								<tbody>
									@if (count($statistics))
										@foreach ($statistics as $stat)
											@include('log.partials.partnertransaction_row')
										@endforeach
									@else
										<tr><td colspan='11'>{{__('No Data')}}</td></tr>
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
	function userClick(username,user){
		if(user == true)
		{
			$('#userID').val('user').prop('selected',true);
		}else{
			$('#userID').val('admin').prop('selected',true);
		}
		
		var searchInput = document.querySelector('input[name="search"]');
		searchInput.value = username;
		$('#searchfrm').submit();
	}
</script>
@endpush
