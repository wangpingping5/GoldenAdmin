<?php 
namespace App\Models
{
    class UserDailySummary extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'userdaily_summary';
        protected $fillable = [
            'user_id', 
            'date',
            'totalbet',
            'totalwin',
            'type',
            'gametype',
            'created_at',
            'updated_at'
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

        public function prevDay()
        {
            return $this->hasOne('App\Models\UserDailySummary', 'user_id', 'user_id')->where('date', date('Y-m-d', strtotime("$this->date -1 days")));        
        }
        public static function adjustment($from, $to)
        {
            set_time_limit(0);
            $adjs = [];
            $query = 'SELECT user_id, SUM(bet) as totalbet, SUM(win) as totalwin, type as gametype FROM w_stat_game WHERE date_time <="'.$to .'" AND date_time>="'. $from. '" group by user_id, type';
            $adjs = \DB::select($query);
            
            return $adjs;
        }
        public static function summary($user_id, $day=null)
        {
            set_time_limit(0);
            $user = \App\Models\User::where('id', $user_id)->first();
            if (!$user)
            {
                return;
            }
            if (!$day)
            {
                $day = date("Y-m-d", strtotime("-1 days"));
            }
            
            $from =  $day . " 0:0:0";
            $to = $day . " 23:59:59";
            $adjs = UserDailySummary::adjustment($from, $to);
            foreach($adjs as $adj){
                $adj->date = $day;
                $adj->type = 'daily';
                $dailysumm = \App\Models\UserDailySummary::where(['user_id'=> $adj->user_id, 'date' => $day, 'gametype'=>$adj->gametype, 'type'=>'daily'])->first();
                $adj = json_decode(json_encode($adj), true);
                if ($dailysumm)
                {
                    $dailysumm->update($adj);
                }
                else
                {
                    \App\Models\UserDailySummary::create($adj);
                }
            }
        }

        public static function summary_today($user_id)
        {
            set_time_limit(0);
            $user = \App\Models\User::where('id', $user_id)->first();
            if (!$user)
            {
                return;
            }
            $day = date("Y-m-d");            
            $from =  $day . " 0:0:0";
            $to = $day . " 23:59:59";
            $adjs = UserDailySummary::adjustment($from, $to);
            foreach($adjs as $adj){
                $adj->date = $day;
                $adj->type = 'today';
                $dailysumm = \App\Models\UserDailySummary::where(['user_id'=> $adj->user_id, 'date' => $day, 'gametype'=>$adj->gametype, 'type'=>'today'])->first();
                $adj = json_decode(json_encode($adj), true);
                if ($dailysumm)
                {
                    $dailysumm->update($adj);
                }
                else
                {
                    \App\Models\UserDailySummary::create($adj);
                }
            }
        }
        public static function rangebetwin($user_id, $date){
            $param = explode('~', $date);
            if(!is_array($param) || count($param) < 2){
                return redirect()->back()->withErrors('찾을수 없습니다.');
            }
            $summary = \App\Models\UserDailySummary::where('date', '>=', $param[0])->where('date', '<=', $param[1])->where('user_id', $user_id);
            $betwin = [
                'table' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                ],
                'slot' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                ],
                'sports' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                ],
                'card' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                ],
                'pball' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                ],
                'total' => [
                    'totalbet' => 0,
                    'totalwin' => 0,
                ],
            ];
            

            foreach ($betwin as $type => $tdata)
            {
                if ($type != 'total')
                {
                    foreach ($tdata as $field => $tvalue)
                    {
                        $sum = (clone $summary)->where('gametype', $type)->sum($field);
                        $betwin[$type][$field] = $betwin[$type][$field] + $sum;
                        $betwin['total'][$field] = $betwin['total'][$field] + $sum;
                    }
                }
            }
            return $betwin;
        }
        public function calcInOut($user_id, $date)
        {
            $adj = [
                'totalin' => 0,
                'totalout' => 0,
                'moneyin' => 0,
                'moneyout' => 0,
            ];
            $param = explode('~', $date);
            if(!is_array($param) || count($param) < 2){
                return redirect()->back()->withErrors('찾을수 없습니다.');
            }
            $from = $param[0] . ' 0:0:0';
            $to = $param[1] . ' 23:59:59';
            $query = 'SELECT SUM(summ) as totalin FROM w_transactions WHERE user_id='. $user_id .' AND created_at <="'.$to .'" AND created_at>="'. $from. '" AND type="add" AND request_id IS NOT NULL';
            $user_in_out = \DB::select($query);
            $adj['totalin'] = $adj['totalin'] + $user_in_out[0]->totalin??0;

            $query = 'SELECT SUM(summ) as totalout FROM w_transactions WHERE user_id='. $user_id .' AND created_at <="'.$to .'" AND created_at>="'. $from. '" AND type="out" AND request_id IS NOT NULL';
            $user_in_out = \DB::select($query);
            $adj['totalout'] = $adj['totalout'] + $user_in_out[0]->totalout??0;

            $query = 'SELECT SUM(summ) as moneyin FROM w_transactions WHERE user_id='. $user_id .' AND created_at <="'.$to .'" AND created_at>="'. $from. '" AND type="add" AND request_id IS NULL';
            $user_in_out = \DB::select($query);
            $adj['moneyin'] = $adj['moneyin'] + $user_in_out[0]->moneyin??0;

            $query = 'SELECT SUM(summ) as moneyout FROM w_transactions WHERE user_id='. $user_id .' AND created_at <="'.$to .'" AND created_at>="'. $from. '" AND type="out" AND request_id IS NULL';
            $user_in_out = \DB::select($query);
            $adj['moneyout'] = $adj['moneyout'] + $user_in_out[0]->moneyout??0;

            return $adj;
        }
    }

}
