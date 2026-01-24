<?php 
namespace App\Models
{
    class Session extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'sessions';
        public $timestamps = false;
        protected $fillable = [
            'user_id', 
            'last_activity',
        ];
        public static function boot()
        {
            parent::boot();
        }
    }

}
