<div class="form-group">
    <label>{{__('agent.Domain')}}</label>
    <input type="text" class="form-control" id="domain" name="domain" value="{{ ($edit == 1 && isset($website->domain)) ? $website->domain : '' }}">
</div>
<div class="form-group">
    <label>{{__('agent.Title')}}</label>
    <input type="text" class="form-control" id="title" name="title"  value="{{ $edit == 1 ? $website->title : '' }}">
</div>

<div class="form-group">
    <label>{{__('agent.PageType')}}</label>
    {!! Form::select('frontend', isset($frontends) ? $frontends : '',  $edit == 1 ? $website->frontend : '' , ['class' => 'form-control']) !!}
</div>

<div class="form-group">
    <label>{{__('agent.AdminPageDomain')}}</label>
    <input type="text" class="form-control" id="backend" name="backend"  value="{{ $edit == 1 ? $website->backend : '' }}">
</div>

<div class="form-group">
    <label>{{__('CoMaster')}}</label>
    <input type="text" class="form-control" id="admin" name="admin"  value="{{ ($edit == 1 && isset($website->admin)) ? $website->admin->username : '' }}">
</div>