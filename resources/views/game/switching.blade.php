@extends('layouts.app',[
        'parentSection' => 'switching',
        'elementName' => 'Evolution Switching'
    ])
@section('content')
<div class="container-fluid py-3">
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row mt-3">
                <div class="col">
                <form action="{{route('game.switching.update')}}" method="POST">
                    @csrf
                    <div class="form-group row mt-3 justify-content-center">
                        <!-- 라디오 버튼 그룹 -->
                        <div class="col-md-6 text-center">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="version" id="salesVersion" value="sales" {{ $isProduction ? 'checked' : '' }}>
                                <label class="form-check-label" for="salesVersion">정품</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="version" id="testVersion" value="test" {{ !$isProduction ? 'checked' : '' }}>
                                <label class="form-check-label" for="testVersion">가품</label>
                            </div>
                        </div>
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
