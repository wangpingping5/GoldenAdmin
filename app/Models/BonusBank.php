<?php 
namespace App\Models
{
    class BonusBank extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'bonus_bank';
        protected $fillable = [
            'master_id', 
            'bank', 
            'bank_01',     // <= 500
            'bank_02',     // <= 1000
            'bank_03',     // <= 2000
            'bank_04',     // <= 5000
            'bank_05',     // > 5000
            'game_id',
            'max_bank'
        ];
        public static function boot()
        {
            parent::boot();
        }

        public function master()
        {
            return $this->belongsTo('App\Models\User', 'master_id');
        }
        public function game()
        {
            return $this->belongsTo('App\Models\Game', 'game_id');
        }
    }

}
