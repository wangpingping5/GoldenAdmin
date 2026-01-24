<?php 
namespace App\Http\Requests\User
{
    class UpdateDetailsRequest extends \App\Http\Requests\Request
    {
        public function rules()
        {
            return [
                'password' => 'confirmed', 
                'confirmation_token' => 'confirmed', 
            ];
        }
    }

}
