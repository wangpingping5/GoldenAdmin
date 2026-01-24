<?php 
namespace App\Models
{
    class Message extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'message';
        protected $fillable = [
            'user_id',
            'writer_id',
            'ref_id',
            'count',
            'type', //0 - 일반문의, 1 - 계좌문의
            'title', 
            'content', 
            'created_at', 
            'read_at',
        ];
        public $timestamps = false;
        const SYSTEM_MSG_ID = 1;
        const GROUP_MSG_ID = 99999;
        const LIVE_MSG_ID = 99998;
        const RECV_NAME = [
            self::SYSTEM_MSG_ID => '시스템',
            self::GROUP_MSG_ID => '전체회원'
        ];

        public static function type()
        {
            return [
                'all' => trans('TotalUser'),
                'live' => trans('ConnectedUser'),
                'shop' => trans('SpecialPartner'),
            ];
        }

        public static function boot()
        {
            parent::boot();
        }

        public function writer()
        {
            return $this->hasOne('App\Models\User', 'id', 'writer_id');
        }
        public function user()
        {
            return $this->hasOne('App\Models\User', 'id', 'user_id');
        }
        public function refs()
        {
            return $this->hasMany('App\Models\Message', 'ref_id', 'id');
        }
    }

}
