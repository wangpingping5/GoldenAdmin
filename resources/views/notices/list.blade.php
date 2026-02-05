@extends('layouts.app',[
        'parentSection' => 'notices',
        'elementName' => 'Notices'
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
										<input class="form-control" type="datetime-local" value="{{Request::get('join')[0]??date('Y-01-01\TH:i', strtotime('-1 year'))}}" id="join" name="join[]">
									</div>  
									<div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
										<input class="form-control" type="datetime-local" value="{{Request::get('join')[1]??date('Y-m-d\TH:i')}}" id="join" name="join[]">
									</div>                  
									<div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
										<div class="input-group input-group-alternative">
											<select class="form-control" id="status" name="status">
												<option value="" @if (Request::get('status') == "") selected @endif>{{__('agent.No')}}</option>
												<option value="1" @if (Request::get('status') == 1) selected @endif>{{__('agent.Title')}}</option>
												<option value="2" @if (Request::get('status') == 2) selected @endif>{{__('agent.Maker')}}</option>
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
												<button type="submit" class="form-control btn btn-primary">{{__('agent.Search')}}</button>
											</div>
										</div>
									</div>
								</div>
							</form>
						</div>
						<div class="border-0 mx-2 mb-sm-2 mt-4">
							<div class="row">
								<div class="col">
									<div class="float-start"><h5 class="mb-0">{{__('Notices')}}</h5></div>
									@if (auth()->user()->isInoutPartner())
									<div class="float-end">
										<a onClick="deleteNotices();" class="btn btn-danger float-end rounded-0 mx-1" id="btn_delete">{{__('Deleted')}}</a>										
										<a onclick="activeNotices(0);" class="btn btn-warning float-end rounded-0 mx-1" id="btn_active">{{__('Disable')}}</a>
										<a onclick="activeNotices(1);" class="btn btn-success float-end rounded-0 mx-2" id="btn_active">{{__('Active')}}</a>
										<a onclick="window.open('{{ argon_route('notices.edit',['id' => '', 'edit'=>false]) }}','{{__("Add")}}','width=800, height=1080, left=500, top=100, toolbar=no, menubar=no, scrollbars=no, resizable=yes');" class="btn btn-primary float-end rounded-0">{{__('Add')}}</a>
									</div>
									@endif                                        
								</div>
							</div>
						</div> 

						<div class="table-responsive">
							<table class="table align-items-center table-flush table-bordered">
								<thead class="thead-light">
									<tr>
										<th scope="col" class="w-5 text-center"><input type="checkbox" id="allCheck"></th>
										<th scope="col" class="text-center">{{__('agent.No')}}</th>
										<th scope="col" class="w-30 text-center">{{__('agent.Title')}}</th>                                                
										<th scope="col" class="w-20 text-center">{{__('agent.DateTime')}}</th>
										<th scope="col" class="text-center">{{__('agent.NoticeType')}}</th>
										<th scope="col" class="text-center">{{__('agent.NoticeTarget')}}</th>
										<th scope="col" class="text-center">{{__('agent.Status')}}</th>
										@if (auth()->user()->hasRole('admin'))
										<th scope="col" class="text-center">{{__('agent.Maker')}}</th>
										@endif
										<th scope="col" class="w-8 text-center"></th>
									</tr>
								</thead>
								<tbody>
									@if (count($notices))
										@foreach ($notices as $notice)
											@include('notices.partials.row')
										@endforeach
									@else
										<tr><td colspan='9'>{{__('No Data')}}</td></tr>
									@endif
								</tbody>
							</table>
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
    $('#allCheck').change(function(){
        if($(this).prop('checked')){
            $('tbody tr td input[name=notice_CheckBox]').each(function(){
                $(this).prop('checked', true);
            });
        }else{
            $('tbody tr td input[name=notice_CheckBox]').each(function(){
                $(this).prop('checked', false);
            });
        }
    });
    
    
    function deleteNotices()
    {
        var checkbox = $("input[name=notice_CheckBox]:checked");
        var user_ids=[];
        checkbox.each(function(i) {
            var tr = checkbox.parent().parent().eq(i);
            var td = tr.children();
            user_ids.push(td.eq(1).text());
        });
        var alldata = user_ids;
        if(user_ids.length == 0){
            alert("{!!__('agent.NoticeMsg1')!!}");
            return;
        }
        if(confirm("{!!__('agent.NoticeMsg2')!!}")){            
            $("#btn_delete").css('pointer-events', 'none');
            $.ajax({
                url: "{{ argon_route('notices.delete') }}",
                type: "GET",
                data: {ids:  user_ids},
                dataType: 'json',
                success: function (data) {
                    $("#btn_delete").css('pointer-events', 'auto');
                    if (data.error)
                    {
                        alert(data.msg);
                    }
                    else
                    {
                        location.reload();
                    }
                    
                },
                error: function () {
                }
            });
        }
    } 
	
	function activeNotices(id){
		var checkbox = $("input[name=notice_CheckBox]:checked");
        var user_ids=[];
        checkbox.each(function(i) {
            var tr = checkbox.parent().parent().eq(i);
            var td = tr.children();
            user_ids.push(td.eq(1).text());
        });
        var alldata = user_ids;
        if(user_ids.length == 0){
            alert("{!!__('agent.NoticeMsg1')!!}");
            return;
        }
		var poptext = '';
		if(id==1){
			poptext = "{!!__('agent.NoticeMsg3')!!}";
		}else{
			poptext = "{!!__('agent.NoticeMsg4')!!}";
		}
		if(confirm(poptext)){            
            $("#btn_active").css('pointer-events', 'none');
            $.ajax({
                url: "{{ argon_route('notices.statusupdate') }}",
                type: "GET",
                data: {ids:  user_ids,
					active: id},
                dataType: 'json',
                success: function (data) {
                    $("#btn_active").css('pointer-events', 'auto');
                    if (data.error)
                    {
                        alert(data.msg);
                    }
                    else
                    {
                        location.reload();
                    }
                    
                },
                error: function () {
                }
            });
        }
	}
</script>
@endpush