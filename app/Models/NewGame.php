<?php 
namespace App\Models
{
    class NewGame extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'new_games';
        protected $fillable = [
            'provider',
            'gameid',
            'type',
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }

}
