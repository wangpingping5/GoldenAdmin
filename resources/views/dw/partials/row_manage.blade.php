@if($history == false)
<td>
    @if($stat->status == \App\Models\WithdrawDeposit::REQUEST)
    <input type="checkbox" class="Check" name="user_CheckBox">
    @endif
</td>
@endif
<td>{{$stat->id}}</td>
@if ($stat->user)
<td><span class="btn {{$badge_class[$stat->user->role_id]}} px-2 py-1 mb-0 rounded-0">{{__($stat->user->role->name)}}</span></td>
<td><span class="btn {{$badge_class[$stat->user->referral->role_id]}} px-2 py-1 mb-0 rounded-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{!!$stat->user->parents(auth()->user()->role_id, $stat->user->role_id+1)!!}">[{{__($stat->user->referral->role->name)}}] </br>{{$stat->user->referral->username}}</span></td>
<td><a class="text-primary" href="{{argon_route('common.profile', ['id'=>$stat->user->id])}}">{{$stat->user->username}}</a></td>
@else
<td></td>
<td></td>
<td></td>
@endif
@if($stat->type == 'add')
<td><span class="text-success">{{__('agent.Deposit')}}</td>
<td><span class="text-success">{{number_format($stat->sum,2)}}</span></td>
@else
<td><span class="text-danger">{{__('agent.Withdraw')}}</td>
<td><span class="text-danger">{{number_format($stat->sum,2)}}</span></td>
@endif
@if ($stat->transaction)
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('OldBalance')}}</span></td>
                <td class="text-end w-70">{{number_format($stat->transaction->old,2)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('NewBalance')}}</span></td>
                <td class="text-end w-70">{{number_format($stat->transaction->new,2)}}</td>
            </tr>
        </tbody>
    </table>
</td>
@else
<td></td>
@endif
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.Bank')}}</span></td>
                <td class="text-end w-70">{{$stat->bank_name}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-info px-2 py-1 mb-1 rounded-0">{{__('agent.PaymentAccount')}}</span></td>
                <td class="text-end w-70">{{$stat->account_no}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-warning px-2 py-1 mb-1 rounded-0">{{__('agent.AccountHolder')}}</span></td>
                <td class="text-end w-70">{{$stat->recommender}}</td>
            </tr>
        </tbody>
    </table>
</td>
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.Request')}}</span></td>
                <td class="text-end w-70">{{$stat->created_at->format('Y-m-d H:i')}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-1 rounded-0">{{__('agent.Finished')}}</span></td>
                @if($stat->status == \App\Models\WithdrawDeposit::DONE || $stat->status == \App\Models\WithdrawDeposit::CANCEL)
                    <td class="text-end w-70">{{$stat->updated_at->format('Y-m-d H:i')}}</td>
                @else
                    <td class="text-end w-70"></td>
                @endif
            </tr>
        </tbody>
    </table>
</td>
@if($stat->user)
<td>
    <table>
        <tbody>
            <tr>
                <td class="text-start"><span  class="btn btn-primary px-2 py-1 mb-1 rounded-0">{{__('agent.Deposit')}}</span></td>
                <td class="text-end w-70">{{number_format($stat->user->total_in, 2)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-danger px-2 py-1 mb-1 rounded-0">{{__('agent.Withdraw')}}</span></td>
                <td class="text-end w-70">{{number_format($stat->user->total_out, 2)}}</td>
            </tr>
            <tr>
                <td class="text-start"><span  class="btn btn-success px-2 py-1 mb-0 rounded-0">{{__('agent.Profit')}}</span></td>
                <td class="text-end w-70">{{number_format($stat->user->total_in - $stat->user->total_out, 2)}}</td>
            </tr>
        </tbody>
    </table>
</td>
@else
<td></td>
@endif
<td>
    @if ($stat->status==1)
        @if($stat->type == 'add')
            <span class="text-success px-2 py-1 mb-0 rounded-0">{{__('agent.Deposit')}}</span>
        @else
            <span class="text-warning px-2 py-1 mb-0 rounded-0">{{__('agent.Withdraw')}}</span>
        @endif
    @elseif ($stat->status==0)
        <span class="text-primary px-2 py-1 mb-0 rounded-0">{{__('agent.Request')}}</span>
    @elseif ($stat->status==2)
        <span class="text-danger px-2 py-1 mb-0 rounded-0">{{__('agent.Cancel')}}</span>
    @else   
        <span class="text-info px-2 py-1 mb-0 rounded-0">{{__('agent.Cancel')}}</span>
    @endif
</td>
<td>
    @if($stat->user && $stat->user->hasRole('user'))
    <a href="{{argon_route('player.gamehistory', ['userID'=>'player', 'search' => $stat->user->username])}}"><span class="btn btn-info px-2 py-1 mb-0 rounded-0">{{__('agent.BetHistory')}}</span></a>
    @endif
</td>
@if($history == false)
<td>
    @if($stat->status == \App\Models\WithdrawDeposit::REQUEST || $stat->status == \App\Models\WithdrawDeposit::WAIT)
    <a href="javascript:;" id="process{{$stat->id}}" onClick="dwProcess({{$stat->id}}, '{{$stat->type}}');"><button class="btn btn-success px-2 py-1 mb-0 rounded-0" >{{__('agent.Allow')}}</button></a>
    <a href="{{ argon_route('ec.reject', ['id' => $stat->id]) }}" 
        data-method="DELETE"
        data-confirm-title="{{__('agent.Confirm')}}"
        data-confirm-text="{{__('agent.RequestCancel?')}}"
        data-confirm-delete="{{__('agent.Confirm')}}"
        data-confirm-cancel="{{__('agent.Cancel')}}">
        <button class="btn btn-danger px-2 py-1 mb-0 rounded-0" >{{__('agent.Cancel')}}</button>
    </a>
    @endif
</td>
@endif