<?php 
namespace App\Models
{
    class GPGameTrend extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'gp_game_trend';
        protected $fillable = [
            'game_id',
            'dno', 
            'rno', 
            'rt', 
            's', 
            'sDate', 
            'eDate',
            'p',
            'a',
            'e',
            'sl',
            'PartialResultTime'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }

}
