<?php 
namespace App\Models
{
    class GPGameBet extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'gp_game_bet';
        protected $fillable = [
            'game_id',
            'user_id',
            'p',
            'dno', 
            'bet_id',
            'rt', 
            'o',
            'amount',
            'win',
            'status'
        ];
        public static function boot()
        {
            parent::boot();
        }
    }

}
