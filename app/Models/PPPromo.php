<?php 
namespace App\Models
{
    class PPPromo extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'pppromo';
        protected $fillable = [
            'active', 
            'racedetails', 
            'raceprizes',
            'racewinners',
            'tournamentdetails',
            'tournamentleaderboard',
            'games'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }
}
