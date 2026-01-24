<?php 
namespace App\Http\Requests\Role
{
    class CreateRoleRequest extends \App\Http\Requests\Request
    {
        public function rules()
        {
            return ['slug' => 'required|regex:/^[a-zA-Z0-9\-_\.]+$/|unique:roles,slug'];
        }
    }

}
