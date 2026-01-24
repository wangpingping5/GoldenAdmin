<?php 
namespace App\Models
{
    class Settings extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'settings';
        public $timestamps = false;
        protected $fillable = [
            'key', 
            'value',
        ];
        public static function boot()
        {
            parent::boot();
        }
    }

}
