<?php 
namespace App\Models
{
    class ShopStat extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'shops_stat';
        protected $fillable = [
            'shop_id', 
            'user_id', 
            'type', 
            'sum', 
            'old',
            'new',
            'balance',
            'date_time',
            'request_id',
            'reason'
        ];
        public $timestamps = false;
        public function shop()
        {
            return $this->belongsTo('App\Models\Shop', 'shop_id');
        }
        public function user()
        {
            return $this->belongsTo('App\Models\User', 'user_id');
        }
        public static function boot()
        {
            parent::boot();
        }

        public function requestInfo()
        {
            return $this->hasOne('App\Models\WithdrawDeposit', 'id', 'request_id');
        }
    }
    

}
