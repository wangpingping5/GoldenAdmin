@extends('layouts.app',[
        'parentSection' => 'profile',
        'elementName' => 'Profile'
    ])
@section('content')
<div class="container-fluid py-3">
    <?php 
        $badge_class = \App\Models\User::badgeclass();
        $referral = $user->referral;
        $maxPercent = $user->maxPercent();
        $minPercent = $user->minPercent();
        $isSelf = $user->id==auth()->user()->id;
    ?>
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card p-4 text-center">
                @if (!$user->hasRole('admin'))
                <p class="mb-0 text-dark font-weight-bold">{{__('agent.ParentList')}} : 
                @for ($i = $user->role_id+1; $i < auth()->user()->role_id; $i++)
                    <span class="badge {{$badge_class[$referral->role_id]}}">[{{__($referral->role->name)}}] {{$referral->username}}</span>
                    <?php 
                        $referral = $referral->referral;
                    ?>
                @endfor
                </p>
                @endif
                <p class="mb-0 text-dark font-weight-bold">{{__('agent.RegisterDate')}} : <span class="text-info">{{date('Y-m-d H:i', strtotime($user->created_at))}}</span>@if (!$user->hasRole('user')) / {{__('agent.ChildUserCount')}} : <span class="text-success">{{count($user->hierarchyUsersOnly())}}</span> / {{__('agent.ChildTotalBalance')}} : <span class="text-warning">{{number_format($user->childBalanceSum(), 2)}}</span> @endif</p>
                <p class="mb-0 text-dark font-weight-bold">{{__('agent.TotalDeposit')}} : <span class="text-primary">{{number_format($user->total_in, 2)}}</span> / {{__('agent.TotalWithdraw')}} : <span class="text-primary">{{number_format($user->total_out, 2)}}</span> / {{__('agent.TotalDW')}} : <span class="text-primary">{{number_format($user->total_in - $user->total_out, 2)}}</span></p>
                @if ($user->hasRole('user'))
                <p class="mb-0 text-dark font-weight-bold">{{__('agent.Last30days')}} {{__('agent.BetAmount')}} : <span class="text-success">{{number_format($totalstatic->totalbet, 2)}}</span> / {{__('agent.WinAmount')}} : <span class="text-danger">{{number_format($totalstatic->totalwin, 2)}}</span></p>
                @endif
            </div>
        </div>
    </div>
    <?php 
        $statuses = \App\Support\Enum\UserStatus::lists(); 
        $status_class = \App\Support\Enum\UserStatus::bgclass(); 
    ?>
    <div class="row mb-4">
        <div class="col">
            <div class="card p-4">
                <div class="row mt-4">
                    <div class="col">
                        <div class="float-start ms-md-auto">
                            <p class="text-dark h5">[<span class="text-primary">{{$user->username}}</span>] {{__('agent.Profile')}}</p>
                        </div>
                        <div class="float-end">
                            @if (auth()->user()->role_id > $user->role_id)
                            @if (auth()->user()->hasRole('admin'))
                            <div class="float-end px-1">
                                <button  id="btn_delete" onClick="deleteUsers({{$user->id}}, 1);" class="rounded-0 btn btn-danger bnt-sm">{{__('agent.AdminDelete')}}</button>
                            </div>
                            @endif
                            @if ($deluser)
                            <div class="float-end px-1">
                                <button  id="btn_delete" onClick="deleteUsers({{$user->id}}, 0);" class="rounded-0 btn btn-danger bnt-sm">{{__('Deleted')}}</button>
                            </div>
                            @endif
                            <div class="float-end px-1">
                                <button id="btn_disable" onClick="activeUsers(1, {{$user->id}});" class="rounded-0 btn btn-warning bnt-sm">{{__('Banned')}}</button>
                            </div>  
                            <div class="float-end px-1">
                                <button id="btn_active" onClick="activeUsers(0, {{$user->id}});" class="rounded-0 btn btn-success bnt-sm">{{__('Active')}}</button>
                            </div>  
                            <div class="float-end px-1">
                                <button class="btn bg-info m-0 rounded-0 text-white dropdown-toggle" type="button" id="dropdownImport" data-bs-toggle="dropdown" aria-expanded="false">
                                {{__("agent.Manage")}}
                                </button>
                                <ul class="dropdown-menu bg-light border-2 border-light" aria-labelledby="dropdownImport" data-popper-placement="left-start">
                                    <li><a class="dropdown-item" href="javascript:;" onClick="window.open('{{ argon_route('common.balance', ['id'=> $user->id]) }}', '{{__("agent.DW")}}','width=600, height=520, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');"><span class="text-dark">{{__('agent.Balance')}} {{__('agent.DW')}}</span></a></li>
                                    <li><a class="dropdown-item" href="javascript:;" onClick="window.open('{{ argon_route('common.message', ['id'=> $user->id]) }}', '{{__("agent.SendMessage")}}','width=800, height=580, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');"><span class="text-dark">{{__("agent.SendMessage")}}</span></a></li>
                                    @if (auth()->user()->isInoutPartner() && !$user->isInoutPartner())
                                    <li><a class="dropdown-item" href="{{argon_route('comp.dealconvert', ['user'=>$user->id])}}"><span class="text-dark">{{__("agent.ConvertRolling")}}</span></a><li>  
                                    @endif
                                    @if ($user->hasRole('user'))
                                    <li><a class="dropdown-item" href="{{argon_route('player.gamehistory', ['userID'=>'player', 'search' => $user->username])}}"><span class="text-dark">{{__("agent.BetHistory")}}</span></a></li>
                                    <li><a class="dropdown-item" href="{{argon_route('player.transaction', ['userID'=>'user', 'search' => $user->username])}}"><span class="text-dark">{{__("agent.DepositHistory")}}</span></a></li>
                                    <li><a class="dropdown-item" href="{{argon_route('player.terminate', ['id' => $user->id])}}" data-method="DELETE"
                                                        data-confirm-title="{{__('agent.Confirm')}}"
                                                        data-confirm-text="{{__('agent.TerminalGameMsg')}}"
                                                        data-confirm-delete="{{__('agent.Confirm')}}"
                                                        data-confirm-cancel="{{__('agent.Cancel')}}"
                                                        onClick="$(this).css('pointer-events', 'none');"
                                                        ><span class="text-dark">{{__('agent.TerminalGame')}}</span></a></li>
                                    @else
                                    <li><a class="dropdown-item" href="{{argon_route('partner.dealstat', ['userID'=>'user', 'search' => $user->username])}}"><span class="text-dark">{{__("agent.RollingHistory")}}</span></a><li>  
                                    <li><a class="dropdown-item" href="{{argon_route('partner.transaction', ['userID'=>'admin', 'search' => $user->username])}}"><span class="text-dark">{{__("agent.DepositHistory")}}</span></a><li>                                     
                                    @if (auth()->user()->hasRole('admin'))
                                    <li><a class="dropdown-item" href="javascript:;"  onClick="window.open('{{ argon_route('patner.move', ['id'=> $user->id]) }}', '쪽지보내기','width=800, height=480, left=500, top=150, toolbar=no, menubar=no, scrollbars=no, resizable=yes');"><span class="text-dark">{{__("agent.PartnerMove")}}</span></a></li>
                                    @endif
                                    @endif
                                    <li><a class="dropdown-item" href="{{argon_route('player.logout', ['id' => $user->id])}}" onClick="$(this).css('pointer-events', 'none');"><span class="text-dark">{{__("agent.ForceLogout")}}</span></a></li>
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col border mb-2">                            
                        <form method="post" id="mainfrm" action="{{argon_route('common.profile.detail')}}" autocomplete="off">
                            @csrf
                            <input type="hidden" name="id" value="{{$user->id}}">
                            <div class="row  border-bottom">
                                <div class="col-md-8 col-lg-8 col-12 border-end">
                                    <div>
                                        <h6 class="heading-small text-muted my-3">{{__("agent.NormalSet")}}</h6>
                                        <div class="row align-items-center">
                                            <div class="col-md-2 col-lg-2 col-6 mb-3">
                                                <label class="form-control-label" id="username">{{__("agent.ID")}}</label>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-6 mb-3">
                                                <input type="text" name="username" id="username" class="form-control" aria-describedby="username" value="{{ $user->username }}" disabled>
                                            </div>
                                            <div class="col-md-2 col-lg-2 col-6 mb-3">
                                                <label class="form-control-label" id="balance">{{__("agent.Balance")}}</label>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-6 mb-3">
                                                <input type="text" name="balance" id="balance" class="form-control" aria-describedby="balance" value="{{$user->hasRole('manager') ? number_format($user->shop->balance, 2) : number_format($user->balance, 2)}}" disabled>
                                            </div>
                                            @if ($user->isInoutPartner())
                                            <div class="col-md-2 col-lg-2 col-6 mb-3">
                                                <label class="form-control-label" id="address">{{__("agent.TelegramId")}}</label>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-6 mb-3">
                                                <input type="text" name="address" id="address" class="form-control{{ $errors->has('address') ? ' is-invalid' : '' }}" aria-describedby="address" value="{{ old('address', $user->address) }}">
                                            </div>
                                            @endif
                                            <div class="col-md-2 col-lg-2 col-6 mb-3">
                                                <label class="form-control-label" id="phone">{{__("agent.Phone")}}</label>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-6 mb-3">
                                                <input type="text" name="phone" id="phone" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }}" value="{{ old('phone', $user->phone) }}" >
                                            </div>
                                            @if ($user->id == auth()->user()->id || auth()->user()->isInoutPartner())
                                            <div class="col-md-2 col-lg-2 col-6 mb-3">
                                                <label class="form-control-label" id="bank_name">{{__("agent.Bank")}}</label>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-6 mb-3">
                                                    @php
                                                        $banks = array_combine(\App\Models\User::$values['banks'], \App\Models\User::$values['banks']);
                                                    @endphp
                                                    {!! Form::select('bank_name', $banks, $user->bank_name ? $user->bank_name : '', ['class' => 'form-control', 'id' => 'bank_name', 'disabled' => !auth()->user()->isInoutPartner()]) !!}       	
                                            </div>
                                            <?php
                                                $accno = $user->account_no;
                                                $recommender = $user->recommender;
                                                if (!$user->isInOutPartner() && $user->id == auth()->user()->id)
                                                {
                                                    if ($accno != '')
                                                    {
                                                        $maxlen = strlen($accno)>1?2:1;
                                                        $accno = '******' . substr($accno, -$maxlen);
                                                    }
                                                    if ($recommender != '')
                                                    {
                                                        $recommender = mb_substr($recommender, 0, 1) . '***';
                                                    }
                                                }
                                            ?>
                                            <div class="col-md-2 col-lg-2 col-6 mb-3">
                                                <label class="form-control-label" id="account_no">{{__('agent.AccountNumber')}}</label>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-6 mb-3">
                                                <input type="text" name="account_no" id="account_no" class="form-control{{ $errors->has('account_no') ? ' is-invalid' : '' }}" value="{{ $accno }}"  {{auth()->user()->isInoutPartner()?'':'disabled'}}>
                                            </div>
                                            <div class="col-md-2 col-lg-2 col-6 mb-3">
                                                <label class="form-control-label" id="recommender">{{__('agent.AccountHolder')}}</label>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-6 mb-3">
                                                <input type="text" name="recommender" id="recommender" class="form-control{{ $errors->has('recommender') ? ' is-invalid' : '' }}" value="{{ $recommender }}"  {{auth()->user()->isInoutPartner()?'':'disabled'}}>
                                            </div>
                                            @endif
                                            
                                            <div class="col-md-2 col-lg-2 col-6 mb-3">
                                                <label class="form-control-label" id="status">{{__("Status")}}</label>
                                            </div>
                                            <div class="col-md-4 col-lg-4 col-6 mb-3">
                                            <input type="text" name="status" id="status" class="form-control" aria-describedby="status" value="{{$statuses[$user->status]}}" disabled>
                                            </div>
                                            @if (auth()->user()->isInOutPartner())
                                            <div class="col-12">
                                                <div class="row">
                                                    <div class="col-md-2 col-lg-2 col-4 mb-3">
                                                        <label class="form-control-label" id="input-email">{{__("agent.Memo")}}</label>
                                                    </div>
                                                    <div class="col-md-10 col-lg-10 col-12 mb-3">
                                                        @if ($user->memo)
                                                        <h6 class="heading-small text-muted">{{__("agent.CreatedAt")}} : {{$user->memo->created_at}} {{__("agent.UpdatedAt")}} : {{$user->memo->updated_at}}</h6>
                                                        @endif
                                                        <textarea id="memo" name="memo" class="form-control" rows="5">{{$user->memo?$user->memo->memo:''}}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            @if (auth()->user()->hasRole('admin') && $user->isInOutPartner())
                                            <div class="form-group table-responsive col-12 mb-3">
                                                <div class="row align-items-center text-center">
                                                    <div class="col-lg-4 col-md-5 col-6">
                                                        <div class="form-check form-switch">
                                                            <input  class="form-check-input" type="checkbox" name="gameOn" {{isset($user->sessiondata()['gameOn']) && $user->sessiondata()['gameOn']==1?'checked':''}}>
                                                            <label class="form-check-label" for="gameOn">{{__("agent.GameManagePermission")}}</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4 col-md-5 col-6">
                                                        <div class="form-check form-switch">
                                                            <input  class="form-check-input" type="checkbox" name="moneyperm" {{isset($user->sessiondata()['moneyperm']) && $user->sessiondata()['moneyperm']==1?'checked':''}}>
                                                            <label class="form-check-label" for="gameOn">{{__("agent.PartnerBalanceMove")}}</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4 col-md-5 col-6">
                                                        <div class="form-check form-switch">
                                                            <input  class="form-check-input" type="checkbox" name="deluser" {{isset($user->sessiondata()['deluser']) && $user->sessiondata()['deluser']==1?'checked':''}}>
                                                            <label class="form-check-label" for="gameOn">{{__("agent.UserDeletePermission")}}</label>
                                                        </div>
                                                    </div>
                                                {{--
                                                    <div class="col-lg-4 col-md-5 col-6">
                                                        <div class="form-check form-switch">
                                                            <input  class="form-check-input" type="checkbox" name="manualjoin" {{!isset($user->sessiondata()['manualjoin']) || $user->sessiondata()['manualjoin']==1?'checked':''}}>
                                                            <label class="form-check-label" for="gameOn">유저가입 승인</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4 col-md-5 col-6">
                                                        <div class="form-check form-switch">
                                                            <input  class="form-check-input" type="checkbox" name="happyuser" {{isset($user->sessiondata()['happyuser']) && $user->sessiondata()['happyuser']==1?'checked':''}}>
                                                            <label class="form-check-label" for="gameOn">유저 콜기능</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4 col-md-5 col-6">
                                                        <div class="form-check form-switch">
                                                            <input  class="form-check-input" type="checkbox" name="swiching" {{isset($user->sessiondata()['swiching']) && $user->sessiondata()['swiching']==1?'checked':''}}>
                                                            <label class="form-check-label" for="gameOn">프라그마틱 스위칭</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4 col-md-5 col-6">
                                                        <div class="form-check form-switch">
                                                            <input  class="form-check-input" type="checkbox" name="ppverifyOn" {{isset($user->sessiondata()['ppverifyOn']) && $user->sessiondata()['ppverifyOn']==1?'checked':''}}>
                                                            <label class="form-check-label" for="ppverifyOn">프라그마틱 인증</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-4 col-md-5 col-6">
                                                        <div class="form-check form-switch">
                                                            <input  class="form-check-input" type="checkbox" name="evoswitching" {{isset($user->sessiondata()['evoswitching']) && $user->sessiondata()['evoswitching']==1?'checked':''}}>
                                                            <label class="form-check-label" for="evoswitching">에볼루션 스위칭</label>
                                                        </div>
                                                    </div>
                                                --}}
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-lg-2 col-6 mb-3">
                                                <label class="form-control-label" id="input-gameOn">{{__("RTP")}}%</label>
                                            </div>
                                            <div class="col-md-10 col-lg-10 col-12 mb-3">
                                                <input type="text" name="gamertp" id="gamertp" class="form-control{{ $errors->has('gamertp') ? ' is-invalid' : '' }}" value="{{ number_format($rtppercent,2) }}" >
                                            </div>
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-4 col-12">
                                    <div>
                                        <h6 class="heading-small text-muted my-3">{{__('agent.Rolling')}}</h6>
                                        <div class="row align-items-center">
                                            <div class="col-md-5 col-lg-4 col-6 mb-3">
                                                <label class="form-control-label" id="deal_percent">{{__('slot')}}% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['deal_percent']}}-{{__("agent.Max")}}{{$maxPercent['deal_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-7 col-lg-8 col-6 mb-3">
                                                <input type="text" name="deal_percent" id="deal_percent" class="form-control" value="{{$user->deal_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            <div class="col-md-5 col-lg-4 col-6 mb-3">
                                                <label class="form-control-label" id="table_deal_percent">{{__('live')}}% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['table_deal_percent']}}-{{__("agent.Max")}}{{$maxPercent['table_deal_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-7 col-lg-8 col-6 mb-3">
                                                <input type="text" name="table_deal_percent" id="table_deal_percent" class="form-control" value="{{$user->table_deal_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            <div class="col-md-5 col-lg-4 col-6 mb-3">
                                                <label class="form-control-label" id="sports_deal_percent">{{__('sports')}}% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['sports_deal_percent']}}-{{__("agent.Max")}}{{$maxPercent['sports_deal_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-7 col-lg-8 col-6 mb-3">
                                                <input type="text" name="sports_deal_percent" id="sports_deal_percent" class="form-control" value="{{$user->sports_deal_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            <div class="col-md-5 col-lg-4 col-6 mb-3">
                                                <label class="form-control-label" id="card_deal_percent">{{__('BoardGame')}}% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['card_deal_percent']}}-{{__("agent.Max")}}{{$maxPercent['card_deal_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-7 col-lg-8 col-6 mb-3">
                                                <input type="text" name="card_deal_percent" id="card_deal_percent" class="form-control" value="{{$user->card_deal_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            {{--
                                            <div class="col-md-5 col-lg-4 col-6 mb-3">
                                                <label class="form-control-label" id="pball_single_percent">파워볼 단폴롤링% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['pball_single_percent']}}-{{__("agent.Max")}}{{$maxPercent['pball_single_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-7 col-lg-8 col-6 mb-3">
                                                <input type="text" name="pball_single_percent" id="pball_single_percent" class="form-control" value="{{$user->pball_single_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            <div class="col-md-5 col-lg-4 col-6 mb-3">
                                                <label class="form-control-label" id="pball_comb_percent">파워볼 조합롤링% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['pball_comb_percent']}}-{{__("agent.Max")}}{{$maxPercent['pball_comb_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-7 col-lg-8 col-6 mb-3">
                                                <input type="text" name="pball_comb_percent" id="pball_comb_percent" class="form-control" value="{{$user->pball_comb_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            --}}
                                            <div class="col-md-5 col-lg-4 col-6 mb-3">
                                                <label class="form-control-label" id="ggr_percent">{{__('slot')}}{{__('GGR')}}% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['ggr_percent']}}-{{__("agent.Max")}}{{$maxPercent['ggr_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-7 col-lg-8 col-6 mb-3">
                                                <input type="text" name="ggr_percent" id="ggr_percent" class="form-control" value="{{$user->ggr_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                            <div class="col-md-5 col-lg-4 col-6 mb-3">
                                                <label class="form-control-label" id="table_ggr_percent">{{__('live')}}{{__('GGR')}}% @if(!$isSelf)<span class="text-danger">({{__("agent.Min")}}{{$minPercent['table_ggr_percent']}}-{{__("agent.Max")}}{{$maxPercent['table_ggr_percent']}})</span>@endif</label>
                                            </div>
                                            <div class="col-md-7 col-lg-8 col-6 mb-3">
                                                <input type="text" name="table_ggr_percent" id="table_ggr_percent" class="form-control" value="{{$user->table_ggr_percent??0}}" {{$isSelf?'disabled':''}}>
                                            </div>
                                        </div>    
                                    </div>    
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-success px-8 my-2">{{__('agent.Save')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="row mb-4">
                @if ($user->role_id >= 3 && auth()->user()->role_id>$user->role_id && auth()->user()->hasRole(['admin','comaster'])) 
                    <div class="col-lg-6 col-md-6 col-12 border mb-2">            
                        <form method="post" action="{{argon_route('common.profile.accessrule')}}" autocomplete="off">
                            @csrf
                            <input type="hidden" name="id" value="{{$user->id}}">
                            <div class="col-md-12 col-lg-12 col-12 ">
                                <h6 class="heading-small text-muted my-3">{{__('agent.AccessSet')}}</h6>                                
                                <div class="col-md-12 col-lg-12 col-12">
                                    <label class="form-control-label" id="ip_address">{{__('agent.AccessIP')}} <span class="text-danger"> {{__('agent.AccessIPMsg')}}</span></label>
                                </div>
                                <div class="col-md-12 col-lg-12 col-12 mb-3">
                                <textarea id="ip_address" name="ip_address" class="form-control" rows="5">{{$user->accessrule?$user->accessrule->ip_address:''}}</textarea>
                                </div>
                                <div class="row">
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-check form-switch">
                                        <input  class="form-check-input" type="checkbox" name="allow_ipv6" {{$user->accessrule==null || $user->accessrule->allow_ipv6==1?'checked':''}}>
                                        <label class="form-check-label" for="gameOn">IPv6 {{__('agent.Allow')}}</label>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-12">
                                    <div class="form-check form-switch">
                                        <input  class="form-check-input" type="checkbox" name="check_cloudflare" {{$user->accessrule && $user->accessrule->check_cloudflare==1?'checked':''}}>
                                        <label class="form-check-label" for="check_cloudflare">{{__('agent.CloudflareIPCheck')}}</label>
                                    </div>
                                </div>
                                </div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-success px-6 mt-4">{{__('agent.Apply')}}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endif
                    @if ($user->id == auth()->user()->id || auth()->user()->isInoutPartner() || (auth()->user()->hasRole('manager') && $user->hasRole('user')))
                    <div class="col-lg-6 col-md-6 col-12 border mb-2">
                        <form method="post" action="{{argon_route('common.profile.password')}}" autocomplete="off">
                            @csrf
                            <input type="hidden" name="id" value="{{$user->id}}">
                            <div class="col-md-12 col-lg-12 col-12">
                                <div>
                                    <h6 class="heading-small text-muted my-3">{{__('agent.PassSet')}}</h6>  
                                    <div class="row">                              
                                        <div class="col-md-6 col-lg-4 col-6">
                                            <label class="form-control-label" id="password">{{__('agent.Password')}}</span></label>
                                        </div>
                                        <div class="col-md-6 col-lg-8 col-6 mb-3">
                                        <input type="password" name="password" id="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" placeholder="" value="" required>
                                        </div>
                                        <div class="col-md-6 col-lg-4 col-6">
                                            <label class="form-control-label" id="password_confirmation">{{__('agent.ConfirmPass')}}</span></label>
                                        </div>
                                        <div class="col-md-6 col-lg-8 col-6 mb-3">
                                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="" value="" required>
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-success px-6">{{__('agent.Apply')}}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endif
                    {{--
                    @if ($user->id == auth()->user()->id)
                    <div class="col-lg-6 col-md-6 col-12 border mb-2">
                        <form method="post" action="{{argon_route('common.profile.dwpass')}}" autocomplete="off">
                            @csrf
                            <input type="hidden" name="id" value="{{$user->id}}">
                            <div class="col-md-12 col-lg-12 col-12">
                                <div>
                                    <h6 class="heading-small text-muted my-3">환전비밀번호</h6>
                                    <div class="row">                              
                                        <div class="col-md-6 col-lg-2 col-6">
                                            <label class="form-control-label" id="old_confirmation_token">기존 비밀번호</span></label>
                                        </div>
                                        <div class="col-md-6 col-lg-4 col-6 mb-3">
                                        <input type="password" name="old_confirmation_token" id="old_confirmation_token" class="form-control{{ $errors->has('old_confirmation_token') ? ' is-invalid' : '' }}" placeholder="" value="">
                                        </div>
                                        <div class="col-md-6 col-lg-2 col-6">
                                            <label class="form-control-label" id="confirmation_token">새 비밀번호</span></label>
                                        </div>
                                        <div class="col-md-6 col-lg-4 col-6 mb-3">
                                            <input type="password" name="confirmation_token" id="confirmation_token" class="form-control{{ $errors->has('confirmation_token') ? ' is-invalid' : '' }}" placeholder="" value="" required>
                                        </div>
                                        <div class="col-md-6 col-lg-2 col-6">
                                            <label class="form-control-label" id="confirmation_token_confirmation">비밀번호 확인</span></label>
                                        </div>
                                        <div class="col-md-6 col-lg-4 col-6 mb-3">
                                            <input type="password" name="confirmation_token_confirmation" id="confirmation_token_confirmation" class="form-control" placeholder="" value="" required>
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-success px-6">환전비번 변경</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endif
                    --}}
                </div>
                <div class="row mb-4">
                    <div class="col border mb-2">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-12 border-end">
                                <h6 class="heading-small text-muted my-3">{{__('agent.Recently')}} {{__('agent.DepositHistory')}}</h6>      
                                <div class="table-responsive">
                                    <table class="table table-bordered text-center align-items-center table-flush" id="agentlist">
                                        <thead class="thead-light">
                                            <tr>
                                                <th scope="col">{{__('agent.Amount')}}</th>
                                                <th scope="col">{{__('agent.ID')}}</th>
                                                <th scope="col">{{__('agent.Payer')}}</th>
                                                <th scope="col">{{__('agent.Type')}}</th>
                                                <th scope="col">{{__('agent.DateTime')}}</th>
                                                <th scope="col">{{__('agent.Notes')}}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list">
                                            @foreach($transactions as $stat)
                                                <tr>
                                                    <td>{{number_format($stat->summ, 2)}}</td>
                                                    <td>
                                                    @if ($stat->user)
                                                        <a href="#" data-toggle="tooltip" data-original-title="{{$stat->user->parents(auth()->user()->role_id)}}">
                                                        {{$stat->user->username}} <span class="badge {{$badge_class[$stat->user->role_id]}}">{{__($stat->user->role->name)}}</span>
                                                        </a>
                                                    @else
                                                        {{__('Unknown')}}
                                                    @endif
                                                    </td>
                                                    <td>
                                                        @if ($stat->admin)
                                                        {{$stat->admin->username}} <span class="badge {{$badge_class[$stat->admin->role_id]}}">{{__($stat->admin->role->name)}}</span>
                                                        @else
                                                        {{__('Unknown')}}
                                                        @endif
                                                    </td>
                                                    <td>
                                                    @if($stat->type == 'add')
                                                    <span class="text-success">
                                                    {{__('agent.Deposit')}}
                                                    </span>
                                                    @else
                                                    <span class="text-danger">
                                                    {{__('agent.Withdraw')}}
                                                    </span>
                                                    @endif
                                                    </td>
                                                    <td>{{$stat->created_at}}</td>
                                                    <td>{{$stat->reason}}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-12">
                                <h6 class="heading-small text-muted my-3">{{__('agent.Recently')}} {{__('agent.WebLoginInfo')}}</h6>      
                                <div class="table-responsive">
                                    <table class="table table-bordered text-center align-items-center table-flush" id="agentlist">
                                        <thead class="thead-light">
                                            <tr>
                                                <th scope="col">{{__('agent.DateTime')}}</th>
                                                @if (auth()->user()->isInoutPartner())
                                                <th scope="col">{{__('agent.AccessIP')}}</th>
                                                @endif
                                                <th scope="col">{{__('agent.Notes')}}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list">
                                            @foreach($userActivities as $activity)
                                                <tr>
                                                    <td>{{ $activity->created_at}}</td>
                                                    @if (auth()->user()->isInoutPartner())
                                                        <td>{{ $activity->ip_address }}</td>
                                                    @endif
                                                    <td>{{ $activity->description }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
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
    function activeUsers(status, userid)
    {
        if(status == 1){
            $("#btn_disable").css('pointer-events', 'none');
        }else{
            $("#btn_active").css('pointer-events', 'none');
        }
        var user_ids=[userid];
        $.ajax({
            url: "{{argon_route('player.active')}}",
            type: "GET",
            data: {ids:  user_ids, status:status},
            dataType: 'json',
            success: function (data) {                
                $("#btn_active").css('pointer-events', 'auto');
                $("#btn_disable").css('pointer-events', 'auto');
                if (data.error)
                {
                    alert(data.msg);
                }
                else
                {
                    $('#mainfrm').submit();
                }
                
            },
            error: function () {
            }
        });
    }
    function deleteUsers(userid, hard)
    {
        $("#btn_delete").css('pointer-events', 'none');
        var user_ids=[userid];
        if(confirm('{!!__("agent.DeleteUser")!!}')){
            $.ajax({
            url: "{{argon_route('player.delete')}}",
            type: "GET",
            data: {ids:  user_ids, hard:hard},
            dataType: 'json',
            success: function (data) {
                $("#btn_delete").css('pointer-events', 'auto');
                if (data.error)
                {
                    alert(data.msg);
                }
                else
                {
                    window.location.href = "{{ argon_route('dashboard')}}";
                }
                
            },
            error: function () {
            }
        });
        }else{
            $("#btn_delete").css('pointer-events', 'auto');
        }
    }
    function refreshPlayerBalance(userid, dealid)
    {
        if(dealid==0){
            $('#uid_' + userid).text('{!!__("agent.RequestingBalance")!!}');
            $('#rfs_' + userid).css("pointer-events", "none");
            setTimeout(() => {
                    $('#rfs_' + userid).css("pointer-events", "auto"); 
                }, 10000);
        }else{
            $('#duid_' + userid).text('{!!__("agent.RequestingRolling")!!}');
            $('#drfs_' + userid).css("pointer-events", "none");
            setTimeout(() => {
                    $('#drfs_' + userid).css("pointer-events", "auto"); 
                }, 10000);
        }
        $.ajax({
            url: "{{argon_route('player.refresh')}}",
            type: "GET",
            data: {id:  userid, deal:dealid},
            dataType: 'json',
            success: function (data) {
                if (data.error)
                {
                    alert(data.msg);
                }
                else
                {
                    if(dealid==0){
                        $('#uid_' + userid).text(data.balance);
                    }else{
                        $('#duid_' + userid).text(data.balance);
                    }
                }
                
            },
            error: function () {
            }
        });
    }
</script>
@endpush

