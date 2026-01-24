<tr>   
    <td class="text-center"><input type="checkbox" class="checkitem" name="tmp_CheckBox"></td>
    <td class="text-center"><span>{{$msgtemp->id}}</span></td>
    <td class="text-center"><span>{{$msgtemp->title}}</span></td>
    <td class="text-center"><span class="btn btn-success px-2 py-1 mb-0">{{ $msgtemp->order }}</span></td>
    <td class="text-center"><span>{{$msgtemp->created_at}}</span></td>
    @if (auth()->user()->hasRole('admin'))
    <td class="text-center">{{$msgtemp->writer?$msgtemp->writer->username:{{__('Unknown')}}}}</td>
    @endif
    <td class="text-center">
        <a onclick="window.open('{{ argon_route('msgtemp.edit',['id' => $msgtemp->id, 'edit'=>true]) }}','{{__("Edit")}}','width=900, height=780, left=500, top=100, toolbar=no, menubar=no, scrollbars=no, resizable=yes');">
            <button type="button" class="btn btn-info btn-sm rounded-0 mb-0" style="background-color:#5c1ac3">{{__('Edit')}}</button>
        </a>
    </td>
</tr>
