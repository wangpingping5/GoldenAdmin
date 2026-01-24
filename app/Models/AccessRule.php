<?php 
namespace App\Models
{
    class AccessRule extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'access_rule';
        protected $fillable = [
            'user_id', 
            'ip_address', 
            'user_agent', 
            'allow_ipv6',
            'check_cloudflare'
        ];
        public static function boot()
        {
            parent::boot();
        }
    }

}
