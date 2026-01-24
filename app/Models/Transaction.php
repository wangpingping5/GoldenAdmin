<?php 
namespace App\Models
{
    class Transaction extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'transactions';
        protected $fillable = [
            'user_id', 
            'payeer_id', 
            'system', 
            'value', 
            'type', 
            'summ', 
            'old',
            'new',
            'balance',
            'request_id', 
            'shop_id',
            'reason'
        ];
        public static function boot()
        {
            parent::boot();
            self::created(function($model)
            {
                $system = ($model->admin ? $model->admin->username : $model->system);
                $sysdata = '<a href="' . argon_route('statistics', ['system_str' => $system]) . '">' . $system . '</a>';
                if( $model->value ) 
                {
                    $sysdata .= $model->value;
                }
                if( $model->type == 'add' || $model->type == '' ) 
                {
                    $sum = '<span class="text-green">' . number_format(abs($model->summ), 0, '.', '') . '</span>';
                }
                else
                {
                    $sum = '<span class="text-red">' . number_format(abs($model->summ), 0, '.', '') . '</span>';
                }
                $usdata = '<a href="' . argon_route('statistics', ['user' => $model->user->username]) . '">' . $model->user->username . '</a>';
                /*try
                {
                    \Illuminate\Support\Facades\Redis::publish('Lives', json_encode([
                        'event' => 'NewLive', 
                        'data' => [
                            'type' => 'PayStat', 
                            'Name' => '', 
                            'Old' => number_format($model->old,0), 
                            'New' => number_format($model->new,0), 
                            'Game' => '', 
                            'User' => $usdata, 
                            'System' => $sysdata, 
                            'Sum' => $sum, 
                            'In' => ($model->type == 'add' ? number_format($model->summ,0) : ''), 
                            'Out' => ($model->type != 'add' ? number_format($model->summ,0) : ''), 
                            'Balance' => '', 
                            'Bet' => '', 
                            'Win' => '', 
                            'IN_GAME' => '', 
                            'IN_JPS' => '', 
                            'IN_JPG' => '', 
                            'Profit' => '', 
                            'user_id' => \Auth::id(), 
                            'shop_id' => $model->shop_id, 
                            'Date' => $model->created_at->format(config('app.date_time_format')), 
                            'domain' => request()->getHost()
                        ]
                    ]));
                }
                catch( \Predis\Connection\ConnectionException $e ) 
                {
                }*/
            });
        }
        public function admin()
        {
            return $this->hasOne('App\Models\User', 'id', 'payeer_id');
        }
        public function user()
        {
            return $this->hasOne('App\Models\User', 'id', 'user_id');
        }
        public function shop()
        {
            return $this->belongsTo('App\Models\Shop');
        }
        public function requestInfo()
        {
            return $this->hasOne('App\Models\WithdrawDeposit', 'id', 'request_id');
        }
    }

}
