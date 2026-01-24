<?php 
namespace App\Models
{
    class PPTransaction extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'pptransaction';
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
