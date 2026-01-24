<table class="table align-items-center table-flush">
	<thead class="thead-light">
		<tr>
			<th></th>
			<th scope="col">{{__('agent.Title')}}</th>
			<th style="display:none;">{{__('agent.Content')}}</th>
		</tr>

	</thead>
	<tbody>
		@if (count($msgtemps))
			@foreach ($msgtemps as $msgtemp)
				<tr>
					<td><a href="#" onClick="addTemplate($('#temp{{$msgtemp->id}}').text());"><button type="button" class="btn btn-warning btn-sm"><<</button></a></td>
					<td>{{$msgtemp->title}}</td>
					<td style="display:none;" id="temp{{$msgtemp->id}}">{{$msgtemp->content}}</td>
				</tr>
			@endforeach
		@else
			<tr><td colspan='5'>No Data</td></tr>
		@endif
	</tbody>
</table>