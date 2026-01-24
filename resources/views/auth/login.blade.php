@extends('layouts.app',[
        'class' => 'bg-default',
        'parentSection' => 'gamebank',
        'elementName' => 'Sign in'
    ])
@section('content')
    @include('layouts.headers.guest')
    <div class="container mt--8 pb-5">
        <div class="row justify-content-center">
        <div class="night"></div>
            <div class="col-xl-4 col-lg-5 col-md-7">
                <div class="card bg-gray-800 shadow border-0">
                    <div class="card-body px-lg-4 py-lg-4">
                        <div class="text-white h4 text-center mb-4">
                        <img src="{{ asset('back') }}/layout/{{config('app.logo')}}/logo.png" class="navbar-brand-img h-100" alt="main_logo" style="
        max-height: 40px !important; margin: auto;">
                            <span>{{config('app.title')}}{{ __('Admin Page') }}</span>
                        </div>
                        <form role="form" method="POST" action="{{ argon_route('auth.login.post') }}">
                            @csrf
                            @if ($errors->count()>0)
                                <span class="text-danger">
                                @foreach($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                                </span>
                            @endif
                            <div class="form-group{{ $errors->has('username') ? ' has-danger' : '' }} mb-3">
                                <div class="input-group input-group-alternative">
                                    <span class="input-group-text"><i class="ni ni-email-83"></i></span>
                                    <input class="form-control{{ $errors->has('username') ? ' is-invalid' : '' }}" placeholder="{{ __('ID') }}" type="text" name="username" value="{{ old('username') }}" value="" required >                                    
                                </div>
                                @if ($errors->has('username'))
                                    <span class="invalid-feedback" style="display: block;" role="alert">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ $errors->has('password') ? ' has-danger' : '' }}">
                                <div class="input-group input-group-alternative">
                                    <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                    <input class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" placeholder="{{ __('Password') }}" type="password" value="secret" required>
                                    @error('password')
                                    <label class="text-danger">{{ $message }}</label>
                                    @enderror
                                </div>
                            </div>
                            
                            {{--
                            @if(config('app.logo') != 'poseidon02')
                            <div class="form-group{{ $errors->has('captcha') ? ' has-danger' : '' }}">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="row align-items-center">
                                            <div class="col">
                                                <input type="text" class="form-control mr-0" placeholder="{{ __('Capture') }}" name="captcha">                                           
                                            </div>
                                            <div class="text-end px-0" style="width:173px">
                                                <div class="float-start captcha">{!! captcha_img('math') !!}</div>
                                                <div class="float-end">
                                                    <a class="nav-link text-white h5 font-weight-bold d-inline reload mx-0" id="reload" href="#">
                                                        &#x21bb;
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            --}}
                            <div class="text-center">
                                <button type="submit" class="btn btn-lg btn-primary btn-lg w-100 mt-4 mb-0">{{ __('Sign in') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row mt-3">
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script type="text/javascript">
    const night = document.querySelector('.night');

    function createShootingStar() {
        const star = document.createElement('div');
        star.classList.add('shooting_star');

        // 랜덤 위치 (화면 범위 내)
        const topPos = Math.random() * window.innerHeight;
        const leftPos = Math.random() * window.innerWidth;
        star.style.top = `${topPos}px`;
        star.style.left = `${leftPos}px`;

        // 랜덤 애니메이션 속도
        const duration = 2 + Math.random() * 2; // 2~4초
        star.style.animationDuration = `${duration}s, ${duration}s`;

        // 랜덤 지연시간
        const delay = Math.random() * 3; // 0~5초
        star.style.animationDelay = `${delay}s, ${delay}s`;

        night.appendChild(star);

        // 애니메이션 끝나면 제거
        setTimeout(() => {
        star.remove();
        }, (duration + delay) * 500);
    }

    // 0.5~1.5초마다 새로운 별똥별 생성
    setInterval(() => {
        createShootingStar();
    }, 100 + Math.random() * 300);

    $('#reload').click(function(){
        $.ajax({
            type:'GET',
            url:'reload-captcha',
            success:function(data){
                $(".captcha").html(data.captcha)
            }
        });
    });
</script>
@endsection


