<?php 
namespace App\Models
{
    class GameLaunch extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'game_launch';
        protected $fillable = [
            'user_id', 
            'provider', 
            'gamecode', 
            'launchUrl', 
            'finished', 
            'created_at',
            'argument'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }

}
