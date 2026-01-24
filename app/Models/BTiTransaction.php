<?php 
namespace App\Models
{
    class BTiTransaction extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'btitransaction';
        protected $fillable = [
            'id',
            'user_id', 
            'reserve_id', 
            'amount', //bet amount
            'balance', //user balance  
            'data',   
            'req_id',    
            'error_code',
            'error_message',
            'status',
            'bet_type_id',
            'bet_type_name',
            'purchase_id',
            'stats'  //1이면 10일후에 삭제,0이면 삭제하지 않음
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }

        public function user()
        {
            return $this->belongsTo('App\Models\User', 'user_id');
        }
    }
}
