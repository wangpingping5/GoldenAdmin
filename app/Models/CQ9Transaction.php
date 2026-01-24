<?php 
namespace App\Models
{
    class CQ9Transaction extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'cq9transaction';
        protected $fillable = [
            'mtcode', 
            'data', 
            'refund',
            'timestamp'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }
}
