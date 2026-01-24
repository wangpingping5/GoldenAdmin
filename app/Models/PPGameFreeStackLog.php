<?php 
namespace App\Models
{
    class PPGameFreeStackLog extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'ppgame_freestack_log';
        protected $fillable = [
            'id', 
            'user_id', 
            'game_id', 
            'freestack_id', 
            'odd', 
            'free_spin_count'
        ];
        
        public static function boot()
        {
            parent::boot();
        }
    }

}
