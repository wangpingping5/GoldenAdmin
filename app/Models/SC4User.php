<?php 
namespace App\Models
{
    class SC4User extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'sc4_users';
        protected $fillable = [
            'sc4user_id', 
            'user_id'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
        public function user()
        {
            return $this->belongsTo('App\Models\User', 'user_id');
        }
    }

}
