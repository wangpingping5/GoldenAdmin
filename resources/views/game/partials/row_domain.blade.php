@foreach ($categories as $category)
    @if ($loop->index > 0)
	<tr>
	@endif
    <td>
        {{$category->title}}
    @if (auth()->user()->hasRole('admin'))
    <td>{{$category->provider??'자체버전'}}</td>
    <td>
        <div class="form-check form-switch justify-content-center">
            <input class="form-check-input " id="chkview{{$category->id}}"  type="checkbox" onclick="toggleview('{{$category->id}}')" {{$category->view==1 ? 'checked' : ''}}>
            <label class="form-check-label" for="chkview{{$category->id}}">
            @if ($category->view == 1)
            <span class="text-green" id="spview{{$category->id}}">사용</span>
            @else
            <span class="text-danger" id="spview{{$category->id}}">내림</span>
            @endif
            </label>
        </div>
    </td>
    @else
    <td>
    공식사
    </td>
    @endif
    @if(auth()->user()->isInoutPartner() && isset(auth()->user()->sessiondata()['swiching']) && auth()->user()->sessiondata()['swiching']==1 && $category->title=='Pragmatic Play')
    <td>
        <div class="form-check form-switch justify-content-center">
            <input class="form-check-input " id="chkview{{$category->id}}"  type="checkbox" onclick="toggleview('{{$category->id}}')" {{$category->view==1 ? 'checked' : ''}}>
            <label class="form-check-label" for="chkview{{$category->id}}">
            @if ($category->view == 1)
            <span class="text-green" id="spview{{$category->id}}">운영</span>
            @else
            <span class="text-danger" id="spview{{$category->id}}">점검</span>
            @endif
            </label>
        </div>
    </td>
    @else
    <td>
        <div class="form-check form-switch justify-content-center">
            <input class="form-check-input " id="chkstatus{{$category->id}}"  type="checkbox" onclick="togglestatus('{{$category->id}}')" {{$category->status==1 ? 'checked' : ''}}>
            <label class="form-check-label" for="chkstatus{{$category->id}}">
            @if ($category->status == 1)
            <span class="text-green" id="spstatus{{$category->id}}">운영</span>
            @else
            <span class="text-danger" id="spstatus{{$category->id}}">점검</span>
            @endif
            </label>
        </div>
    </td>
    @endif
    @if ($loop->index > 0)
	</tr>
	@endif
@endforeach
