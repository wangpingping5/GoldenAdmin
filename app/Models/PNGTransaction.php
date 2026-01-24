<?php 
namespace App\Models
{
    class PNGTransaction extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'pngtransaction';
        protected $fillable = [
            'transactionId', 
            'timestamp', 
            'data',
            'response'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }
}
