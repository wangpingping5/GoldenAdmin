@extends('layouts.app',[
        'parentSection' => 'gamecall',
        'elementName' => 'Game Call'
    ])
@section('content')
<div class="container-fluid py-3">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card p-4 text-center">
                <p class="mb-0 text-dark font-weight-bold">
                    <span class="text-primary">{{Request::get('join')[0]??date('Y-m-d 00:00')}} ~ {{Request::get('join')[1]??date('Y-m-d H:i')}}</span>: 까지의</br> 
                    콜금액 : <span class="text-success">{{number_format($total['totalbank'])}}</span>원&nbsp;
                    남은콜금액 : <span class="text-info">{{number_format($total['currentbank'])}}</span>원&nbsp;
                    오버된콜금액 : <span class="text-danger">{{number_format($total['overbank'])}}</span>원
                </p>                 
            </div>
        </div>
    </div>
    <?php 
        $statuses = \App\Support\Enum\UserStatus::lists(); 
        $status_class = \App\Support\Enum\UserStatus::bgclass(); 
        $badge_class = \App\Models\User::badgeclass();
    ?>
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row shadow bg-light p-1 rounded-1">
                    <form id="searchfrm" action="" method="GET" >
                        <div class="row">                         
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="datetime-local" value="{{Request::get('join')[0]??date('Y-m-d\T00:00')}}" id="join" name="join[]">
                            </div>  
                            <div class="col-lg-2 col-md-3 col-6 mt-2 px-1">
                                <input class="form-control" type="datetime-local" value="{{Request::get('join')[1]??date('Y-m-d\TH:i')}}" id="join" name="join[]">
                            </div> 
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="kind" name="kind">
                                        <option value="" @if (Request::get('kind') == '') selected @endif>아이디</option>
                                        <option value="1" @if (Request::get('kind') == 1) selected @endif>작성자</option>
                                        <option value="2" @if (Request::get('kind') == 2) selected @endif>콜금액</option>
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div>                            
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="status" name="status">
                                        <option value="" @if (Request::get('status') == '') selected @endif>--전체--</option>
                                        <option value="1" @if (Request::get('status') == 1) selected @endif>활성</option>
                                        <option value="2" @if (Request::get('status') == 2) selected @endif>완료</option>
                                    </select>
                                    <span class="input-group-text"><i class="ni ni-bold-down"></i></span>
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-2 col-4 mt-2 px-1">
                                <div class="input-group input-group-alternative">
                                    <select class="form-control" id="order" name="order">
                                        <option value="" @if (Request::get('order') == '') selected @endif>오름순</option>
                                        <option value="1" @if (Request::get('order') == 1) selected @endif>내림순</option>
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
                                        <button type="submit" class="form-control btn btn-primary">검색하기</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="row mt-4">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">유저 콜 목록</p>
                        </div>
                        <div class="float-end">
                            <div class="float-end px-1">
                                <a  href="javascript:;" onClick="window.open('{{ argon_route('game.call.create') }}', '','width=600, height=400, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');" class="btn btn-primary btn-sm">콜 추가</a>
                            </div>  
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">                    
                        <div class="table-responsive">
                            <table class="table table-bordered align-items-center text-center table-flush  mb-0" id="datalist">
                                <thead>
                                    <tr>
                                        <th scope="col">번호</th>
                                        <th scope="col">작성자</th>
                                        <th scope="col">유저아이디</th>
                                        <th scope="col">콜 금액</th>
                                        <th scope="col">남은콜금액</th>
                                        <th scope="col">오버된콜금액</th>
                                        <th scope="col">작성시간</th>
                                        <th scope="col">완료시간</th>
                                        <th scope="col">상태</th>
                                        <th scope="col">비고</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($happyhours) > 0)
                                        @foreach ($happyhours as $happyhour)
                                            <tr>
                                                @include('game.partials.row_call')
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr><td colspan="10">{{__('No Data')}}</td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">{{ $happyhours->withQueryString()->links('vendor.pagination.argon') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
@push('js')
@endpush
