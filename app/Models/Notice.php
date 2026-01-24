<?php 
namespace App\Models
{
    class Notice extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'notice';
        protected $fillable = [
            'user_id', 
            'title', 
            'type', 
            'content', 
            'popup', 
            'order',
            'active',
            'date_time', 
        ];
        public $timestamps = false;
        public static function lists()
        {
            return [
                'user' => '회원',
                'partner' => '파트너',
                'all' => '전체'
            ];
        }
        public static function popups()
        {
            return [
                'all' => '팝업/일반',
                'popup' => '팝업',
                'general' => '일반',
            ];
        }
        public static function boot()
        {
            parent::boot();
        }

        public function writer()
        {
            return $this->hasOne('App\Models\User', 'id', 'user_id');
        }
    }

}
