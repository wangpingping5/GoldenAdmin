<?php 
namespace App\Models
{
    class ATATransaction extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'atatransaction';
        protected $fillable = [
            'reference', 
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
