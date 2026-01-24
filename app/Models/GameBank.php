<?php 
namespace App\Models
{
    class GameBank extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'game_bank';
        protected $fillable = [
            'slots', 
            'slots_01',     // <= 500
            'slots_02',     // <= 1000
            'slots_03',     // <= 2000
            'slots_04',     // <= 5000
            'slots_05',     // > 5000
            'little', 
            'table_bank', 
            'fish', 
            'bonus', 
            'shop_id', 
            'game_id'
        ];
        public static function boot()
        {
            parent::boot();
        }
        public function shop()
        {
            return $this->belongsTo('App\Models\Shop');
        }
        public function game()
        {
            return $this->belongsTo('App\Models\Game');
        }
    }

}
