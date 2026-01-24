<?php 
namespace App\Models
{
    class BankStat extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'bank_stat';
        protected $fillable = [
            'name', 
            'user_id', 
            'type', 
            'sum', 
            'old', 
            'new', 
            'shop_id'
        ];
        public function user()
        {
            return $this->belongsTo('App\Models\User', 'user_id');
        }
        public function shop()
        {
            return $this->belongsTo('App\Models\Shop');
        }
        public static function boot()
        {
            parent::boot();
            /*self::created(function($model)
            {
                try
                {
                    \Illuminate\Support\Facades\Redis::publish('Lives', json_encode([
                        'event' => 'NewLive', 
                        'data' => [
                            'type' => 'BankStat', 
                            'Name' => $model->name, 
                            'Old' => $model->old, 
                            'New' => $model->new, 
                            'Game' => '', 
                            'User' => $model->user->username, 
                            'System' => '', 
                            'Sum' => number_format($model->sum, 2, '.', ''), 
                            'In' => ($model->type == 'add' ? $model->sum : ''), 
                            'Out' => ($model->type != 'add' ? $model->sum : ''), 
                            'Balance' => '', 
                            'Bet' => '', 
                            'Win' => '', 
                            'IN_GAME' => '', 
                            'IN_JPS' => '', 
                            'IN_JPG' => '', 
                            'Profit' => '', 
                            'user_id' => \Auth::id(), 
                            'shop_id' => \Auth::user()->shop_id, 
                            'Date' => date(config('app.date_time_format')), 
                            'domain' => request()->getHost()
                        ]
                    ]));
                }
                catch( \Predis\Connection\ConnectionException $e ) 
                {
                }
            });*/
        }
    }

}
