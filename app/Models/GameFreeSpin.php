<?php 
namespace App\Models
{
    class GameFreeSpin extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'game_freespin';
        protected $fillable = [
            'id', 
            'game_id', 
            'odd', 
            'free_spin_type', 
            'free_spin_count', 
            'free_spin_stack'
        ];
        
        public static function boot()
        {
            parent::boot();
        }
    }

}
