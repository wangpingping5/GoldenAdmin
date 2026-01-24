<?php 
namespace App\Http\Requests\Role
{
    class RemoveRoleRequest extends \App\Http\Requests\Request
    {
        public function authorize()
        {
            return $this->route('role')->removable;
        }
        public function rules()
        {
            return [];
        }
    }

}
