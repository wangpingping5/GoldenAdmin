@if (auth()->user()->hasRole('admin'))
<div class="form-group">
    <label>{{__('agent.Maker')}}</label>
    <input type="text" class="form-control title" id="user" name="user" placeholder="@lang('app.title')" required value="{{ $edit == 1 ? $notice->writer->username : '' }}">
</div> 
@endif
<div class="form-group">
    <label>{{__('agent.Title')}}</label>
    <input type="text" class="form-control order" id="title" name="title"  required value="{{ $edit == 1 ? $notice->title : '' }}">
</div>

<div class="form-group">
    <label>{{__('agent.Content')}}</label>
    <div id="quillArea" class="mb-3 height-200"></div>
    <textarea id="content" name="content" class="mb-3 d-none" rows="3">{{ $edit == 1 ? $notice->content : '' }}</textarea>
</div>

<div class="form-group">
    <label>{{__('agent.No')}}</label>
    <input type="number" class="form-control order" id="order" name="order"  required value="{{ $edit == 1 ? $notice->order : '' }}">
</div> 

<div class="form-group">
    <label>{{__('agent.NoticeType')}}</label>
    {!! Form::select('popup', \App\Models\Notice::popups(), $edit == 1 ? $notice->popup : 'all', ['id' => 'all', 'class' => 'form-control']) !!}
</div>

<div class="form-group">
    <label>{{__('agent.NoticeTarget')}}</label>
    {!! Form::select('type', \App\Models\Notice::lists(), $edit == 1 ? $notice->type : 'user', ['id' => 'type', 'class' => 'form-control']) !!}
</div>

<div class="form-group">
    <label>@lang('app.status')</label>
    {!! Form::select('active', ['0' => __('Disable'), '1' => __('Active')], $edit ? $notice->active : 1, ['id' => 'active', 'class' => 'form-control']) !!}
</div>