<?php 
namespace App\Models
{
    class JPG extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'jpg';
        protected $fillable = [
            'date_time', 
            'name', 
            'balance', 
            'fake_balance', 
            'pay_sum', 
            'pay_sum_new', 
            'start_balance', 
            'percent', 
            'view', 
            'shop_id'
        ];
        public static $values = [
            'percent' => [
                '1.00', 
                '0.90', 
                '0.80', 
                '0.70', 
                '0.60', 
                '0.50', 
                '0.40', 
                '0.30', 
                '0.20', 
                '0.10'
            ]
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
        public function add_jps($user = false, $sum, $type = 'add')
        {
            $shop = Shop::find($this->shop_id);
            $old = $this->balance;
            // $shop_id = $shop->id;
            if( !$user ) 
            {
                $user = User::where('role_id', 4)->whereHas('rel_shops', function($query) use ($shop)
                {
                    $query->where('shop_id', $shop->id);
                })->first();
            }
            if( !$shop ) 
            {
                return [
                    'success' => false, 
                    'text' => trans('app.wrong_shop')
                ];
            }
            if( !$sum ) 
            {
                return [
                    'success' => false, 
                    'text' => trans('app.wrong_sum')
                ];
            }

            if( $type == 'out' && $this->balance < $sum ) 
            {
                return [
                    'success' => false, 
                    'text' => 'Not enough money in the jackpot balance "' . $this->name . '". Only ' . $this->balance
                ];
            }
            
            $sum = ($type == 'out' ? -1 * $sum : $sum);
            if( $this->balance + $sum < 0 ) 
            {
                return [
                    'success' => false, 
                    'text' => 'Balance < 0'
                ];
            }
            if ($this->pay_sum > 0 &&  $this->balance + $sum > $this->pay_sum)
            {
                return [
                    'success' => false, 
                    'text' => 'Balance > Trigger'
                ];
            }
            if ($this->pay_sum_new > 0 &&  $this->balance + $sum > $this->pay_sum_new)
            {
                return [
                    'success' => false, 
                    'text' => 'Balance > Trigger'
                ];
            }
            $this->update(['balance' => $this->balance + $sum]);
            // $shop->update(['balance' => $shop->balance - $sum]);
            //no need to update because the jpg is not shop's balance
            if( $user ) 
            {
                $open_shift = OpenShift::where([
                    'user_id' => $user->id, 
                    'end_date' => null
                ])->first();
                if( $open_shift ) 
                {
                    if( $type == 'out' ) 
                    {
                        $open_shift->increment('money_out', abs($sum));
                    }
                    else
                    {
                        $open_shift->increment('money_in', abs($sum));
                    }
                }
            
                BankStat::create([
                    'name' => 'JPS ' . $this->id, 
                    'user_id' => $user->id, 
                    'type' => $type, 
                    'sum' => abs($sum), 
                    'old' => $old, 
                    'new' => $this->balance, 
                    'shop_id' => $shop->id
                ]);
            }
            return ['success' => true];
        }
    }

}
