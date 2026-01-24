@extends('layouts.app',[
        'parentSection' => 'loglist6',
        'elementName' => 'Share GameStat'
])

@section('content')
<div class="container-fluid py-4">
	<div class="row mb-4">
        <div class="col-lg-12">
            <div class="card p-4 text-center">
                <p class="mb-0 text-dark font-weight-bold">
                    <span class="text-primary">{{Request::get('join')[0]??date('Y-m-d H:i')}} ~ {{Request::get('join')[1]??date('Y-m-d H:i')}}</span>: 까지의</br> 
                    배팅합계 : <span class="text-success">{{number_format($total['bet'])}}</span>원&nbsp;
                    당첨합계 : <span class="text-info">{{number_format($total['win'])}}</span>원&nbsp;
                    받치기배팅합계 : <span class="text-danger">{{number_format($total['sharebet'])}}</span>원&nbsp;
                    받치기당첨합계 : <span class="text-info">{{number_format($total['sharewin'])}}</span>원					
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
										<input class="form-control" type="datetime-local" value="{{Request::get('join')[0]??date('Y-m-d\TH:i', strtotime('-1 days'))}}" id="join" name="join[]">
									</div>  
									<div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
										<input class="form-control" type="datetime-local" value="{{Request::get('join')[1]??date('Y-m-d\TH:i')}}" id="join" name="join[]">
									</div>                  
									<div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
										<div class="input-group input-group-alternative">
											<select class="form-control" id="userID" name="userID">
												<option value="username" @if (Request::get('userID') == 'username') selected @endif>유저</option>
												<option value="partner" @if (Request::get('userID') == 'partner') selected @endif>파트너</option>
												<option value="game" @if (Request::get('userID') == 'game') selected @endif>게임명</option>
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
												<button type="submit" class="form-control btn btn-primary" id="searchsubmit">검색하기</button>
											</div>
										</div>
									</div>
								</div>
							</form>
						</div>

						<div class=" mt-4 mb-sm-2">
							<div class="row">
								<div class="col">
									<div class="float-start"><h5>{{__('Share GameStat')}}</h5></div>								                                       
								</div>
							</div>
						</div>

						<div class="table-responsive">
							<table class="table align-items-center text-center table-bordered">
								<thead class="thead-light">
									<tr>
										<th scope="col" class="text-center">유저</th>
										<th scope="col" class="text-center">파트너</th>                                                
										<th scope="col" class="text-center">받치기상위</th>
										<th scope="col" class="text-center">게임사</th>
										<th scope="col" class="text-center">게임명</th>
										<th scope="col" class="text-center">배팅금</th>
										<th scope="col" class="text-center">당첨금</th>
										<th scope="col" class="text-center">롤링</th>
										<th scope="col" class="text-center">롤링금</th>
										<th scope="col" class="text-center">배팅시간</th>
										<th scope="col" class="text-center">배팅상세</th>
									</tr>
								</thead>
								<tbody>
									@if (count($sharebetlogs))
										@foreach ($sharebetlogs as $stat)
											@include('log.sharegame.partials.row')
										@endforeach
									@else
										<tr><td colspan='11'>{{__('No Data')}}</td></tr>
									@endif
								</tbody>
							</table>
						</div>
						<div class="dataTable-bottom">
							{{ $sharebetlogs->withQueryString()->links('vendor.pagination.argon') }}     							
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