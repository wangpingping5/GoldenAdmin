<?php 
namespace App\Models
{
    class UserMemo extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'users_memo';
        protected $fillable = [
            'user_id',
            'writer_id',
            'memo', 
        ];
        public $timestamps = true;
        public static function boot()
        {
            parent::boot();
        }

        public function writer()
        {
            return $this->hasOne('App\Models\User', 'id', 'writer_id');
        }
        public function user()
        {
            return $this->hasOne('App\Models\User', 'id', 'user_id');
        }
    }

}
