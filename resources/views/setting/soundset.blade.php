@extends('layouts.app',[
        'parentSection' => 'soundset',
        'elementName' => 'Sound Set'
    ])
@section('content')
<div class="container-fluid py-3">
    <?php 
        $badge_class = \App\Models\User::badgeclass();
    ?>
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row">
                    <div class="col-md-12 col-lg-12">
                        <div class="row">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="chkSound" onclick="soundset()" {{auth()->user()->rating>0 ? 'checked' : ''}}>
                                <label class="form-check-label text-dark h5" for="chkSound">Sound ON/OFF</label>
                            </div>
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
    function soundset(rating)
    {   
        var checked = $("#chkSound").is(':checked');
        var rating = 0;
        if(checked == true){
            rating = 1;
        }
        $.ajax({
					url: "{{argon_route('common.inoutlist')}}",
					type: "GET",
					data: {'rating': rating },
					dataType: 'json',
					success: function (data) {
                        
                    }
				});
    }
</script>
@endpush
