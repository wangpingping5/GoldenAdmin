<?php 
namespace App\Models
{
    class GameLog extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'game_log';
        protected $fillable = [
            'time', 
            'game_id', 
            'user_id', 
            'ip', 
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
