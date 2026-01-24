<tr>   
    <td class="text-center"><input type="checkbox" class="checkitem" name="domain_CheckBox"></td>
    <td class="text-center"><span>{{$website->id}}</span></td>
    <td class="text-center"><span>{{$website->domain}}</span></td>
    <td class="text-center"><span class="btn btn-success m-0 rounded-0">{{ $website->title }}</span></td>
    <td class="text-center"><span>{{$website->frontend}}</span></td>
    <td class="text-center"><span>{{$website->backend}}</span></td>
    <td class="text-center"><span class="btn btn-warning m-0 rounded-0">{{ $website->admin ?$website->admin->username :__('Unknown') }}</span></td>
    <td>{{ $website->created_at }}</td>
    <td>
        
        @if ($website->status == 1)
        <div class="form-check form-switch"  style="display: block;">
            <input class="form-check-input " id="statusChange"  type="checkbox" checked="" data-value='{{$website->id}}' onclick="toggle('{{$website->id}}')">
            <label class="form-check-label text-primary" id="switchChange{{$website->id}}" for="flexSwitchCheckDefault" data-value='{{$website->id}}'>{{__('agent.GameEnable')}}</label>
        </div>
        @else
        <div class="form-check form-switch" style="display: block;">
            <input class="form-check-input" id="statusChange" type="checkbox" name="switch" data-value='{{$website->id}}' onclick="toggle('{{$website->id}}')">
            <label class="form-check-label text-danger switchlabel" id="switchChange{{$website->id}}" name="switch" for="flexSwitchCheckDefault" data-value='{{$website->id}}'>{{__('agent.GameDisable')}}</label>
        </div>
        @endif
    </td>
    <td>
        <a onclick="window.open('{{ argon_route('websites.edit',['id' => $website->id, 'edit'=>true]) }}','{{__("agent.Domain")}} {{__("Edit")}}','width=900, height=750, left=500, top=125, toolbar=no, menubar=no, scrollbars=no, resizable=yes');">
            <button type="button" class="btn btn-info btn-sm rounded-0 mb-0" style="background-color:#5c1ac3">{{__('Edit')}}</button>
        </a>
    </td>
</tr>
