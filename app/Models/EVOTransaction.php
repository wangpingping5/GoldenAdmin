<?php 
namespace App\Models
{
    class EVOTransaction extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'evotransaction';
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
