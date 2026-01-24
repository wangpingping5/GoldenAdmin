<?php 
namespace App\Models
{
    class ProviderInfo extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'provider_info';
        protected $fillable = [
            'user_id', 
            'provider', 
            'config',
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }
}
