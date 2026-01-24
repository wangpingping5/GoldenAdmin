@if(isset ($errors) && count($errors) > 0)
    <div class="alert alert-white text-danger mx-3">
        <p class="h4 text-danger">{{__('Error')}}</p>
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

@if(Session::get('success', false))
    <?php $data = Session::get('success'); ?>
    @if (is_array($data))
        @foreach ($data as $msg)
	        <div class="alert alert-white text-success mx-3">
                <p class="h4 text-success">{{__('Success')}}</p>
                <p>{{ $msg }}</p>
            </div>
        @endforeach
    @else
	        <div class="alert alert-white text-success mx-3">
            <p class="h4 text-success">{{__('Success')}}</p>
            <p>{{ $data }}</p>
            </div>
    @endif
@endif