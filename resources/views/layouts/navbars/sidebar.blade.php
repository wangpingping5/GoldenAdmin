<aside class="sidenav bg-gray-900 navbar navbar-vertical navbar-expand-xs border-1 fixed-start overflow-hidden" id="sidenav-main" data-color="primary">
    <div class="sidenav-header d-flex align-items-center w-100">
        <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand m-0 text-center w-100" href="{{argon_route('dashboard')}}">
            <img src="{{ asset('back') }}/layout/{{config('app.logo')}}/logo.png" class="navbar-brand-img h-100" alt="main_logo" style="
    max-height: 60px !important;">
        </a>
    </div>
    <hr class="horizontal dark mt-0">
    <div class="collapse navbar-collapse w-auto h-auto h-100 position-relative" id="sidenav-collapse-main" style="height: calc(100% - 6rem) !important">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link  {{ (isset($parentSection) && $parentSection == 'dashboard') ? 'active' : '' }}" href="{{ argon_route('dashboard') }}">
                    <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                        <i class="ni ni-shop text-primary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">{{ __('Dashboard')}}</span>
                </a>
            </li>
            <li class="nav-item">
                <h0 class="nav-link text-uppercase text-xxs p-0 px-2 pt-2 font-weight-bolder opacity-6">BANK</h6>
            </li>
            <li class="nav-item">
                <a data-bs-toggle="collapse" href="#navmoney" class="nav-link {{ (isset($parentSection) && ($parentSection == 'charge' || $parentSection == 'exchange' || $parentSection == 'dwhistory' || $parentSection == 'convertdeal')) ? 'active' : '' }}"
                    aria-controls="navmoney" role="button" aria-expanded="false">
                    <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                        <i class="ni ni-credit-card text-primary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">{{__('DWManagment')}}</span>
                </a>
                <div class="collapse {{ (isset($parentSection) && ($parentSection == 'charge' || $parentSection == 'exchange' || $parentSection == 'dwhistory' || $parentSection == 'convertdeal')) ? 'show' : '' }}" id="navmoney">
                    <ul class="nav ms-4">
                        @if (auth()->user()->isInoutPartner())
                            <li class="nav-item ">
                                <a class="nav-link {{ (isset($parentSection) && $parentSection == 'charge') ? 'active' : '' }}" href="{{argon_route('ec.manage', ['type' => 'add'])}}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('Charge Manage')}}</span>
                                </a>
                            </li>
                            <li class="nav-item ">
                                <a class="nav-link  {{ (isset($parentSection) && $parentSection == 'exchange') ? 'active' : '' }}" href="{{argon_route('ec.manage', ['type' => 'out'])}}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('Exchange Manage')}}</span>
                                </a>
                            </li>
                        @else
                            <li class="nav-item ">
                                <a class="nav-link {{ (isset($parentSection) && $parentSection == 'convertdeal') ? 'active' : '' }}" href="{{argon_route('comp.dealconvert')}}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('deal_out')}}</span>
                                </a>
                            </li>
                            <li class="nav-item ">
                                <a class="nav-link {{ (isset($parentSection) && $parentSection == 'charge') ? 'active' : '' }}" href="{{argon_route('charge.request')}}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('Charge Request')}}</span>
                                </a>
                            </li>
                            <li class="nav-item {{ (isset($parentSection) && $parentSection == 'exchange') ? 'active' : '' }}">
                                <a class="nav-link " href="{{argon_route('exchang.request')}}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('Exchange Request')}}</span>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item {{ (isset($parentSection) && $parentSection == 'dwhistory') ? 'active' : '' }}">
                            <a class="nav-link " href="{{argon_route('ec.history')}}">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal"> {{__('DW History')}}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <h6 class="nav-link text-uppercase text-xxs p-0 px-2 pt-2 font-weight-bolder opacity-6">USER</h6>
            </li>
            <li class="nav-item">
                <a data-bs-toggle="collapse" class="nav-link {{ (isset($parentSection) && ($parentSection == 'playerlist' || $parentSection == 'noconnectlist')) ? 'active' : '' }}" href="#navplayers" role="button" aria-expanded="false"
                    aria-controls="navplayers">
                    <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                        <i class="ni ni-user-run text-primary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">{{ __('Player Manager')}}</span>
                </a>
                <div class="collapse {{ (isset($parentSection) && ($parentSection == 'playerlist' || $parentSection == 'noconnectlist')) ? 'show' : '' }}" id="navplayers">
                    <ul class="nav ms-4">
                        <li class="nav-item ">
                            <a class="nav-link {{ (isset($parentSection) && $parentSection == 'playerlist') ? 'active' : '' }}" href="{{argon_route('player.list')}}">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal"> {{ __('Player List')}}</span>
                            </a>
                        </li>
                        <li class="nav-item ">
                            <a class="nav-link {{ (isset($parentSection) && $parentSection == 'noconnectlist') ? 'active' : '' }}" href="{{argon_route('player.noconnect')}}">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal"> {{ __('No Connect List')}}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ (isset($parentSection) && ($parentSection == 'playerconnectedlist')) ? 'active' : '' }}" href="{{argon_route('player.connected')}}">
                    <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                        <i class="fa fa-user-plus text-primary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">{{ __('Player Connected List')}}</span>
                </a>
            </li>
            @if (!auth()->user()->hasRole('manager'))
                <li class="nav-item">
                    <a class="nav-link {{ (isset($parentSection) && strpos($parentSection, 'patnerlist') !== false) ? 'active' : '' }}" href="#navpartners" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="navpartners">
                        <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                            <i class="ni ni-single-02 text-primary text-sm opacity-10"></i>
                        </div>
                        <span class="nav-link-text ms-1">{{__('AgentManagment')}}</span>
                    </a>
                    <div class="collapse {{ (isset($parentSection) && strpos($parentSection, 'patnerlist') !== false) ? 'show' : '' }}" id="navpartners">
                        <ul class="nav ms-4">
                            <li class="nav-item ">
                                <a class="nav-link {{$parentSection == 'patnerlist' ? 'active' : ''}}" href="{{argon_route('patner.list')}}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('AgentList')}}</span>
                                </a>
                            </li>
                            @for ($role = auth()->user()->role_id - 1; $role >= 3; $role--)
                                <li class="nav-item {{$parentSection == 'patnerlist' . $role ? 'active' : ''}}">
                                    <a class="nav-link " href="{{argon_route('patner.eachlist', ['role' => $role])}}">
                                        <span class="sidenav-mini-icon"> L </span>
                                        <span class="sidenav-normal"> {{ __(\App\Models\Role::where('level', $role)->first()->name)}}</span>
                                    </a>
                                </li>
                            @endfor
                        </ul>
                    </div>
                </li>
            @endif
            @if (auth()->user()->isInoutPartner())
                <li class="nav-item">
                    <h6 class="nav-link text-uppercase text-xxs p-0 px-2 pt-2 font-weight-bolder opacity-6">GAME</h6>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ (isset($parentSection) && $parentSection == 'gamedomain') ? 'active' : '' }}" href="{{argon_route('game.domain')}}">
                        <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                            <i class="ni ni-world text-primary text-sm opacity-10"></i>
                        </div>
                        <span class="nav-link-text ms-1">{{__('Game Domain')}}</span>
                    </a>
                </li>
                @if (auth()->user()->hasRole('admin'))
                    <li class="nav-item">
                        <a class="nav-link {{ (isset($parentSection) && $parentSection == 'gamepatner') ? 'active' : '' }}" href="{{argon_route('game.patner')}}">
                            <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                                <i class="ni ni-world-2 text-primary text-sm opacity-10"></i>
                            </div>
                            <span class="nav-link-text ms-1">{{__('Game Partner')}}</span>
                        </a>
                    </li>
                @endif
            @endif
            <li class="nav-item">
                <h6 class="nav-link text-uppercase text-xxs p-0 px-2 pt-2 font-weight-bolder opacity-6">REPORT</h6>
            </li>
            <li class="nav-item">
                <a data-bs-toggle="collapse" href="#navreport" class="nav-link {{ (isset($parentSection) && ($parentSection == 'reportdaily' || $parentSection == 'reportgame' || $parentSection == 'sharedaily' || $parentSection == 'reportuser')) ? 'active' : '' }}"
                    aria-controls="navreport" role="button" aria-expanded="false">
                    <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                        <i class="ni ni-badge text-primary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">{{__('SettlementManagment')}}</span>
                </a>
                <div class="collapse  {{ (isset($parentSection) && ($parentSection == 'reportdaily' || $parentSection == 'reportgame' || $parentSection == 'sharedaily' || $parentSection == 'reportuser')) ? 'show' : '' }}" id="navreport">
                    <ul class="nav ms-4">
                        <li class="nav-item ">
                            <a class="nav-link {{ (isset($parentSection) && $parentSection == 'reportdaily') ? 'active' : '' }}" href="{{argon_route('report.daily')}}">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal"> {{__('DailySettlement')}}</span>
                            </a>
                        </li>
                        <li class="nav-item {{ (isset($parentSection) && $parentSection == 'reportgame') ? 'active' : '' }}">
                            <a class="nav-link " href="{{argon_route('report.game')}}">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal"> {{__('GameSettlement')}}</span>
                            </a>
                        </li>
                        {{--
                        @if (auth()->user()->isInoutPartner())
                            <li class="nav-item {{ (isset($parentSection) && $parentSection == 'sharedaily') ? 'active' : '' }}">
                                <a class="nav-link " href="{{argon_route('share.report.daily')}}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('ShareDailySettlement')}}</span>
                                </a>
                            </li>
                        @endif
                        --}}
                        <li class="nav-item {{ (isset($parentSection) && $parentSection == 'reportuser') ? 'active' : '' }}">
                            <a class="nav-link " href="{{argon_route('report.user')}}">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal"> {{__('UserSettlement')}}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <h6 class="nav-link text-uppercase text-xxs p-0 px-2 pt-2 font-weight-bolder opacity-6">History</h6>
            </li>
            <li class="nav-item">
                <a data-bs-toggle="collapse" href="#navlog" class="nav-link {{ (isset($parentSection) && strpos($parentSection, 'loglist') !== false) ? 'active' : '' }}" aria-controls="navlog" role="button" aria-expanded="false">
                    <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                        <i class="ni ni-ui-04 text-primary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">{{__('History')}}</span>
                </a>
                <div class="collapse {{ (isset($parentSection) && strpos($parentSection, 'loglist') !== false) ? 'show' : '' }}" id="navlog">
                    <ul class="nav ms-4">
                        <li class="nav-item ">
                            <a class="nav-link {{ (isset($parentSection) && ($parentSection == 'loglist1')) ? 'active' : '' }}" href="{{ argon_route('partner.transaction') }}">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal"> {{__('AgentPayHistory')}}</span>
                            </a>
                        </li>
                        <li class="nav-item ">
                            <a class="nav-link {{ (isset($parentSection) && ($parentSection == 'loglist2')) ? 'active' : '' }}" href="{{ argon_route('partner.dealstat') }}">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal"> {{__('AgentRollingHistory')}}</span>
                            </a>
                        </li>
                        <li class="nav-item ">
                            <a class="nav-link {{ (isset($parentSection) && ($parentSection == 'loglist3')) ? 'active' : '' }}" href="{{ argon_route('player.transaction') }}">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal"> {{__('UserPayHistory')}}</span>
                            </a>
                        </li>
                        {{--
                        @if (auth()->user()->isInOutPartner())
                            <li class="nav-item ">
                                <a class="nav-link {{ (isset($parentSection) && ($parentSection == 'loglist4')) ? 'active' : '' }}" href="{{ argon_route('player.gamepending') }}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> 미결중 게임내역</span>
                                </a>
                            </li>
                        @endif
                        --}}
                        <li class="nav-item ">
                            <a class="nav-link {{ (isset($parentSection) && ($parentSection == 'loglist5')) ? 'active' : '' }}" href="{{argon_route('player.gamehistory')}}">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal"> {{__('UserBetHistory')}}</span>
                            </a>
                        </li>
                        {{--
                        @if (auth()->user()->isInoutPartner())
                            <li class="nav-item ">
                                <a class="nav-link {{ (isset($parentSection) && ($parentSection == 'loglist6')) ? 'active' : '' }}" href="{{argon_route('share.gamestat')}}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('ShareGameHistory')}}</span>
                                </a>
                            </li>
                        @endif
                        --}}
                        @if (auth()->user()->hasRole('admin'))
                            <li class="nav-item ">
                                <a class="nav-link {{ (isset($parentSection) && ($parentSection == 'loglist7')) ? 'active' : '' }}" href="{{argon_route('game.transaction')}}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('SlotBankHistory')}}</span>
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->isInoutPartner())
                            <li class="nav-item ">
                                <a class="nav-link {{ (isset($parentSection) && ($parentSection == 'loglist8')) ? 'active' : '' }}" href="{{argon_route('activity.index')}}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal">  {{__('IPAccessLog')}}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <h6 class="nav-link text-uppercase text-xxs p-0 px-2 pt-2 font-weight-bolder opacity-6">BOARD</h6>
            </li>

            @if (auth()->user()->isInoutPartner())
            <li class="nav-item">
                <a data-bs-toggle="collapse" href="#navnotices" class="nav-link {{ (isset($parentSection) && ($parentSection == 'notices' || $parentSection == 'notelist' || $parentSection == 'noteconfirm' || $parentSection == 'messagesend' || $parentSection == 'msgtemp')) ? 'active' : '' }}"
                    aria-controls="navnotices" role="button" aria-expanded="false">
                    <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                    <i class="ni ni-chat-round text-primary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">{{__('Notices')}}</span>
                </a>
            @else
            <li class="nav-item">
                <a data-bs-toggle="collapse" href="#navnotices" class="nav-link {{ (isset($parentSection) && ($parentSection == 'notices' || $parentSection == 'notelist' || $parentSection == 'noteconfirm' || $parentSection == 'messagelist' || $parentSection == 'messagesend' || $parentSection == 'msgtemp')) ? 'active' : '' }}"
                    aria-controls="navnotices" role="button" aria-expanded="false">
                    <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                    <i class="ni ni-chat-round text-primary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">{{__('Message')}}</span>
                </a>
            @endif
                <div class="collapse  {{ (isset($parentSection) && ($parentSection == 'notices' || $parentSection == 'notelist' || $parentSection == 'noteconfirm' || $parentSection == 'messagelist' || $parentSection == 'messagesend' || $parentSection == 'msgtemp')) ? 'show' : '' }}" id="navnotices">
                    <ul class="nav ms-4">
                        @if (auth()->user()->isInoutPartner())
                            <li class="nav-item">
                                <a class="nav-link {{ (isset($parentSection) && ($parentSection == 'notices')) ? 'active' : '' }}" href="{{ argon_route('notices.list') }}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('Notices')}}</span>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link {{ (isset($parentSection) && ($parentSection == 'notelist' || $parentSection == 'noteconfirm')) ? 'active' : '' }}" href="{{ argon_route('msg.list', ['type' => 0])}}">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal"> {{__('Message-list')}}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ (isset($parentSection) && ($parentSection == 'messagelist' || $parentSection == 'messagesend')) ? 'active' : '' }}" href="{{ argon_route('msg.list', ['type' => 1])}}">
                                <span class="sidenav-mini-icon"> L </span>
                                <span class="sidenav-normal"> {{__('cs')}}</span>
                            </a>
                        </li>
                        @if (auth()->user()->isInoutPartner())
                            <li class="nav-item">
                                <a class="nav-link {{ (isset($parentSection) && $parentSection == 'msgtemp') ? 'active' : '' }}" href="{{ argon_route('msgtemp.list') }}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('MsgTemp')}}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </li>
            @if (auth()->user()->isInoutPartner())
                <li class="nav-item">
                    <h6 class="nav-link text-uppercase text-xxs p-0 px-2 pt-2 font-weight-bolder opacity-6">SETTING</h6>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#navsettings" class="nav-link {{ (isset($parentSection) && ($parentSection == 'websitelist' || $parentSection == 'ipblocklist' || $parentSection == 'alramset' || $parentSection == 'statistics' || $parentSection == 'soundset')) ? 'active' : '' }}"
                        aria-controls="navnotices" role="button" aria-expanded="false">
                        <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                            <i class="ni ni-settings-gear-65 text-primary text-sm opacity-10"></i>
                        </div>
                        <span class="nav-link-text ms-1">{{__('Setting')}}</span>
                    </a>
                    <div class="collapse  {{ (isset($parentSection) && ($parentSection == 'websitelist' || $parentSection == 'ipblocklist' || $parentSection == 'alramset' || $parentSection == 'statistics' || $parentSection == 'soundset')) ? 'show' : '' }}" id="navsettings">
                        <ul class="nav ms-4">
                            <li class="nav-item">
                                <a class="nav-link {{ (isset($parentSection) && $parentSection == 'websitelist') ? 'active' : '' }}" href="{{ argon_route('websites.list') }}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('WebsiteList')}}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ (isset($parentSection) && $parentSection == 'ipblocklist') ? 'active' : '' }}" href="{{ argon_route('ipblock.list') }}">
                                    <span class="sidenav-mini-icon"> L </span>
                                    <span class="sidenav-normal"> {{__('Ipblocks')}}</span>
                                </a>
                            </li>
                            @if (auth()->user()->hasRole('admin'))
                                <li class="nav-item">
                                    <a class="nav-link {{ (isset($parentSection) && $parentSection == 'alramset') ? 'active' : '' }}" href="{{ argon_route('system.alramset') }}">
                                        <span class="sidenav-mini-icon"> L </span>
                                        <span class="sidenav-normal"> {{__('Alarm Set')}}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ (isset($parentSection) && $parentSection == 'statistics') ? 'active' : '' }}" href="{{ argon_route('system.statistics') }}">
                                        <span class="sidenav-mini-icon"> L </span>
                                        <span class="sidenav-normal"> {{__('Statistics')}}</span>
                                    </a>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a class="nav-link {{ (isset($parentSection) && $parentSection == 'soundset') ? 'active' : '' }}" href="{{ argon_route('system.soundset') }}">
                                        <span class="sidenav-mini-icon"> L </span>
                                        <span class="sidenav-normal">{{__('Sound Set')}}</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </li>
                {{--
                    <li class="nav-item">
                        <a class="nav-link {{ (isset($parentSection) && $parentSection == 'websitelist') ? 'active' : '' }}" href="{{ argon_route('websites.list') }}">
                            <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                                <i class="ni ni-trophy text-primary text-sm opacity-10"></i>
                            </div>
                            <span class="nav-link-text ms-1">{{__('WebsiteList')}}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ (isset($parentSection) && $parentSection == 'ipblocklist') ? 'active' : '' }}" href="{{ argon_route('ipblock.list') }}">
                            <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                                <i class="ni ni-lock-circle-open text-primary text-sm opacity-10"></i>
                            </div>
                            <span class="nav-link-text ms-1">{{__('Ipblocks')}}</span>
                        </a>
                    </li>
                    @if (auth()->user()->hasRole('admin'))
                        <li class="nav-item">
                            <a class="nav-link {{ (isset($parentSection) && $parentSection == 'alramset') ? 'active' : '' }}" href="{{ argon_route('system.alramset') }}">
                                <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                                    <i class="ni ni-time-alarm text-primary text-sm opacity-10"></i>
                                </div>
                                <span class="nav-link-text ms-1">{{__('Alarm Set')}}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ (isset($parentSection) && $parentSection == 'statistics') ? 'active' : '' }}" href="{{ argon_route('system.statistics') }}">
                                <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                                    <i class="ni ni-settings-gear-65 text-primary text-sm opacity-10"></i>
                                </div>
                                <span class="nav-link-text ms-1">{{__('Statistics')}}</span>
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ (isset($parentSection) && $parentSection == 'soundset') ? 'active' : '' }}" href="{{ argon_route('system.soundset') }}">
                                <div class="icon icon-shape icon-sm text-center d-flex align-items-center justify-content-center">
                                    <i class="ni ni-bell-55 text-primary text-sm opacity-10"></i>
                                </div>
                                <span class="nav-link-text ms-1">{{__('Sound Set')}}</span>
                            </a>
                        </li>
                    @endif
                --}}
            @endif
        </ul>
    </div>
</aside>