<?php 
namespace App\Http\Requests\Permission
{
    class RemovePermissionRequest extends \App\Http\Requests\Request
    {
        public function authorize()
        {
            return $this->route('permission')->removable;
        }
        public function rules()
        {
            return [];
        }
    }

}
