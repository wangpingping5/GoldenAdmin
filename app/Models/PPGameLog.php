<?php 
namespace App\Models
{
    class PPGameLog extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'pp_game_log';
        protected $fillable = [
            'time', 
            'game_id', 
            'user_id', 
            'str', 
            'shop_id',
            'roundid'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }

}
