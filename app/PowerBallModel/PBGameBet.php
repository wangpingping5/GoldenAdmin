<?php 
namespace App\PowerBallModel;
{
    class PBGameBet extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'pb_game_bets';
        protected $fillable = [
            'user_id', 
            'game_id', 
            'ground_no',
            'bet_id',
            'result',
            'rate', 
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
