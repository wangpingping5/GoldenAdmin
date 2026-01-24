<?php 
namespace App\Models
{
    class GameFreeSpinDD extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'game_freespin_dd';
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
