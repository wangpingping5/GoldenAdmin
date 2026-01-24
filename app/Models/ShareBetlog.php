<?php 
namespace App\Models
{
    class ShareBetlog extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'sharebet_log';
        protected $fillable = [
            'user_id',
            'date_time',
            'game',
            'partner_id',
            'share_id', 
            'bet',
            'win',
            'betlimit', 
            'winlimit',
            'deal_percent',
            'deal_limit',
            'deal_share',
            'shop_id',
            'category_id', 
            'game_id',
            'stat_id'
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
        public function partner()
        {
            return $this->belongsTo('App\Models\User', 'partner_id');
        }
        public function shareuser()
        {
            return $this->belongsTo('App\Models\User', 'share_id');
        }
        public function category()
        {
            return $this->belongsTo('App\Models\Category', 'category_id');
        }

    }

}
