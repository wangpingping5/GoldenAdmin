<?php 
namespace App\Models
{
    class WithdrawDeposit extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'withdraw_deposit';
        protected $fillable = [
            'user_id', 
            'payeer_id', 
            'type', 
            'sum', 
            'status', 
            'shop_id',
            'created_at',
            'updated_at',
            'shop_id',
            'bank_name',
            'account_no',
            'recommender',
            'partner_type'
        ];

        const REQUEST = 0;
        const WAIT = 3;
        const DONE = 1;
        const CANCEL = 2;
        public static function statMsg()
        {
            return [
                self::REQUEST => '신청',
                self::WAIT => '대기',
                self::DONE => '승인',
                self::CANCEL => '취소'
            ];
        }
        public static function boot()
        {
            parent::boot();
            // self::created(function($model)
            // {
            //     $system = ($model->admin ? $model->admin->username : $model->system);
            //     $sysdata = '<a href="' . route('backend.statistics', ['system_str' => $system]) . '">' . $system . '</a>';
            //     if( $model->value ) 
            //     {
            //         $sysdata .= $model->value;
            //     }
            //     if( $model->type == 'add' || $model->type == '' ) 
            //     {
            //         $sum = '<span class="text-green">' . number_format(abs($model->summ), 4, '.', '') . '</span>';
            //     }
            //     else
            //     {
            //         $sum = '<span class="text-red">' . number_format(abs($model->summ), 4, '.', '') . '</span>';
            //     }
            //     $usdata = '<a href="' . route('backend.statistics', ['user' => $model->user->username]) . '">' . $model->user->username . '</a>';
            //     try
            //     {
            //         \Illuminate\Support\Facades\Redis::publish('Lives', json_encode([
            //             'event' => 'NewLive', 
            //             'data' => [
            //                 'type' => 'PayStat', 
            //                 'Name' => '', 
            //                 'Old' => '', 
            //                 'New' => '', 
            //                 'Game' => '', 
            //                 'User' => $usdata, 
            //                 'System' => $sysdata, 
            //                 'Sum' => $sum, 
            //                 'In' => ($model->type == 'add' ? $model->summ : ''), 
            //                 'Out' => ($model->type != 'add' ? $model->summ : ''), 
            //                 'Balance' => '', 
            //                 'Bet' => '', 
            //                 'Win' => '', 
            //                 'IN_GAME' => '', 
            //                 'IN_JPS' => '', 
            //                 'IN_JPG' => '', 
            //                 'Profit' => '', 
            //                 'user_id' => \Auth::id(), 
            //                 'shop_id' => $model->shop_id, 
            //                 'Date' => $model->created_at->format(config('app.date_time_format')), 
            //                 'domain' => request()->getHost()
            //             ]
            //         ]));
            //     }
            //     catch( \Predis\Connection\ConnectionException $e ) 
            //     {
            //     }
            // });
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
        public function shopStat()
        {
            return $this->hasOne('App\Models\ShopStat', 'request_id');
        }
        public function transaction()
        {
            return $this->hasOne('App\Models\Transaction', 'request_id');
        }

        public function bankinfo($bmask=false)
        {
            $info = $this->bank_name . ' - ' . $this->account_no . ' - ' . $this->recommender;
            if ($bmask)
            {
                $accno = $this->account_no;
                $recommender = $this->recommender;
                if ($accno != '')
                {
                    $maxlen = strlen($accno)>1?2:1;
                    $accno = '******' . substr($accno, -$maxlen);
                }
                if ($recommender != '')
                {
                    $recommender = mb_substr($recommender, 0, 1) . '***';
                }
                $info = $this->bank_name . ' - ' . $accno . ' - ' . $recommender;
            }
            return $info;
        }
        public function bankinfotoarry($bmask=false)
        {
            $info[] = [
                '은행' => $this->bank_name,
                '계좌정보' => $this->account_no,
                '예금주' => $this->recommender,
            ];
            if ($bmask)
            {
                $accno = $this->account_no;
                $recommender = $this->recommender;
                if ($accno != '')
                {
                    $maxlen = strlen($accno)>1?2:1;
                    $accno = '******' . substr($accno, -$maxlen);
                }
                if ($recommender != '')
                {
                    $recommender = mb_substr($recommender, 0, 1) . '***';
                }
                $info[] = [
                    '은행' => $this->bank_name,
                    '계좌정보' => $accno,
                    '예금주' => $recommender,
                ];
            }
            return $info;
        }
    }

}
