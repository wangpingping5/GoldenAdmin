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
                    $sum = '<span class="text-green">' . number_format(abs($model->summ), 2, '.', '') . '</span>';
                }
                else
                {
                    $sum = '<span class="text-red">' . number_format(abs($model->summ), 2, '.', '') . '</span>';
                }
                $usdata = '<a href="' . argon_route('statistics', ['user' => $model->user->username]) . '">' . $model->user->username . '</a>';
                
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
