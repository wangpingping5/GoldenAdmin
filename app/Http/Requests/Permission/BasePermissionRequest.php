<?php 
namespace App\Http\Requests\Permission
{
    class BasePermissionRequest extends \App\Http\Requests\Request
    {
        public function messages()
        {
            return ['name.unique' => trans('app.permission_already_exists')];
        }
    }

}
