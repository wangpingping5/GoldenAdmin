<?php 
namespace App\Models
{
    class BNGBonus extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'bngbonus';
        protected $fillable = [
            'player_id', 
            'total_bet', 
            'campaign',
            'game_id',
            'start_date',
            'end_date',
            'bonus_id',
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }

        public function user()
        {
            return $this->belongsTo('App\Models\User', 'player_id');
        }
    }
}
