<?php 
namespace App\Models
{
    class HBNTransaction extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'hbntransaction';
        protected $fillable = [
            'transferid', 
            'gameinstanceid',
            'timestamp', 
            'data',
            'refund'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }
}
