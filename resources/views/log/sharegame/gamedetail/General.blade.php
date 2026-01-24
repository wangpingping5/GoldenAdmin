
<tr>
    <td>상세</td>
    <td style="  word-break: break-all;">
        @if (isset($res['bets']))
            {{json_encode($res['bets'], JSON_UNESCAPED_UNICODE)}}
        @endif
    </td>
</tr>
