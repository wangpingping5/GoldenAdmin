<?php 
namespace App\Http\Requests\Auth
{
    class PasswordRemindRequest extends \App\Http\Requests\Request
    {
        public function rules()
        {
            return ['email' => 'required|email|exists:users,email'];
        }
    }

}
