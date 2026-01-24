<?php 
namespace App\Models
{
    class DealLog extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'deal_log';
        protected $fillable = [
            'date_time', 
            'user_id', 
            'partner_id',
            'balance_before',
            'balance_after',
            'bet', 
            'win', 
            'deal_profit',
            'mileage',
            'game', 
            'shop_id',
            'type',
            'deal_percent',
            'ggr_percent',
            'ggr_profit',
            'ggr_mileage',
            'category_id',
            'game_id'
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
        public function shop()
        {
            return $this->belongsTo('App\Models\Shop');
        }
        public function partner()
        {
            return $this->belongsTo('App\Models\User', 'partner_id');
        }
        public function game_item()
        {
            return $this->hasOne('App\Models\Game', 'name', 'game');
        }
        public function name_ico()
        {
            return explode(' ', $this->game)[0];
        }

        public function category()
        {
            return $this->belongsTo('App\Models\Category', 'category_id');
        }
    }

}
