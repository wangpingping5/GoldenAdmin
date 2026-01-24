<?php 
namespace App\Models
{
    class UserActivity extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'user_activity';
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
        public function userdata()
        {
            return $this->hasOne('App\Models\User', 'id', 'user_id');
        }
    }

}
