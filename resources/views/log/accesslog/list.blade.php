@extends('layouts.app',[
        'parentSection' => 'loglist8',
        'elementName' => 'AccessLog'
    ])
@section('content')
<div class="container-fluid py-4">
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
												<option value="username" @if (Request::get('userID') == 'username') selected @endif>{{__('agent.ID')}}</option>
												<option value="ip_address" @if (Request::get('userID') == 'ip_address') selected @endif>IP</option>
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
									<div class="float-start"><h5>{{__('AccessLog')}}</h5></div>								                                       
								</div>
							</div>
						</div>

						<div class="table-responsive">
							<table class="table align-items-center text-center table-flush table-bordered">
								<thead class="thead-light">
									<tr>
										<th scope="col" class="text-center">{{__('agent.ID')}}</th>
										<th scope="col" class="text-center">{{__('agent.IP')}}</th>                                                
										<th scope="col" class="text-center">{{__('agent.Message')}}</th>
										<th scope="col" class="text-center">{{__('agent.DateTime')}}</th>
										<th scope="col" class="text-center">{{__('agent.Detail')}}</th>
									</tr>
								</thead>
								<tbody>
									@if (count($activities))
										@foreach ($activities as $activity)
                                            <tr>
                                                @if (isset($adminView))
                                                    <td class="text-center">
                                                    <?php 
                                                        $badge_class = \App\Models\User::badgeclass();
                                                    ?>
                                                        @if (isset($user))
                                                            {{ $activity->user->present()->username }}
                                                        @else
                                                        <a href="javascript:;" onclick="userClick('{{$activity->userdata->username}}');" data-toggle="tooltip" data-original-title="{{$activity->userdata->parents(auth()->user()->role_id)}}">
                                                            <span class="badge {{$badge_class[$activity->userdata->role_id]}} rounded-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$activity->userdata->parents(auth()->user()->role_id, $activity->userdata->role_id+1)!!}">{{$activity->userdata->username}}&nbsp;&nbsp;[{{__($activity->userdata->role->name)}}]</span>
                                                        @endif
                                                    </td>
                                                @endif
                                                <td class="text-center">{{ $activity->ip_address }}</td>
                                                <td class="text-center">{{ $activity->description }}</td>
                                                <td class="text-center">{{ date(config('app.date_time_format'), strtotime($activity->created_at)) }}</td>
                                                <td class="text-center">{{ $activity->user_agent }}</td>
                                            </tr>
										@endforeach
									@else
										<tr><td colspan='11'>{{__('No Data')}}</td></tr>
									@endif
								</tbody>
							</table>
						</div>
						<div class="dataTable-bottom">
							{{ $activities->withQueryString()->links('vendor.pagination.argon') }}     							
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