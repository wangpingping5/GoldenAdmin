<?php 
namespace App\Models
{
    use Illuminate\Support\Facades\Log;
    class CategorySummary extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'category_summary';
        protected $fillable = [
            'user_id',
            'category_id', 
            'shop_id',
            'date',
            'totalbet',
            'totalwin',
            'totaldealbet',
            'totaldealwin',
            'totalcount',
            'total_deal',
            'total_mileage',
            'type',
            'updated_at',
            'total_ggr',
            'total_ggr_mileage'
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
        public function category()
        {
            return $this->belongsTo('App\Models\Category', 'category_id');
        }

        public static function adjustment($user_id, $from, $to)
        {
            $categories = \App\Models\Category::where(['shop_id' => 0, 'site_id' => 0]);
            $categories = $categories->get();
            $adjustments = [];
            $user = \App\Models\User::where('id', $user_id)->first();
            if (!$user)
            {
                return null;
            }

            if (!$user->hasRole('manager'))
            {
                return null;
            }

            foreach ($categories as $cat)
            {
                $adj = [
                    'user_id' => $user_id,
                    'category_id' => $cat->id,
                    'shop_id' => $user->shop_id,
                    // 'category' => $cat->title,
                    'totalbet' => 0,
                    'totalwin' => 0,
                    'totaldealbet' => 0,
                    'totaldealwin' => 0,
                    'totalcount' => 0,
                    'total_deal' => 0,
                    'total_mileage' => 0,
                    'total_ggr' => 0,
                    'total_ggr_mileage' => 0,
                    'games' => []
                ];
                if ($cat->provider != null)
                {
                    $games = null;
                    try {
                        $games = call_user_func('\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($cat->provider) . 'Controller::getgamelist', $cat->href);
                    }
                    catch (\Exception $e)
                    {
                        Log::error('getgamelist at category adjustment error');
                    }
                    if ($games){
                        foreach ($games as $game)
                        {
                            $adj_game = [
                                'user_id' => $user_id,
                                'category_id' => $cat->id,
                                'game_id' => $game['gamecode'],
                                'shop_id' => $user->shop_id,
                                'name' => $game['name'],
                                'totalwin' => 0,
                                'totalbet' => 0,
                                'totaldealbet' => 0,
                                'totaldealwin' => 0,
                                'totalcount' => 0,
                                'total_deal' => 0,
                                'total_mileage' => 0,
                                'total_ggr' => 0,
                                'total_ggr_mileage' => 0,
                            ];
                            $adj['games'][] = $adj_game;
                        }
                    }
                }
                else
                {
                    $gameIds = \App\Models\GameCategory::where('category_id', $cat->id)->pluck('game_id')->toArray();
                    $games = \App\Models\Game::whereIn('id', $gameIds);
                    $games = $games->get();
                    foreach ($games as $game)
                    {
                        $adj_game = [
                            'user_id' => $user_id,
                            'category_id' => $cat->id,
                            'game_id' => $game->id,
                            'shop_id' => $user->shop_id,
                            'name' => $game->name,
                            'totalwin' => 0,
                            'totalbet' => 0,
                            'totaldealbet' => 0,
                            'totaldealwin' => 0,
                            'totalcount' => 0,
                            'total_deal' => 0,
                            'total_mileage' => 0,
                            'total_ggr' => 0,
                            'total_ggr_mileage' => 0,
                        ];
                        $adj['games'][] = $adj_game;
                    }
                }
                $adjustments[] = $adj;
            }

            $shop_id = $user->shop_id;
            $query = 'SELECT game, game_id, category_id, SUM(bet) as totalbet, SUM(win) as totalwin, COUNT(*) as betcount FROM w_stat_game WHERE shop_id ='. $shop_id .' AND status=1 AND date_time <="'.$to .'" AND date_time>="'. $from. '" GROUP BY game_id, category_id';
            $stat_games = \DB::select($query);
            
            $query = 'SELECT game, game_id, category_id, SUM(bet) as totaldealbet, SUM(win) as totaldealwin, SUM(deal_profit) as total_deal, SUM(mileage) as total_mileage, SUM(ggr_profit) as total_ggr, SUM(ggr_mileage) as total_ggr_mileage FROM w_deal_log WHERE type="shop" AND shop_id =' . $shop_id . ' AND date_time <="'.$to .'" AND date_time>="'. $from. '" GROUP BY game_id, category_id';
            $deal_logs = \DB::select($query);


            foreach($stat_games as $stat_game) {
                $bfound_cat = false;
                foreach ($adjustments as $i => $adj)
                {
                    if($stat_game->category_id == $adj['category_id'])
                    {
                        $adj['totalbet'] = $adj['totalbet'] + $stat_game->totalbet;
                        $adj['totalwin'] = $adj['totalwin'] + $stat_game->totalwin;
                        $adj['totalcount'] = $adj['totalcount'] + $stat_game->betcount;
                        $bfound_cat = true;
                    }

                    $bfound_game = false;
                    foreach ($adj['games'] as $j => $game)
                    {
                        if ($stat_game->game_id == $game['game_id'])
                        {
                            $game['totalbet'] = $game['totalbet'] + $stat_game->totalbet;
                            $game['totalwin'] = $game['totalwin'] + $stat_game->totalwin;
                            $game['totalcount'] = $game['totalcount'] + $stat_game->betcount;
                            $adj['games'][$j] = $game;
                            $bfound_game = true;
                            break;
                        }
                    }
                    if ($bfound_cat){
                        $adjustments[$i] = $adj;
                        break; 
                    }
                }
            }

            foreach($deal_logs as $deal_log) {
                $bfound_cat = false;
                foreach ($adjustments as $i => $adj)
                {
                    if($deal_log->category_id == $adj['category_id'])
                    {
                        $adj['totaldealbet'] = $adj['totaldealbet'] + $deal_log->totaldealbet;
                        $adj['totaldealwin'] = $adj['totaldealwin'] + $deal_log->totaldealwin;
                        $adj['total_deal'] = $adj['total_deal'] + $deal_log->total_deal;
                        $adj['total_mileage'] = $adj['total_mileage'] + $deal_log->total_mileage;
                        $adj['total_ggr'] = $adj['total_ggr'] + $deal_log->total_ggr;
                        $adj['total_ggr_mileage'] = $adj['total_ggr_mileage'] + $deal_log->total_ggr_mileage;
                        $bfound_cat = true;
                    }
                    $bfound_game = false;
                    foreach ($adj['games'] as $j => $game)
                    {
                        if($deal_log->game_id == $game['game_id'] )
                        {
                            $game['totaldealbet'] = $game['totaldealbet'] + $deal_log->totaldealbet;
                            $game['totaldealwin'] = $game['totaldealwin'] + $deal_log->totaldealwin;
                            $game['total_deal'] = $game['total_deal'] + $deal_log->total_deal;
                            $game['total_mileage'] = $game['total_mileage'] + $deal_log->total_mileage;
                            $game['total_ggr'] = $game['total_ggr'] + $deal_log->total_ggr;
                            $game['total_ggr_mileage'] = $game['total_ggr_mileage'] + $deal_log->total_ggr_mileage;

                            $bfound_game = true;
                            $adj['games'][$j] = $game;
                            break;
                        }
                    }
                    if ($bfound_cat){ 
                        $adjustments[$i] = $adj;
                        break; 
                    }
                }
            }

            //remove total bet is zero
            $filtered_adjustment = [];

            foreach ($adjustments as $index => $adj)
            {
                if ($adj['totalbet'] > 0)
                {
                    if ($user->deal_percent == 0)
                    {
                        $adj['totaldealbet'] = $adj['totalbet'];
                        $adj['totaldealwin'] = $adj['totalwin'];
                    }
                    $filtered_games = [];
                    foreach ($adj['games'] as $game)
                    {
                        if ($game['totalbet'] > 0)
                        {
                            if ($user->deal_percent == 0)
                            {
                                $game['totaldealbet'] = $game['totalbet'];
                                $game['totaldealwin'] = $game['totalwin'];
                            }
                            $filtered_games[] = $game;
                        }
                    }
                    usort($filtered_games, function($element1, $element2)
                    {
                        return $element2['totalbet'] - $element1['totalbet'];
                    });

                    $adj['games'] = $filtered_games;
                    $filtered_adjustment[] = $adj;
                }
            }
            return $filtered_adjustment;
        }
        public static function summary_month($shop_id, $month=null)
        {
            return;
        }

        public static function summary($user_id, $day=null, $start=null, $end=null)
        {
            set_time_limit(0);
            $user = \App\Models\User::where('id', $user_id)->first();
            if (!$user)
            {
                return null;
            }
            if (!$day)
            {
                $day = date("Y-m-d", strtotime("-1 days"));
            }
            $from =  $day . " 0:0:0";
            if ($start != null)
            {
                $from =  $day . ' ' . $start;
            }
            $to = $day . " 23:59:59";
            if ($end != null)
            {
                $to =  $day . ' ' . $end;
            }

            $adj_cats = [];
            if(!$user->hasRole('manager')){
                //repeat child partners
                $childusers = $user->childPartners(); //하위 파트너들 먼저 정산

                foreach ($childusers as $c)
                {
                    $childAdj = CategorySummary::summary($c, $day, $start, $end);
                    $updated_cats = $adj_cats;
                    foreach ($childAdj as $cadj)
                    {
                        $adj = [
                            'user_id' => $user_id,
                            'category_id' => $cadj['category_id'],
                            'shop_id' => 0,
                            'totalbet' => $cadj['totalbet'],
                            'totalwin' => $cadj['totalwin'],
                            'totaldealbet' => $cadj['totaldealbet'],
                            'totaldealwin' => $cadj['totaldealwin'],
                            'totalcount' => $cadj['totalcount'],
                            'total_deal' => 0,
                            'total_mileage' => 0,
                            'total_ggr' => 0,
                            'total_ggr_mileage' => 0,
                            'games' => $cadj['games']
                        ];

                        $bfound_cats = false;

                        foreach ($adj_cats as $i => $uadj)
                        {
                            if ($uadj['category_id'] == $cadj['category_id'])
                            {
                                $adj['totalbet'] = $uadj['totalbet'] + $adj['totalbet'];
                                $adj['totalwin'] = $uadj['totalwin'] + $adj['totalwin'];
                                $adj['totaldealbet'] = $uadj['totaldealbet'] + $adj['totaldealbet'];
                                $adj['totaldealwin'] = $uadj['totaldealwin'] + $adj['totaldealwin'];
                                $adj['totalcount'] = $uadj['totalcount'] + $adj['totalcount'];
                                

                                //for games
                                $updated_games = $uadj['games'];
                                foreach ($cadj['games'] as $cgame)
                                {
                                    $adj_game = [
                                        'user_id' => $user_id,
                                        'category_id' => $cadj['category_id'],
                                        'game_id' => $cgame['game_id'],
                                        'shop_id' => 0,
                                        'name' => $cgame['name'],
                                        'totalwin' => $cgame['totalwin'],
                                        'totalbet' => $cgame['totalbet'],
                                        'totaldealbet' => $cgame['totaldealbet'],
                                        'totaldealwin' => $cgame['totaldealwin'],
                                        'totalcount' => $cgame['totalcount'],
                                        'total_deal' => 0,
                                        'total_mileage' => 0,
                                        'total_ggr' => 0,
                                        'total_ggr_mileage' => 0,
                                    ];
                                    $bfound_games = false;
                                    foreach ($uadj['games'] as $j => $ugame)
                                    {
                                        if ($ugame['game_id'] == $cgame['game_id'])
                                        {
                                            $adj_game['totalbet'] = $ugame['totalbet'] + $adj_game['totalbet'];
                                            $adj_game['totalwin'] = $ugame['totalwin'] + $adj_game['totalwin'];
                                            $adj_game['totaldealbet'] = $ugame['totaldealbet'] + $adj_game['totaldealbet'];
                                            $adj_game['totaldealwin'] = $ugame['totaldealwin'] + $adj_game['totaldealwin'];
                                            $adj_game['totalcount'] = $ugame['totalcount'] + $adj_game['totalcount'];
                                            $bfound_games = true;
                                            $updated_games[$j] = $adj_game;
                                            break;
                                        }
                                    }
                                    if (!$bfound_games)
                                    {
                                        $updated_games[] = $adj_game;
                                    }
                                }

                                $adj['games'] = $updated_games;
                                $bfound_cats = true;
                                $updated_cats[$i] = $adj;
                                break;
                            }
                        }

                        if (!$bfound_cats) //add new category summary
                        {
                            $updated_cats[] = $adj;
                        }
                    }

                    $adj_cats = $updated_cats;
                }

                if ($user->hasRole(['admin','group']))
                {
                    $query = 'SELECT "" as game, "" as game_id, "" as category_id, 0 as total_deal, 0 as total_mileage, 0 as totaldealbet, 0 as totaldealwin';
                }
                else if ($user->hasRole(['comaster']))
                {
                    $masters = $user->childPartners();
                    if (count($masters) > 0){
                        $query = 'SELECT game, game_id, category_id, SUM(bet) as totaldealbet, SUM(win) as totaldealwin, 0 as total_deal, SUM(deal_profit) as total_mileage,  0 as total_ggr, SUM(ggr_mileage) as total_ggr_mileage FROM w_deal_log WHERE type="partner" AND partner_id in (' . implode(',',$masters) . ') AND date_time <="'.$to .'" AND date_time>="'. $from. '" GROUP BY game_id, category_id';
                    }
                    else
                    {
                        $query = 'SELECT "" as game, "" as game_id, "" as category_id, 0 as total_deal, 0 as total_mileage,  0 as total_ggr, 0 as total_ggr_mileage, 0 as totaldealbet, 0 as totaldealwin';
                    }
                }
                else
                {
                    $query = 'SELECT game, game_id, category_id, SUM(bet) as totaldealbet, SUM(win) as totaldealwin, SUM(deal_profit) as total_deal, SUM(mileage) as total_mileage, SUM(ggr_profit) as total_ggr, SUM(ggr_mileage) as total_ggr_mileage FROM w_deal_log WHERE type="partner" AND partner_id =' . $user->id . ' AND date_time <="'.$to .'" AND date_time>="'. $from. '" GROUP BY game_id, category_id';
                }

                $deal_logs = \DB::select($query);

                foreach($deal_logs as $deal_log) {
                    $bfound_cat = false;
                    foreach ($adj_cats as $i => $adj)
                    {
                        if($deal_log->category_id == $adj['category_id'])
                        {
                            $adj['total_deal'] = $adj['total_deal'] + $deal_log->total_deal;
                            $adj['total_mileage'] = $adj['total_mileage'] + $deal_log->total_mileage;
                            $adj['total_ggr'] = $adj['total_ggr'] + $deal_log->total_ggr;
                            $adj['total_ggr_mileage'] = $adj['total_ggr_mileage'] + $deal_log->total_ggr_mileage;
                            $bfound_cat = true;
                        }
                        $bfound_game = false;
                        foreach ($adj['games'] as $j => $game)
                        {
                            if($deal_log->game_id == $game['game_id'] )
                            {
                                $game['total_deal'] = $game['total_deal'] + $deal_log->total_deal;
                                $game['total_mileage'] = $game['total_mileage'] + $deal_log->total_mileage;
                                $game['total_ggr'] = $game['total_ggr'] + $deal_log->total_ggr;
                                $game['total_ggr_mileage'] = $game['total_ggr_mileage'] + $deal_log->total_ggr_mileage;
    
                                $bfound_game = true;
                                $adj['games'][$j] = $game;
                                break;
                            }
                        }
                        if ($bfound_cat){ 
                            $adj_cats[$i] = $adj;
                            break; 
                        }
                    }
                }
            }
            else
            {
                $adj_cats = CategorySummary::adjustment($user_id, $from, $to);
            }

            if ($user->hasRole(['admin','comaster']))
            {
                if ($start==null && $end == null)
                {
                    \App\Models\GameSummary::where(['user_id'=> $user->id, 'date' => $day, 'type'=>'daily'])->delete();
                }
            }
            $catsum = [];
            foreach ($adj_cats as $adj)
            {
                if ($user->hasRole(['admin','comaster']))
                {
                    $games = [];
                    foreach ($adj['games'] as $adjgame)
                    {
                        if ($start==null && $end == null)
                        {
                            $adjgame['user_id'] = $user->id;
                            $adjgame['shop_id'] = 0;
                            $adjgame['type'] = 'daily';
                            $adjgame['date'] = $day;
                            $games[] = $adjgame;
                        }
                        else //today's game summary
                        {
                            $dbgame = \App\Models\GameSummary::where([
                                'user_id'=> $user->id, 
                                'date' => $day, 
                                'type'=>'today', 
                                'category_id' => $adjgame['category_id'],
                                'game_id' => $adjgame['game_id']
                                 ])->first();
                            if ($dbgame)
                            {
                                $dbgame->update([
                                    'totalwin' => $dbgame->totalwin + $adjgame['totalwin'],
                                    'totalbet' => $dbgame->totalbet + $adjgame['totalbet'],
                                    'totaldealbet' => $dbgame->totaldealbet + $adjgame['totaldealbet'],
                                    'totaldealwin' => $dbgame->totaldealwin + $adjgame['totaldealwin'],
                                    'totalcount' => $dbgame->totalcount + $adjgame['totalcount'],
                                    'total_deal' => $dbgame->total_deal + $adjgame['total_deal'],
                                    'total_mileage' => $dbgame->total_mileage + $adjgame['total_mileage'],
                                    'total_ggr' => $dbgame->total_ggr + $adjgame['total_ggr'],
                                    'total_ggr_mileage' => $dbgame->total_ggr_mileage + $adjgame['total_ggr_mileage'],
                                ]);
                            }
                            else
                            {
                                $adjgame['user_id'] = $user->id;
                                $adjgame['shop_id'] = 0;
                                $adjgame['type'] = 'today';
                                $adjgame['date'] = $day;
                                \App\Models\GameSummary::create($adjgame);
                            }
                        }
                    }
                    if (count($games)>0 && $start==null && $end == null)
                    {
                        \App\Models\GameSummary::insert($games);
                    }
                }
                unset($adj['games']);
                if ($start==null && $end == null)
                {
                    $adj['type'] = 'daily';
                    $adj['date'] = $day;
                    $catsum[] = $adj;
                }
                else  //today's category summary
                {
                    $dbcategory = \App\Models\CategorySummary::where([
                        'user_id'=> $user->id, 
                        'date' => $day, 
                        'type'=>'today', 
                        'category_id' => $adj['category_id']
                         ])->first();
                    if ($dbcategory)
                    {
                        $dbcategory->update([
                            'totalwin' => $dbcategory->totalwin + $adj['totalwin'],
                            'totalbet' => $dbcategory->totalbet + $adj['totalbet'],
                            'totaldealbet' => $dbcategory->totaldealbet + $adj['totaldealbet'],
                            'totaldealwin' => $dbcategory->totaldealwin + $adj['totaldealwin'],
                            'totalcount' => $dbcategory->totalcount + $adj['totalcount'],
                            'total_deal' => $dbcategory->total_deal + $adj['total_deal'],
                            'total_mileage' => $dbcategory->total_mileage + $adj['total_mileage'],
                            'total_ggr' => $dbcategory->total_ggr + $adj['total_ggr'],
                            'total_ggr_mileage' => $dbcategory->total_ggr_mileage + $adj['total_ggr_mileage'],
                            'updated_at' => $to
                        ]);
                    }
                    else
                    {
                        $adj['type'] = 'today';
                        $adj['date'] = $day;
                        $adj['updated_at'] = $to;
                        \App\Models\CategorySummary::create($adj);
                    }
                }
            }
            if (count($catsum) > 0 && $start==null && $end == null)
            {
                \App\Models\CategorySummary::where(['user_id'=> $user->id, 'date' => $day, 'type'=>'daily'])->delete();
                \App\Models\CategorySummary::insert($catsum);
            }
            return $adj_cats;
        }

        public static function summary_today($user_id)
        {
            set_time_limit(0);
          
            $day = date("Y-m-d");
            $todaysumm = \App\Models\CategorySummary::where(['user_id'=> $user_id, 'date' => $day, 'type'=>'today'])->orderBy('updated_at', 'desc')->get();
            if (count($todaysumm) > 0)
            {
                $from = date("H:i:s", strtotime($todaysumm->first()->updated_at . " +1 seconds"));
            }
            else
            {
                $from =  "0:0:0";
            }
            $to =  date("H:i:s");
            $adj_cats = CategorySummary::summary($user_id, $day, $from, $to);
            return;
        }
    }

}
