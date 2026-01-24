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
                <div class="row mt-4">
                    <div class="col">     
                        <div>
                            <p class="text-dark h5">설정</p>
                        </div>
                        <form action="" method="POST" >
                            @csrf
                            <div class="table-responsive">
                                <table class="table table-bordered align-items-center text-center table-flush  mb-0" id="patnertlist">
                                    <thead>
                                        <tr>
                                            <th class="w-auto p-0"></th>
                                            <th scope="col" class="w-20">스킨</th>
                                            <th scope="col" class="w-80">배팅한도</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($betSkins as $betskin)
                                            <tr>
                                                <td>
                                                    @if($betskin->skin == $currentSkin)
                                                    <input type="radio" name="skin" value="{{$betskin->skin}}" checked>
                                                    @else
                                                    <input type="radio" name="skin" value="{{$betskin->skin}}">
                                                    @endif
                                                </td>
                                                <td>{{$betskin->skin}}</td>
                                                <td>{{number_format($betskin->min,0)}} - {{number_format($betskin->max,0)}}</td>
                                            </tr>
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
            </div>
        </div>
    </div>
</div>
@stop
@push('js')
<script>
    
</script>
@endpush
