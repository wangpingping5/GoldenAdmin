<?php 
namespace App\Models
{
    class Info extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'info';
        protected $fillable = [
            'link',   //공배팅수
            'title',  //공배팅 한도
            'text',  //공배팅 난수
            'roles',  //슬롯, 카지노
            'user_id'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
            self::deleting(function($model)
            {
                InfoShop::where('info_id', $model->id)->delete();
            });
        }
        public function user()
        {
            return $this->hasOne('App\Models\User', 'id', 'user_id');
        }
        public function shops()
        {
            return $this->hasMany('App\Models\InfoShop');
        }
        public function shops_info()
        {
            $results = [];
            if( $this->shops ) 
            {
                foreach( $this->shops as $shop ) 
                {
                    if( $shop->shop ) 
                    {
                        $results[] = $shop->shop->name;
                    }
                }
            }
            return implode(',', $results);
        }
    }

}
