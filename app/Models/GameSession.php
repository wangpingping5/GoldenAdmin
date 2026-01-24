<?php 
namespace App\Models
{
    class GameSession extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'games_session';
        protected $fillable = [
            'user_id', 
            'game_id', 
            'session'
        ];
        public static function boot()
        {
            parent::boot();
        }
        public function shop()
        {
            return $this->belongsTo('App\Models\Shop');
        }
    }

}
