<?php 
namespace App\Models
{
    class ShareBetSummary extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'sharebet_summary';
        protected $fillable = [
            'user_id',
            'date',           
            'bet',
            'win',
            'betlimit', 
            'winlimit',
            'deal_limit',
            'deal_share',
            'deal_out',
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


        public static function adjustment($user_id, $from, $to)
        {
            set_time_limit(0);
            $user = \App\Models\User::where('id', $user_id)->first();
            if (!$user)
            {
                return null;
            }
            $sharebetlogs = \App\Models\ShareBetlog::select('sharebet_log.*')->orderBy('sharebet_log.date_time', 'desc');;
            $sharebetlogs = $sharebetlogs->where('partner_id', $user_id);
            $sharebetlogs = $sharebetlogs->where('sharebet_log.date_time', '>=', $from);
            $sharebetlogs = $sharebetlogs->where('sharebet_log.date_time', '<=', $to);
            $adj = [
                'user_id' => $user_id,
                'bet' => $sharebetlogs->sum('bet'),
                'win' => $sharebetlogs->sum('win'),
                'betlimit' => $sharebetlogs->sum('betlimit'),
                'winlimit' => $sharebetlogs->sum('winlimit'),
                'deal_limit' => $sharebetlogs->sum('deal_limit'),
                'deal_share' => $sharebetlogs->sum('deal_share'),
                'deal_out' => 0,
            ];
            //
            $query = 'SELECT SUM(summ) as dealout FROM w_transactions WHERE user_id = '.$user_id.' AND created_at <="'.$to .'" AND created_at>="'. $from. '" AND type="deal_out"';

            $user_in_out = \DB::select($query);
            $adj['deal_out'] = $adj['deal_out'] + $user_in_out[0]->dealout??0;
            return $adj;
        }

        public static function summary($user_id, $day=null)
        {
            set_time_limit(0);
            $b_shop = false;
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
            $adj = [
                'user_id' => $user_id,
                'bet' => 0,
                'win' => 0,
                'betlimit' => 0,
                'winlimit' => 0,
                'deal_limit' => 0,
                'deal_share' => 0,
                'deal_out' => 0,
                'date' => $day
            ];

            if ($user->hasRole('group'))
            {
                $childusers = $user->childPartners();
                foreach ($childusers as $c)
                {
                    $childAdj = ShareBetSummary::summary($c, $day);
                    $adj['bet'] = $adj['bet'] + $childAdj['bet'];
                    $adj['win'] = $adj['win'] + $childAdj['win'];
                    $adj['betlimit'] = $adj['betlimit'] + $childAdj['betlimit'];
                    $adj['winlimit'] = $adj['winlimit'] + $childAdj['winlimit'];
                    $adj['deal_limit'] = $adj['deal_limit'] + $childAdj['deal_limit'];
                    $adj['deal_share'] = $adj['deal_share'] + $childAdj['deal_share'];
                    $adj['deal_out'] = $adj['deal_out'] + $childAdj['deal_out'];
                }
            }
            else
            {
                $adj = ShareBetSummary::adjustment($user_id, $from, $to);
                $adj['date'] = $day;
            }

            $dailysumm = ShareBetSummary::where(['user_id'=> $user_id, 'date' => $day])->first();
            if ($dailysumm)
            {
                $dailysumm->update($adj);
            }
            else
            {
                \App\Models\ShareBetSummary::create($adj);
            }
            return $adj;
        }

    }

}
