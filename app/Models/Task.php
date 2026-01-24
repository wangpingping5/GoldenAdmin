<?php 
namespace App\Models
{
    class Task extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'tasks';
        protected $fillable = [
            'category', 
            'user_id', 
            'action', 
            'item_id', 
            'details', 
            'ip_address', 
            'user_agent', 
            'finished',
            'created_at'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }

}
