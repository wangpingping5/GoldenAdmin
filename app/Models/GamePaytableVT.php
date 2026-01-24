<?php 
namespace App\Models
{
    class GamePaytableVT extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'paytable_vt';
        protected $fillable = [
            'id', 
            'game_id', 
            'game_name', 
            'pay_table'
        ];
        
        public static function boot()
        {
            parent::boot();
        }
    }

}
