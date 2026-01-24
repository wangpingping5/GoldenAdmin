<div class="form-group">
    <label>{{__('agent.Title')}}</label>
    <input type="text" class="form-control title" id="title" name="title" placeholder="@lang('app.title')" required value="{{ $edit == 1 ? $msgtemp->title : '' }}">
</div>
<div class="form-group">
    <label>{{__('OrderBy')}}</label>
    <input type="text" class="form-control order" id="order" name="order"  required value="{{ $edit == 1 ? $msgtemp->order : '' }}">
</div>

<div class="form-group">
    <label>{{__('agent.Template')}}</label>
    <div id="quillArea" class="mb-3 height-200"></div>
    <textarea id="content" name="content" class="mb-3 d-none" rows="3">{{ $edit == 1 ? $msgtemp->content : '' }}</textarea>
</div>
