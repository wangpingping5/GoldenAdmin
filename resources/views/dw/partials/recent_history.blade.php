<div class="row">
    <div class="col">                    
        <div class="table-responsive">
            <table class="table table-bordered align-items-center text-center table-flush p-0 mb-0" id="datalist">
                <thead>
                    <tr>
                        <th scope="col">{{__('agent.No')}}</th>
                        <th scope="col">{{__('agent.Amount')}}</th>
                        <th scope="col">{{__('agent.Type')}}</th>
                        <th scope="col">{{__('agent.PaymentAccount')}}</th>
                        <th scope="col">{{__('agent.DateTime')}}</th>
                        <th scope="col">{{__('agent.Status')}}</th>
                    </tr>
                </thead>
                <tbody class="list">
                    @if (count($in_out_logs) > 0)
                        @foreach ($in_out_logs as $stat)
                            <tr>
                                <td>{{$stat->id}}</td>
                                @if($stat->type == 'add')
                                <td><span class="text-success">{{number_format($stat->sum, 2)}}</span></td>
                                <td><span class="text-success">{{__('agent.Deposit')}}</span></td>
                                @else
                                <td><span class="text-warning">{{number_format($stat->sum, 2)}}</span></td>
                                <td><span class="text-warning">{{__('agent.Withdraw')}}</span></td>
                                @endif
                                <td>{{$stat->bankInfo(!auth()->user()->isInOutPartner())}}</td>
                                <td>{{$stat->created_at}}</td>
                                <td>
                                    @if ($stat->status==1)
                                        <span class="text-success px-2 py-1 mb-0 rounded-0">{{__('agent.Approved')}}</span>
                                    @elseif ($stat->status==0)
                                        <span class="text-primary px-2 py-1 mb-0 rounded-0">{{__('agent.Request')}}</span>
                                    @elseif ($stat->status==2)
                                        <span class="text-danger px-2 py-1 mb-0 rounded-0">{{__('agent.Cancel')}}</span>
                                    @else   
                                        <span class="text-info px-2 py-1 mb-0 rounded-0">{{__('agent.Waiting')}}</span>
                                    @endif    
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr><td colspan="6">{{__('No Data')}}</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>