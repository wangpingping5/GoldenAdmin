<?php 
namespace App\Models
{
    class JPGDuofu extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'jpg_duofu';
        public $timestamps = false;
        protected $fillable = [
            'id', 
            'name', 
            'balance', 
            'start_balance', 
            'pay_sum',
            'shop_id'
        ];
        
        public static function boot()
        {
            parent::boot();
        }
    }

}
