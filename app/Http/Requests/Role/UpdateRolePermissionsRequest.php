<?php 
namespace App\Http\Requests\Role
{
    class UpdateRolePermissionsRequest extends \App\Http\Requests\Request
    {
        public function rules()
        {
            $permissions = \App\Permission::pluck('id')->toArray();
            return [
                'permissions' => 'required|array', 
                'permissions.*' => \Illuminate\Validation\Rule::in($permissions)
            ];
        }
        public function messages()
        {
            return ['permissions.*' => 'Provided permission does not exist.'];
        }
    }

}
