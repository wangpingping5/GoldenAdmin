<td><input type="checkbox" class="Check" name="shop_CheckBox"></td>
<td>{{ $shop->id }}</td>
<td>{{ $shop->name }} </td>
<td>{{ $shop->deal_percent }}</td>
<td>{{ $shop->table_deal_percent }}</td>
@if (auth()->user()->hasRole('admin'))
<td>
    @if ($shop->info)
        @foreach ($shop->info as $inf)
            @if ($inf->info->roles == 'slot')
            {{$inf->info->text}}
            @endif
        @endforeach
    @endif
</td>
<td>
    @if ($shop->info)
        @foreach ($shop->info as $inf)
            @if ($inf->info->roles == 'table')
            {{$inf->info->text}}
            @endif
        @endforeach
    @endif
</td>
@endif
<td>
    @if ($shop->slot_miss_deal>0)
        <span class="text-success">적용</span>
    @else
        <span class="text-warning">금지</span>
    @endif
</td>

<td>
    @if ($shop->table_miss_deal>0)
        <span class="text-success">적용</span>
    @else
        <span class="text-warning">금지</span>
    @endif
</td>