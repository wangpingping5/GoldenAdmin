<?php 
namespace App\Models
{
    class PPGameVerifyLog extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'ppgame_verify_log';
        protected $fillable = [
            'id', 
            'user_id', 
            'game_id', 
            'bet',
            'date_time'
        ];
        
        public static function boot()
        {
            parent::boot();
        }
    }

}
