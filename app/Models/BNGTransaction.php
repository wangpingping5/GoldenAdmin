<?php 
namespace App\Models
{
    class BNGTransaction extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'bngtransaction';
        protected $fillable = [
            'uid', 
            'timestamp', 
            'data',
            'response',
            'refund'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }
}
