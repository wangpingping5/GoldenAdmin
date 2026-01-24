@if (count($msgs) > 0)
    @foreach ($msgs as $msg)
        <?php
            $backcolor = '';
            $fontbold = '';
            if ($msg->user && $msg->user->id==auth()->user()->id && $msg->read_at==null)
            {
                $backcolor = 'lightgreen';
                $fontbold = 'bold';
            }
        ?>
        <tr data-tt-id="{{$msg->id}}" data-tt-parent-id="{{$msg->ref_id}}" data-tt-branch="{{count($msg->refs)>0?'true':'false'}}" style="background-color:{{$backcolor}};font-weight:{{$fontbold}};">
            @include('notices.messages.partials.row')
        </tr>
        @if (count($msg->refs)>0)
            @foreach ($msg->refs as $ref)
                <?php
                    $backcolor = '';
                    $fontbold = '';
                    if ($ref->user && $ref->user->id==auth()->user()->id && $ref->read_at==null)
                    {
                        $backcolor = 'lightgreen';
                        $fontbold = 'bold';
                    }
                ?>
                <tr data-tt-id="{{$ref->id}}" data-tt-parent-id="{{$ref->ref_id}}" data-tt-branch="{{count($ref->refs)>0?'true':'false'}}" style="background-color:{{$backcolor}};font-weight:{{$fontbold}};">
                    @include('notices.messages.partials.row', ['msg' => $ref])
                </tr>
            @endforeach
        @endif
    @endforeach
@else
    <tr  data-tt-id="-10" data-tt-parent-id="{{isset($child_id)?$child_id:0}}" >
        <td colspan='11'>{{__('No Data')}}</td>
    </tr>
@endif