<?php 
namespace App\Http\Requests\Auth
{
    class PasswordResetRequest extends \App\Http\Requests\Request
    {
        public function rules()
        {
            return [
                'token' => 'required', 
                'email' => 'required|email', 
                'password' => 'required|confirmed|min:6'
            ];
        }
    }

}
