@extends('layouts.app',[
        'parentSection' => 'gacbetlimit',
        'elementName' => 'GAC BetLimit'
    ])
@section('content')
<div class="container-fluid py-3">
    <?php 
        $statuses = \App\Support\Enum\UserStatus::lists(); 
        $status_class = \App\Support\Enum\UserStatus::bgclass(); 
        $badge_class = \App\Models\User::badgeclass();
    ?>
    
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">{{__('GAC BetLimit')}}</p>
                        </div>
                    </div>
                </div>
                @foreach ($betLimits as $tbllimit)
                <div class="row mt-4">
                    <div class="col">     
                        <div>
                            <p class="text-dark h5">설정 [{{implode(',', $tbllimit['tableName'])}}]</p>
                        </div>
                        <form action="" method="POST" >
                            @csrf
                            <input type="hidden" id="tableid" name="tableid" value="{{implode(',', $tbllimit['tableIds'])}}">               
                            <div class="table-responsive">
                                <table class="table table-bordered align-items-center text-center table-flush  mb-0" id="patnertlist">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="w-20">항목</th>
                                            <th scope="col" class="w-30">값</th>
                                            <th scope="col" class="w-20">항목</th>
                                            <th scope="col" class="w-30">값</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($tbllimit['BetLimit'] as $k=>$v)
                                            @if(($loop->index % 2)==0)
                                            <tr>
                                            @endif
                                                <td>{{$k}}</td>
                                                <td>
                                                    <input class="form-control" type="text" style="min-width:150px" value="{{$v}}" id="{{$k}}" name="{{$k}}">
                                                </td>
                                            @if(($loop->index % 2)==1)
                                            </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div> 
                            <div class="form-group row mt-3 justify-content-center">
                                <button type="submit" class="col-md-2 col-4 btn btn-primary">설정</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@stop
@push('js')
<script>
    
</script>
@endpush
