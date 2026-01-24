<?php 
namespace App\Models
{
    class BNGGameLog extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'bnggame_log';
        protected $fillable = [
            'type', 
            'transaction_id', 
            'player_id', 
            'brand', 
            'tag', 
            'game_id', 
            'game_name', 
            'currency', 
            'mode', 
            'bet', 
            'win', 
            'profit', 
            'outcome',
            'round_id', 
            'round_started', 
            'round_finished', 
            'balance_before', 
            'balance_after', 
            'status', 
            'exceed_code', 
            'exceed_message', 
            'is_bonus', 
            'c_at',
            'log'
        ];
        
        public static function boot()
        {
            parent::boot();
        }
    }

}
