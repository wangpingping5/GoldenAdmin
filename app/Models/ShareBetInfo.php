<?php 
namespace App\Models
{
    class ShareBetInfo extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'sharebet_info';
        protected $fillable = [
            'partner_id', 
            'share_id', 
            'minlimit', 
            'limit_info',
            'category_id', 
        ];
        public $timestamps = false;
        const BET_TYPES = [
            'baccarat'=> [
                'Baccarat_Player' => 0,
                'Baccarat_Banker' => 0,
                'Baccarat_Tie' => 0,
                'Baccarat_PlayerPair' => 0,
                'Baccarat_BankerPair' => 0,
            ]
        ];
        public static function boot()
        {
            parent::boot();
        }

        public function partner()
        {
            return $this->hasOne('App\Models\User', 'id', 'partner_id');
        }
        public function category()
        {
            return $this->hasOne('App\Models\Category', 'id', 'category_id');
        }
    }

}
