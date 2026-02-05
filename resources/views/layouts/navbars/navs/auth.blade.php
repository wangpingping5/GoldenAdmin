<nav class="navbar navbar-main navbar-expand-lg  px-0 mx-0 shadow-none z-index-sticky bg-warning position-sticky left-auto" id="navbarBlur" data-scroll="false">
      <div class="container-fluid py-1 px-4">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
            {{--
            <li class="breadcrumb-item text-sm">
              <a class="text-white" href="javascript:;">
                <i class="ni ni-box-2"></i>
              </a>
            </li>
            --}}
            <li class="breadcrumb-item font-weight-bold text-white active" aria-current="page">{{ __($elementName)}}</li>
          </ol>
        </nav>
        <div class="sidenav-toggler sidenav-toggler-inner d-xl-block d-none ">
          <a href="javascript:;" class="nav-link p-0">
            <div class="sidenav-toggler-inner">
              <i class="sidenav-toggler-line bg-light"></i>
              <i class="sidenav-toggler-line bg-light"></i>
              <i class="sidenav-toggler-line bg-light"></i>
            </div>
          </a>
        </div>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
        <ul class="navbar-nav align-items-center px-3">                
              @if (!auth()->user()->hasRole('admin'))                
                @if (auth()->user()->isInoutPartner())
                  <li class="nav-item d-none d-lg-block ml-lg-4">
                      <div class="nav-item-btn align-items-center">
                        <a class="nav-link text-light font-weight-bold" href="{{argon_route('player.list')}}">
                            {{ __('NewJoin')}} : <span id="join_newmark" class="badge bg-primary">(0)</span>
                        </a>
                      </div>
                  </li>
                  <li class="nav-item d-none d-lg-block ml-lg-4">
                      <div class="nav-item-btn align-items-center">
                      <a class="nav-link text-light font-weight-bold" href="{{argon_route('ec.manage', ['type'=>'add'])}}">
                            {{ __('Charge Request')}} : <span id="in_newmark" class="badge bg-success">(0)</span>
                      </a>
                      </div>
                  </li>
                  <li class="nav-item d-none d-lg-block ml-lg-4">
                      <div class="nav-item-btn align-items-center">
                        <a class="nav-link text-light font-weight-bold" href="{{argon_route('ec.manage', ['type'=>'out'])}}">
                            {{ __('Exchange Request')}} : <span id="out_newmark" class="badge bg-danger">(0)</span>
                        </a>
                      </div>
                  </li>
                @else
                  <?php
                      $master = auth()->user()->referral;
                      while ($master!=null && !$master->isInoutPartner())
                      {
                          $master = $master->referral;
                      }
                  ?>
                  <li class="nav-item d-none d-lg-block ml-lg-4">
                      <div class="nav-item-btn align-items-center">
                          <a class="nav-link text-light font-weight-bold" href="javascript:;">
                          {{__('RequestTelegram')}} : <span class="text-info">{{$master->address}}</span>
                          </a>
                      </div>
                  </li>
                  <li class="nav-item d-none d-lg-block ml-lg-4">
                      <div class="nav-item-btn align-items-center">
                        <a class="nav-link text-light font-weight-bold" href="{{argon_route('charge.request')}}">
                            <span class="btn btn-success px-2 py-1 m-0 rounded-0">{{__('Charge Request')}}</span>
                        </a>
                      </div>
                  </li>
                  <li class="nav-item d-none d-lg-block ml-lg-4">
                      <div class="nav-item-btn align-items-center">
                        <a class="nav-link text-light font-weight-bold" href="{{argon_route('exchang.request')}}">
                            <span class="btn btn-danger px-2 py-1 m-0 rounded-0">{{__('Exchange Request')}}</span>
                        </a>
                      </div>
                  </li>
                @endif
                  <li class="nav-item d-none d-lg-block ml-lg-4">
                      <div class="nav-item-btn align-items-center">
                        <a class="nav-link text-light font-weight-bold" href="{{argon_route('msg.list', ['type'=>$msgtype])}}">
                        {{__('Notices')}} : <span id="unreadmsgcount" class="badge bg-info">({{count($unreadmsgs)}})</span>
                        </a>
                      </div>
                  </li>
              @endif
          </ul>
          <ul class="navbar-nav align-items-center ms-md-auto">
              <li class="nav-item d-none d-lg-block ml-lg-4">
                  <a class="nav-link text-light font-weight-bold" href="#">
                      {{__('Balance')}} : 
                      <span class="badge badge-pill badge-md bg-gradient-success">
                      @if (auth()->user()->hasRole('manager'))
                          {{number_format(auth()->user()->shop->balance, 2)}}
                      @else
                          {{number_format(auth()->user()->balance, 2)}}
                      @endif
                      </span>
                  </a>
              </li>
              @if (auth()->user()->role_id > 3)
              <li class="nav-item d-none d-lg-block ml-lg-4">
                  <a class="nav-link text-light font-weight-bold" href="#">
                      {{__('Agent Balance')}} : <span class="badge badge-pill badge-md bg-gradient-info">{{number_format(auth()->user()->childPartnerBalanceSum(), 2)}}</span>
                  </a>
              </li>
              @endif
              <li class="nav-item d-none d-lg-block ml-lg-4">
                  <a class="nav-link text-light font-weight-bold" href="#">
                      {{__('User Balance')}} :  <span class="badge badge-pill badge-md bg-gradient-info">{{number_format(auth()->user()->childBalanceSum() - auth()->user()->childPartnerBalanceSum(), 2)}}</span>
                  </a>
              </li>
              <li class="nav-item d-none d-lg-block ml-lg-4">
                  <a class="nav-link text-light font-weight-bold" href="#">
                      {{__('Rolling')}} : 
                      <span class="badge badge-pill badge-md bg-gradient-danger">
                      @if (auth()->user()->isInoutPartner())
                      {{ number_format(auth()->user()->childDealSum(), 2) }}
                      @elseif (auth()->user()->hasRole('manager'))
                      {{ number_format(auth()->user()->shop->deal_balance - auth()->user()->shop->mileage, 2) }}
                      @else
                      {{ number_format(auth()->user()->deal_balance - auth()->user()->mileage, 2) }}
                      @endif
                      </span>
                  </a>
              </li>
          </ul>
          
          <ul class="navbar-nav  justify-content-end ms-2">
            <li class="nav-item px-2 d-flex align-items-center">
              <a href="{{argon_route('auth.logout', ['force' => 1])}}" class="text-light font-weight-bold" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{__('Logout')}}">
                <i class="fa fa-sign-out me-sm-1"></i>
              </a>
            </li>
            <li class="nav-item d-xl-none px-2 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-white p-0" id="iconNavbarSidenav">
                <div class="sidenav-toggler-inner">
                  <i class="sidenav-toggler-line bg-light"></i>
                  <i class="sidenav-toggler-line bg-light"></i>
                  <i class="sidenav-toggler-line bg-light"></i>
                </div>
              </a>
            </li>
            <li class="nav-item px-2 d-flex align-items-center">
              <a href="{{argon_route('common.profile', ['id'=>auth()->user()->id])}}" class="nav-link text-white p-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{__('Setting')}}">
                <i class="fa fa-user-circle  cursor-pointer"></i>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>