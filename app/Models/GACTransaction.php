<?php 
namespace App\Models
{
    class GACTransaction extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'gactransaction';
        protected $fillable = [
            'user_id', 
            'game_id', 
            'betInfo', //when placeBet is betInfo,  when betResult is betId
            'type', //1 - placeBet, 2 - betResult
            'data',
            'response',
            'status', // 0 - placeBet is not processed, 1 - placeBet is processed with betResult
            'date_time'
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
