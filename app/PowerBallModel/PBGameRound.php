<?php 
namespace App\PowerBallModel;
{
    class PBGameRound extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'pb_game_rounds';
        protected $fillable = [
            'game_id', 
            'ground_no', 
            'dround_no', 
            'balls', 
            'result',
            'status',
            's_time',
            'e_time',
            'remindtime',
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }

}
