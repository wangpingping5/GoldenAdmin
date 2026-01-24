<?php 
namespace App\Models
{
    class IPBlockList extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'ip_blocklist';
        protected $fillable = [
            'user_id', 
            'ip_address'
        ];
        public static function boot()
        {
            parent::boot();
        }
        public function user()
        {
            return $this->belongsTo('App\Models\User', 'user_id');
        }
    }
}
