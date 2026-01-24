<?php 
namespace App\Http\Requests\Auth
{
    class RegisterRequest extends \App\Http\Requests\Request
    {
        public function rules()
        {
            $rules = [
                'username' => 'required|regex:/^[A-Za-z0-9가-힣]+$/|unique:users,username', 
                'password' => 'required|confirmed|min:6'
            ];
            if( settings('tos') ) 
            {
                $rules['tos'] = 'accepted';
            }
            if( settings('use_email') ) 
            {
                $rules['email'] = 'required|unique:users,email';
            }
            return $rules;
        }
        public function messages()
        {
            return ['tos.accepted' => trans('app.you_have_to_accept_tos')];
        }
    }

}
