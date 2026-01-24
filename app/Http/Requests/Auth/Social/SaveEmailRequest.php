<?php 
namespace App\Http\Requests\Auth\Social
{
    class SaveEmailRequest extends \App\Http\Requests\Request
    {
        public function rules()
        {
            return ['email' => 'required|email|unique:users,email'];
        }
    }

}
