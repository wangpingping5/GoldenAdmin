<?php 
namespace App\PowerBallModel;
{
    class PBGameResult extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'pb_game_results';
        protected $fillable = [
            'game_id', 
            'category', 
            'rt_no', 
            'rt_rate', 
            'rt_name', 
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }

}
