<?php 
namespace App\Http\Requests\User
{
    class UpdateLoginDetailsRequest extends \App\Http\Requests\Request
    {
        public function rules()
        {
            $user = $this->getUserForUpdate();
            return [
                'username' => 'regex:/^[A-Za-z0-9가-힣]+$/|nullable|unique:users,username,' . $user->id, 
                'password' => 'nullable|min:6|confirmed'
            ];
        }
        protected function getUserForUpdate()
        {
            return $this->route('user');
        }
    }

}
