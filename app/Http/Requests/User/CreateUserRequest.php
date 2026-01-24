<?php 
namespace App\Http\Requests\User
{
    class CreateUserRequest extends \App\Http\Requests\Request
    {
        public function rules()
        {
            $rules = [
                'username' => 'required|regex:/^[A-Za-z0-9가-힣]+$/', 
                'password' => 'required|min:6|confirmed'
            ];
            return $rules;
        }
    }

}
