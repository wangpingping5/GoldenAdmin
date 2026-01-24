<?php
namespace App\Models {
    use Log;
    class User extends \Illuminate\Foundation\Auth\User implements \Tymon\JWTAuth\Contracts\JWTSubject
    {
        use \Laracasts\Presenter\PresentableTrait,
            \Illuminate\Notifications\Notifiable,
            \jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;
        protected $presenter = 'App\Presenters\UserPresenter';
        protected $table = 'users';
        protected $dates = [
            'last_login',
            'birthday'
        ];
        protected $fillable = [
            'first_name',
            'last_name',
            'phone',
            'password',
            'email',
            'username',
            'avatar',
            'address',
            'balance',
            'last_login',
            'confirmation_token',  //used for withdraw password
            'status',
            'wager',
            'rating',  //used for in/out alarm
            'points',
            'total_balance',
            'bonus',
            'count_bonus',
            'total_in',
            'total_out',
            'language',
            'remember_token',  //used for game launch token
            'role_id',
            'count_balance',
            'count_return',
            'parent_id',
            'shop_id',
            'session',//used as nosql data for comaster
            'pball_single_percent',
            'pball_comb_percent',
            'sports_deal_percent',
            'card_deal_percent',
            'deal_balance',
            'deal_percent',
            'table_deal_percent',
            'money_percent',
            'mileage',
            'bank_name',
            'recommender',
            'account_no',
            'api_token',
            'ggr_percent',
            'table_ggr_percent',
            'ggr_mileage',
            'reset_days',
            'last_reset_at',
            'playing_game',
            'played_at'
        ];


        public static $values = [
            'banks' => [
                '',
                '국민',
                '기업',
                '농협 / 단위농협',
                '신한',
                '우체국',
                'ibk저축은행',
                'SC(스탠다드차타드) / 제일',
                '하나',
                '씨티',
                '우리',
                '경남',
                '광주',
                '대구',
                '도이치',
                '부산',
                '산업',
                '수협',
                '전북',
                '제주',
                '새마을금고',
                '신용협동조합',
                '홍콩상하이(HSBC)',
                '상호저축은행중앙회',
                '뱅크오브아메리카',
                '케이뱅크',
                '카카오뱅크',
                '제이피모간체이스',
                '비엔피파리바',
                'NH투자증권',
                '유안타증권',
                'KB증권',
                '미래에셋대우',
                '삼성증권',
                '한국투자증권',
                '교보증권',
                '하이투자증권',
                '현대차증권',
                'SK증권',
                '한화투자증권',
                '하나금융투자',
                '신한금융투자',
                '유진투자증권',
                '메리츠종합금융증권',
                '신영증권',
                '이베스트투자증권',
                '케이프증권',
                '산림조합',
                '부국증권',
                '키움증권',
                '대신증권',
                'DB금융투자',
                '중국공상',
                '펀드온라인코리아',
                '케이티비투자증권',
                '토스뱅크',
                '신협',
                'PAYWIN',
                'JUNCOIN',
                'WORLDPAY',
                'OSSCOIN',
                '경남가상계좌',
                '나인가상계좌',
                'VirtualAcc',
                '효원라이프',
                'MESSAGE',
                '나우딜'
            ],
            'reset_days' => [
                '',
                '1일',
                '2일',
                '3일',
                '4일',
                '5일',
                '6일',
                '7일',
                '8일',
                '9일',
                '10일',
                '11일',
                '12일',
                '13일',
                '14일',
                '15일',
            ]
        ];

        const  USER_CURRENCY_SYMBOLS = [
            "USD" => '$',
            "KRW" => '₩',
            "CAD" => 'C$',
            "TRY" => '₺',
            "BRL" => 'R$',
            "EUR" => '€',
            "GBP" => '£',
            "TND" => 'د.ت',
            "AUD" => 'A$',
        ];

        protected $hidden = [
            'password',
            'remember_token'
        ];
        public function generateCode($limit)
        {
            $code = 0;
            for ($i = 0; $i < $limit; $i++) {
                $code .= mt_rand(0, 9);
            }
            return $code;
        }
        public static function boot()
        {
            parent::boot();
            self::created(function ($model) {
            });
            self::deleting(function ($model) {
                $model->detachAllRoles();
                Transaction::where('user_id', $model->id)->delete();
                ShopUser::where('user_id', $model->id)->delete();
                StatGame::where('user_id', $model->id)->delete();
                GameLog::where('user_id', $model->id)->delete();
                UserActivity::where('user_id', $model->id)->delete();
                Session::where('user_id', $model->id)->delete();
                Info::where('user_id', $model->id)->delete();
                OpenShift::where('user_id', $model->id)->delete();
                GameActivity::where('user_id', $model->id)->delete();
            });
        }
        public function setPasswordAttribute($value)
        {
            $this->attributes['password'] = bcrypt($value);
        }
        public function setBirthdayAttribute($value)
        {
            $this->attributes['birthday'] = (trim($value) ?: null);
        }
        public function gravatar()
        {
            $hash = hash('md5', strtolower(trim($this->attributes['username'])));
            return sprintf('https://www.gravatar.com/avatar/%s?size=150', $hash);
        }
        public function isActive()
        {
            return $this->status == Support\Enum\UserStatus::ACTIVE;
        }
        public function availableUsers()
        {
            $users = User::where(['id' => $this->id])->get();
            if ($this->hasRole(['admin'])) {
                $groups = User::where([
                    'role_id' => 8,
                ])->get();
                $comasters = User::where('role_id', 7)->whereIn('parent_id', $groups->pluck('id')->toArray())->get();
                $masters = User::where('role_id', 6)->whereIn('parent_id', $comasters->pluck('id')->toArray())->get();
                $agents = User::where('role_id', 5)->whereIn('parent_id', $masters->pluck('id')->toArray())->get();
                $distributors = User::where('role_id', 4)->whereIn('parent_id', $agents->pluck('id')->toArray())->get();
                $other = User::where('role_id', '<=', 3)->whereIn('shop_id', $this->availableShops())->get();
                $users = $users->merge($groups);
                $users = $users->merge($comasters);
                $users = $users->merge($masters);
                $users = $users->merge($agents);
                $users = $users->merge($distributors);
                $users = $users->merge($other);
            }
            if ($this->hasRole(['group'])) {
                $comasters = User::where([
                    'role_id' => 7,
                    'parent_id' => $this->id
                ])->get();
                $masters = User::where('role_id', 6)->whereIn('parent_id', $comasters->pluck('id')->toArray())->get();
                $agents = User::where('role_id', 5)->whereIn('parent_id', $masters->pluck('id')->toArray())->get();
                $distributors = User::where('role_id', 4)->whereIn('parent_id', $agents->pluck('id')->toArray())->get();
                $other = User::where('role_id', '<=', 3)->whereIn('shop_id', $this->availableShops())->get();
                $users = $users->merge($comasters);
                $users = $users->merge($masters);
                $users = $users->merge($agents);
                $users = $users->merge($distributors);
                $users = $users->merge($other);
            }
            if ($this->hasRole(['comaster'])) {
                $masters = User::where([
                    'role_id' => 6,
                    'parent_id' => $this->id
                ])->get();
                $agents = User::where('role_id', 5)->whereIn('parent_id', $masters->pluck('id')->toArray())->get();
                $distributors = User::where('role_id', 4)->whereIn('parent_id', $agents->pluck('id')->toArray())->get();
                $other = User::where('role_id', '<=', 3)->whereIn('shop_id', $this->availableShops())->get();
                $users = $users->merge($masters);
                $users = $users->merge($agents);
                $users = $users->merge($distributors);
                $users = $users->merge($other);
            }
            if ($this->hasRole(['master'])) {
                $agents = User::where([
                    'role_id' => 5,
                    'parent_id' => $this->id
                ])->get();
                $distributors = User::where('role_id', 4)->whereIn('parent_id', $agents->pluck('id')->toArray())->get();
                $other = User::where('role_id', '<=', 3)->whereIn('shop_id', $this->availableShops())->get();
                $users = $users->merge($agents);
                $users = $users->merge($distributors);
                $users = $users->merge($other);
            }
            if ($this->hasRole(['agent'])) {
                $distributors = User::where([
                    'role_id' => 4,
                    'parent_id' => $this->id
                ])->get();
                $other = User::where('role_id', '<=', 3)->whereIn('shop_id', $this->availableShops())->get();
                $users = $users->merge($distributors);
                $users = $users->merge($other);
            }
            if ($this->hasRole(['distributor'])) {
                $other = User::where('role_id', '<=', 3)->whereIn('shop_id', $this->shops_array(true))->get();
                $users = $users->merge($other);
            }
            if ($this->hasRole(['manager'])) {
                $other = User::where('role_id', '<=', 2)->where('shop_id', $this->shop_id)->get();
                $users = $users->merge($other);
            }
            if ($this->hasRole(['cashier'])) {
                $other = User::where('role_id', 1)->where('shop_id', $this->shop_id)->get();
                $users = $users->merge($other);
            }
            $users = $users->pluck('id');
            if (!count($users)) {
                $users = [0];
            } else {
                $users = $users->toArray();
            }
            return $users;
        }
        public function hierarchyUsers()
        {
            return $this->availableUsers();
        }

        public function hierarchyPartners()
        {
            $users = $this->availableUsers();
            if (!count($users)) {
                return [];
            }
            $level = $this->level();
            if ($level < 3) {
                return [];
            }
            return User::whereIn('role_id', range(3, $level - 1))->whereIn('id', $users)->pluck('id')->toArray();
        }

        public function childPartners()
        {
            $level = $this->level();
            $users = User::where(['parent_id' => $this->id])->get();
            return $users->pluck('id')->toArray();
        }

        public function hierarchyUsersOnly()
        {
            return $this->availableUsersByRole('user');
        }
        public function hierarchyUserNamesOnly()
        {
            $users = $this->availableUsers();
            if (!count($users)) {
                return [];
            }
            return User::where('role_id', 1)->whereIn('id', $users)->pluck('username', 'id')->toArray();
        }

        public function isAvailable($user)
        {
            if (!$user) {
                return false;
            }
            if (in_array($user->id, $this->availableUsers())) {
                return true;
            }
            return false;
        }
        public function emptyShops()
        {
            $count = 0;
            if ($shops = $this->rel_shops) {
                foreach ($shops as $shop) {
                    if ($shop->shop && count($shop->shop->getUsersByRole('user')) == 0) {
                        $count++;
                    }
                }
            }
            return $count;
        }
        public function availableUsersByRole($roleName)
        {
            $role = \jeremykenedy\LaravelRoles\Models\Role::where('slug', $roleName)->first();
            if ($this->hasRole(['admin'])) {
                $users = User::where('role_id', $role->id)->pluck('id')->toArray();
            } else {
                $users = $this->availableUsers();
                if (!count($users)) {
                    return [];
                }
                $users = User::where('role_id', $role->id)->whereIn('id', $users)->pluck('id')->toArray();
            }
            return $users;
        }
        public function availableShops()
        {
            $shops = [$this->shop_id];
            if (!$this->hasRole(['manager'])) {
                //if( !$this->shop_id ) 
                //{
                $shops = array_merge([0], $this->shops_array(true));
                /*}
                else
                {
                    $shops = [$this->shop_id];
                }*/
            }
            return $shops;
        }
        public function getInnerUsers()
        {
            $role = \jeremykenedy\LaravelRoles\Models\Role::where('id', $this->role_id - 1)->first();
            $ids = $this->availableUsersByRole($role->slug);
            if (count($ids)) {
                return User::whereIn('id', $ids)->get();
            }
            return false;
        }
        public function getRowspan()
        {
            $rowspan = 0;
            if ($this->hasRole(['comaster', 'master', 'agent']))  //don't use this function
            {
                $rowspan = 0;
                $distributors = User::where('parent_id', $this->id)->get();
                if ($distributors) {
                    foreach ($distributors as $distributor) {
                        $shops = $distributor->shops_array();
                        $rowspan += (count($shops) ?: 1);
                    }
                }
            }
            if ($this->hasRole('distributor')) {
                $rowspan = 0;
                if ($shops = $this->rel_shops) {
                    foreach ($shops as $shop) {
                        if ($shop = $shop->shop) {
                            $managers = $shop->getUsersByRole('manager');
                            $rowspan += (count($managers) ?: 1);
                        }
                    }
                }
            }
            return ($rowspan > 0 ? $rowspan : 1);
        }
        public function isBanned()
        {
            return $this->status == \App\Support\Enum\UserStatus::BANNED;
        }
        public function role()
        {
            return $this->belongsTo('jeremykenedy\LaravelRoles\Models\Role', 'role_id');
        }
        public function activities()
        {
            return $this->hasMany('App\Models\Services\Logging\UserActivity\Activity', 'user_id');
        }
        public function lastActivity()
        {
            $activity = \App\Models\UserActivity::where('user_id', $this->id)->orderby('created_at', 'desc')->first();
            return $activity;
        }
        public function referral()
        {
            return $this->belongsTo('App\Models\User', 'parent_id');
        }
        public function rel_shops()
        {
            return $this->hasMany('App\Models\ShopUser', 'user_id');
        }
        public function shops($onlyId = false)
        {
            if ($this->hasRole('admin')) {
                $shops = Shop::all()->pluck('id');
            } else if ($this->hasRole('group')) {
                $groups = $this->childPartners();
                $comasters = User::whereIn('parent_id', $groups)->get()->pluck('id')->toArray();
                $shops = ShopUser::whereIn('user_id', $comasters)->pluck('shop_id');
            } else if ($this->hasRole('comaster')) {
                $partners = $this->childPartners();
                $shops = ShopUser::whereIn('user_id', $partners)->pluck('shop_id');
            } else {
                $shops = ShopUser::where('user_id', $this->id)->pluck('shop_id');
            }
            if (count($shops)) {
                if ($onlyId) {
                    return Shop::whereIn('id', $shops)->pluck('id');
                } else {
                    return Shop::whereIn('id', $shops)->pluck('name', 'id');
                }
            } else {
                return [];
            }
        }
        public function shops_array($onlyId = false)
        {
            $data = $this->shops($onlyId);
            if (!is_array($data)) {
                return $data->toArray();
            }
            return $data;
        }
        public function available_roles($withMe = false)
        {
            $roles = [
                '1' => [],
                '2' => [1],
                '3' => [1],
                '4' => [3],
                '5' => [4],
                '6' => [5],
                '7' => [6],
                '8' => [7]
            ];
            if ($withMe) {
                $roles = [
                    '1' => [],
                    '2' => [
                        1,
                        2
                    ],
                    '3' => [
                        1,
                        2,
                        3
                    ],
                    '4' => [
                        1,
                        2,
                        3,
                        4
                    ],
                    '5' => [
                        1,
                        2,
                        3,
                        4,
                        5
                    ],
                    '6' => [
                        1,
                        2,
                        3,
                        4,
                        5,
                        6
                    ],
                    '7' => [
                        1,
                        2,
                        3,
                        4,
                        5,
                        6,
                        7
                    ],
                    '8' => [
                        1,
                        2,
                        3,
                        4,
                        5,
                        6,
                        7,
                        8
                    ]
                ];
            }
            if (count($roles[$this->level()])) {
                return \jeremykenedy\LaravelRoles\Models\Role::whereIn('id', $roles[$this->level()])->pluck('name', 'id');
            }
            return [];
        }
        public function shop()
        {
            return $this->belongsTo('App\Models\Shop', 'shop_id');
        }

        public function memo()
        {
            return $this->hasOne('App\Models\UserMemo', 'user_id');
        }

        public function accessrule()
        {
            return $this->hasOne('App\Models\AccessRule', 'user_id');
        }

        public function getJWTIdentifier()
        {
            return $this->id;
        }
        public function getJWTCustomClaims()
        {
            $token = app('App\Models\Services\Auth\Api\TokenFactory')->forUser($this);
            return ['jti' => $token->id];
        }
        public function addBalance($type, $summ, $payeer = false, $return = 0, $request_id = null, $reason = null)
        {
            if (
                !in_array($type, [
                    'add',
                    'out'
                ])
            ) {
                $type = 'add';
            }
            $shop = $this->shop;
            if (!$payeer) {
                $payeer = User::where('id', auth()->user()->id)->first();
            }
            /*if( $payeer->hasRole('admin') && !$this->hasRole('master') ) 
            {
                return response()->json([
                    'status' => 'error', 
                    'message' => trans('app.wrong_user')
                ]);
            }
            if( $payeer->hasRole('master') && !$this->hasRole('agent') ) 
            {
                return response()->json([
                    'status' => 'error', 
                    'message' => trans('app.wrong_user')
                ]);
            } 
            if( $payeer->hasRole('agent') && (!$this->hasRole('distributor') && !$this->hasRole('user')) ) 
            {
                return json_encode([
                    'status' => 'error', 
                    'message' => trans('app.wrong_user')
                ]);
            }
            if( $payeer->hasRole('distributor') && !$this->hasRole('manager') ) 
            {
                return json_encode([
                    'status' => 'error', 
                    'message' => trans('app.wrong_user')
                ]);
            }
            if( ($payeer->hasRole('cashier') || $payeer->hasRole('manager')) && !$this->hasRole('user')) 
            {
                return json_encode([
                    'status' => 'error', 
                    'message' => trans('app.wrong_user')
                ]);
            }*/
            if (!$summ) {
                return json_encode([
                    'status' => 'error',
                    'message' => trans('app.wrong_sum')
                ]);
            }
            $summ = abs($summ);
            if (($payeer->hasRole('cashier') || $payeer->hasRole('manager')) && $this->hasRole('user')) {
                if (!$shop) {
                    return json_encode([
                        'status' => 'error',
                        'message' => trans('app.wrong_shop')
                    ]);
                }
                if ($type == 'add' && $shop->balance < $summ) {
                    return json_encode([
                        'status' => 'error',
                        'message' => trans('app.not_enough_money_in_the_shop', [
                            'name' => $shop->name,
                            'balance' => $shop->balance
                        ])
                    ]);
                }
            }
            if (/* ($payeer->hasRole('agent') && ($this->hasRole('distributor') || $this->hasRole('user'))|| $payeer->hasRole('distributor') && $this->hasRole('manager')) && */ $payeer->hasRole(['comaster', 'group', 'master', 'agent', 'distributor']) && $type == 'add' && $payeer->balance < $summ) {
                return json_encode([
                    'status' => 'error',
                    'message' => trans('app.not_enough_money_in_the_user_balance', [
                        'name' => $payeer->name,
                        'balance' => $payeer->balance
                    ])
                ]);
            }
            if ($type == 'out' && $this->balance < $summ) {
                return json_encode([
                    'status' => 'error',
                    'message' => trans('app.not_enough_money_in_the_user_balance', [
                        'name' => $this->username,
                        'balance' => $this->balance
                    ])
                ]);
            }
            $open_shift = null;
            if (($payeer->hasRole('cashier') || $payeer->hasRole('manager')) && $this->hasRole('user')) {
                $open_shift = OpenShift::where([
                    'shop_id' => $payeer->shop_id,
                    'type' => 'shop',
                    'end_date' => null
                ])->first();
                // if( !$open_shift ) 
                // {
                //     return json_encode([
                //         'status' => 'error', 
                //         'message' => trans('app.shift_not_opened')
                //     ]);
                // }
            }
            if ($this->hasRole(['comaster', 'master', 'agent', 'distributor'])) {
                $open_shift = OpenShift::where([
                    'user_id' => $this->id,
                    'type' => 'partner',
                    'end_date' => null
                ])->first();
            }
            if ($payeer->hasRole(['admin', 'comaster', 'master', 'agent'])) {
                $payeer_open_shift = OpenShift::where([
                    'user_id' => $payeer->id,
                    'type' => 'partner',
                    'end_date' => null
                ])->first();
            }


            $happyhour = HappyHour::where([
                'shop_id' => $payeer->shop_id,
                'time' => date('G')
            ])->first();
            $summ = ($type == 'out' ? -1 * abs($summ) : abs($summ));
            $balance = $summ;
            $old = $this->balance;
            /*if( ($payeer->hasRole('cashier') || $payeer->hasRole('manager')) && $this->hasRole('user') && $type == 'add' && $happyhour ) 
            {
                $transactionSum = $summ * intval(str_replace('x', '', $happyhour->multiplier));
                $bonus = $transactionSum - $summ;
                $wager = $bonus * intval(str_replace('x', '', $happyhour->wager));
                Transaction::create([
                    'user_id' => $this->id, 
                    'system' => 'HH ' . $happyhour->multiplier, 
                    'type' => $type, 
                    'summ' => $transactionSum, 
                    'request_id' => $request_id,
                    'shop_id' => ($this->hasRole('user') ? $this->shop_id : 0)
                ]);
                $this->increment('wager', $wager);
                $this->increment('bonus', $bonus);
                $this->increment('count_bonus', $bonus);
                $balance = $transactionSum;
            }
            else
            {*/

            //}
            if (!$this->hasRole('admin')) {
                $this->increment('balance', $balance);
                $this->increment('count_balance', $summ);
            }
            if ($type == 'out') {
                $this->increment('total_out', abs($summ));
            } else {
                $this->increment('total_in', abs($summ));
            }
            if ($this->hasRole('user')) {
                if ($type == 'out') {
                    $this->update(['count_return' => 0]);
                } else if ($return > 0) {
                    $this->update(['count_return' => $this->count_return + (($summ * $return) / 100)]);
                } else {
                    $this->update(['count_return' => $this->count_return + \App\Lib\Functions::count_return($summ, $this->shop_id)]);
                }
            }
            $payer_balance = 0;
            if (/* $payeer->hasRole('agent') && ($this->hasRole('distributor') || $this->hasRole('user'))|| $payeer->hasRole('distributor') && $this->hasRole('manager') */
                $payeer->hasRole(['group', 'comaster', 'master', 'agent', 'distributor'])
            ) {
                $payeer->update(['balance' => $payeer->balance - $summ]);
                $payeer = $payeer->fresh();
                $payer_balance = $payeer->balance;
            }
            if (($payeer->hasRole('cashier') || $payeer->hasRole('manager')) && $this->hasRole('user')) {
                $shop->update(['balance' => $shop->balance - $summ]);
                $shop = $shop->fresh();
                $payer_balance = $shop->balance;
                if ($type == 'out') {
                    if ($open_shift)
                        $open_shift->increment('money_out', abs($summ));
                } else {
                    if ($open_shift)
                        $open_shift->increment('money_in', abs($summ));
                }
            }
            if ($payeer->hasRole(['admin', 'comaster', 'master', 'agent'])) {
                if ($type == 'out') {
                    if ($open_shift)
                        $open_shift->increment('balance_out', abs($summ));
                    if ($payeer_open_shift)
                        $payeer_open_shift->increment('money_out', abs($summ));
                } else {
                    if ($open_shift)
                        $open_shift->increment('balance_in', abs($summ));
                    if ($payeer_open_shift)
                        $payeer_open_shift->increment('money_in', abs($summ));
                }

            }
            Transaction::create([
                'user_id' => $this->id,
                'payeer_id' => $payeer->id,
                'type' => $type,
                'summ' => abs($summ),
                'old' => $old,
                'new' => $this->balance,
                'balance' => $payer_balance,
                'request_id' => $request_id,
                'shop_id' => ($this->hasRole('user') ? $this->shop_id : 0),
                'reason' => $reason
            ]);
            if ($this->balance == 0) {
                $this->update([
                    'wager' => 0,
                    'bonus' => 0
                ]);
            }
            if ($this->wager <= 0) {
                $this->update([
                    'wager' => 0,
                    'bonus' => 0,
                    'count_bonus' => 0
                ]);
            }
            if ($this->count_return <= 0) {
                $this->update(['count_return' => 0]);
            }
            if ($this->count_balance < 0) {
                $this->update(['count_balance' => 0]);
            }
            return json_encode([
                'status' => 'success',
                'message' => trans('app.balance_updated')
            ]);
        }

        public function getDealData($betMoney, $winMoney, $type, $stat_game)
        {
            $game = $stat_game->game;
            $category_id = $stat_game->category_id;
            $game_id = $stat_game->game_id;
            $date_time = $stat_game->date_time;
            if ($date_time == null) {
                $date_time = date('Y-m-d H:i:s');
            }
            if ($type == null) {
                $type = 'slot';
            }
            $deal_field = [
                'slot' => 'deal_percent',
                'table' => 'table_deal_percent',
                'pbsingle' => 'pball_single_percent',
                'pbcomb' => 'pball_comb_percent',
                'sports' => 'sports_deal_percent',
                'card' => 'card_deal_percent'
            ];
            $ggr_field = [
                'slot' => 'ggr_percent',
                'table' => 'table_ggr_percent',
                'pbsingle' => 'table_ggr_percent',
                'pbcomb' => 'table_ggr_percent',
                'sports' => 'table_ggr_percent',
                'card' => 'table_ggr_percent'
            ];

            $shop = $this->shop;
            $deal_balance = 0;
            $deal_mileage = 0;
            $deal_percent = 0;
            $ggr_profit = 0;
            $ggr_mileage = 0;
            $ggr_percent = 0;

            $deal_data = [];
            $share_data = null;
            $deal_percent = $this->{$deal_field[$type]};
            $ggr_percent = $this->{$ggr_field[$type]};
            if ($deal_percent > 0 || $ggr_percent > 0) //user can get deal percent
            {
                $deal_balance = $betMoney * $deal_percent / 100;
                $ggr_profit = ($betMoney - $winMoney) * $ggr_percent / 100;
                $deal_data[] = [
                    'user_id' => $this->id,
                    'partner_id' => $this->id, //user's id
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'bet' => $betMoney,
                    'win' => $winMoney,
                    'deal_profit' => $deal_balance,
                    'game' => $game,
                    'shop_id' => $shop->id,
                    'type' => 'partner',
                    'deal_percent' => $deal_percent,
                    'mileage' => $deal_mileage,
                    'ggr_profit' => $ggr_profit,
                    'ggr_mileage' => $ggr_mileage,
                    'ggr_percent' => $ggr_percent,
                    'date_time' => $date_time,
                    'category_id' => $category_id,
                    'game_id' => $game_id,
                ];
                $deal_mileage = $deal_balance;
                $ggr_mileage = $ggr_profit;
            }

            $deal_percent = $shop->{$deal_field[$type]};
            $ggr_percent = $shop->{$ggr_field[$type]};
            $manager = $this->referral;
            if ($manager != null) {
                if ($deal_percent > 0 || $ggr_percent > 0) {
                    $deal_balance = $betMoney * $deal_percent / 100;
                    $ggr_profit = ($betMoney - $winMoney) * $ggr_percent / 100;
                    if ($betMoney > 0 && ($deal_balance < $deal_mileage)) {
                        //error
                        return ['deal' => $deal_data, 'share' => $share_data];
                    }
                    $deal_data[] = [
                        'user_id' => $this->id,
                        'partner_id' => $manager->id, //manager's id
                        'balance_before' => 0,
                        'balance_after' => 0,
                        'bet' => abs($betMoney),
                        'win' => abs($winMoney),
                        'deal_profit' => $deal_balance,
                        'game' => $game,
                        'shop_id' => $shop->id,
                        'type' => 'shop',
                        'deal_percent' => $deal_percent,
                        'mileage' => $deal_mileage,
                        'ggr_profit' => $ggr_profit,
                        'ggr_mileage' => $ggr_mileage,
                        'ggr_percent' => $ggr_percent,
                        'date_time' => $date_time,
                        'category_id' => $category_id,
                        'game_id' => $game_id,
                    ];
                }
                $partner = $manager->referral;
                while ($partner != null && !$partner->isInoutPartner()) {
                    $deal_mileage = $deal_balance;
                    $ggr_mileage = $ggr_profit;
                    $deal_percent = $partner->{$deal_field[$type]};
                    $ggr_percent = $partner->{$ggr_field[$type]};
                    if ($deal_percent > 0 || $ggr_percent > 0) {
                        $deal_balance = $betMoney * $deal_percent / 100;
                        $ggr_profit = ($betMoney - $winMoney) * $ggr_percent / 100;
                        if ($betMoney > 0 && ($deal_balance < $deal_mileage)) {
                            //error
                            return ['deal' => $deal_data, 'share' => $share_data];
                        }
                        // if ($deal_balance > $deal_mileage)
                        {
                            $deal_data[] = [
                                'user_id' => $this->id,
                                'partner_id' => $partner->id,
                                'balance_before' => 0,
                                'balance_after' => 0,
                                'bet' => abs($betMoney),
                                'win' => abs($winMoney),
                                'deal_profit' => $deal_balance,
                                'game' => $game,
                                'shop_id' => $this->shop_id,
                                'type' => 'partner',
                                'deal_percent' => $deal_percent,
                                'mileage' => $deal_mileage,
                                'ggr_profit' => $ggr_profit,
                                'ggr_mileage' => $ggr_mileage,
                                'ggr_percent' => $ggr_percent,
                                'date_time' => $date_time,
                                'category_id' => $category_id,
                                'game_id' => $game_id,
                            ];
                        }
                    }
                    $partner = $partner->referral;
                }
                // last check if the deal_percent is less than comaster's deal percent
                if ($partner != null && $partner->{$deal_field[$type]} < $deal_percent) {
                    //error
                    return ['deal' => [], 'share' => null];
                }
                $sharebetinfo = \App\Models\ShareBetInfo::where(['partner_id' => $partner->id, 'share_id' => $partner->parent_id, 'category_id' => $category_id])->first();
                if ($sharebetinfo && $sharebetinfo->minlimit > 0 && $sharebetinfo->minlimit < $betMoney) {
                    $share_data = [
                        'user_id' => $this->id,
                        'date_time' => $date_time,
                        'game' => $game,
                        'partner_id' => $partner->id,
                        'share_id' => $partner->parent_id,
                        'bet' => $betMoney,
                        'win' => $winMoney,
                        'betlimit' => $sharebetinfo->minlimit,
                        'winlimit' => 0,
                        'deal_percent' => $deal_percent,
                        'deal_limit' => 0,
                        'shop_id' => $this->shop_id,
                        'category_id' => $category_id,
                        'game_id' => $game_id,
                        'stat_id' => $stat_game->id
                    ];

                }
            }
            return ['deal' => $deal_data, 'share' => $share_data];
        }

        public function processBetDealerMoney_Queue($stat_game)
        {
            $game = $stat_game->game;
            $betMoney = $stat_game->bet;
            $winMoney = $stat_game->win;
            $type = $stat_game->type;
            $category_id = $stat_game->category_id;
            $game_id = $stat_game->game_id;
            $date_time = $stat_game->date_time;
            if ($date_time == null) {
                $date_time = date('Y-m-d H:i:s');
            }

            $refundGames = ['_refund', '_tie'];
            foreach ($refundGames as $refundGame) {
                if (strlen($game) >= strlen($refundGame) && substr_compare($game, $refundGame, -strlen($refundGame)) === 0) {
                    $betMoney = -$stat_game->win;
                    $winMoney = 0;
                    break;
                }
            }
            if (!$this->hasRole('user')) {
                return;
            }

            if ($type == 'pball') //powerball deal
            {
                $res = null;
                $gameInfo = $stat_game->game_item;
                if ($gameInfo) {
                    $object = '\App\Models\Http\Controllers\Web\GameParsers\PowerBall\\' . $gameInfo->name;
                    if (!class_exists($object)) {
                        return;
                    }
                    $gameObject = new $object($gameInfo->original_id);
                    if (method_exists($gameObject, 'gameDetail')) {
                        $res = $gameObject->gameDetail($stat_game);
                    } else {
                        return;
                    }
                }
                if ($res == null) {
                    return;
                }
                foreach ($res['bets'] as $bet) {
                    $betMoney = $bet->amount;
                    $winMoney = $bet->win;
                    if ($bet->rate < 2) {
                        $type = 'pbsingle';
                    } else {
                        $type = 'pbcomb';
                    }
                    $deal_data = $this->getDealData($betMoney, $winMoney, $type, $stat_game);
                    if (isset($deal_data['deal']) && count($deal_data['deal']) > 0) {
                        \App\Models\Jobs\UpdateDeal::dispatch($deal_data)->onQueue('deal');
                    }
                }
            } else {
                $deal_data = $this->getDealData($betMoney, $winMoney, $type, $stat_game);
                if (isset($deal_data['deal']) && count($deal_data['deal']) > 0) {
                    \App\Models\Jobs\UpdateDeal::dispatch($deal_data)->onQueue('deal');
                }
                if (isset($deal_data['share'])) {
                    \App\Models\Jobs\ShareBet::dispatch(['share' => $deal_data['share']])->onQueue('share');
                }
            }


        }
        public function processBetDealerMoney($betMoney, $game, $type = 'slot')
        {
            if (!$this->hasRole('user')) {
                return;
            }

            //$shop = $this->shop;
            $shop = \App\Models\Shop::lockForUpdate()->where('id', $this->shop_id)->first();
            $deal_shop = 0;
            $deal_distributor = 0;
            $deal_agent = 0;
            $deal_percent = ($type == null || $type == 'slot') ? $shop->deal_percent : $shop->table_deal_percent;
            if ($deal_percent > 0) {
                $deal_shop = $betMoney * $deal_percent / 100;
                $balance_before = $shop->deal_balance;
                $shop->update(['deal_balance' => $shop->deal_balance + $deal_shop]);
                /*$open_shift = OpenShift::where([
                    'shop_id' => $shop->id, 
                    'type' => 'shop',
                    'end_date' => null
                ])->first();
                if ($open_shift)
                {
                    $open_shift->increment('deal_profit', $deal_shop);
                } */

                $balance_after = $shop->deal_balance;

                DealLog::create([
                    'user_id' => $this->id,
                    'partner_id' => 0,
                    'balance_before' => $balance_before,
                    'balance_after' => $balance_after,
                    'bet' => abs($betMoney),
                    'deal_profit' => $deal_shop,
                    'game' => $game,
                    'shop_id' => $shop->id,
                    'type' => 'shop',
                    'deal_percent' => $deal_percent,
                    'mileage' => 0
                ]);
            }

            $manager = $this->referral;
            if ($manager != null) {
                $distributor = \App\Models\User::lockForUpdate()->where('id', $manager->parent_id)->first();
                $deal_percent = ($type == null || $type == 'slot') ? $distributor->deal_percent : $distributor->table_deal_percent;
                if ($distributor != null && $distributor->hasRole('distributor') && $deal_percent > 0) {
                    $deal_distributor = $this->addDealerMoney($betMoney, $distributor, $deal_shop, $game, $type);
                }

                if ($distributor != null && $distributor->referral != null) {
                    $agent = \App\Models\User::lockForUpdate()->where('id', $distributor->parent_id)->first();
                    $deal_percent = ($type == null || $type == 'slot') ? $agent->deal_percent : $agent->table_deal_percent;
                    if ($agent != null && $deal_percent > 0) {
                        $agent_distributor = $this->addDealerMoney($betMoney, $agent, $deal_distributor, $game, $type);

                        if (settings('enable_master_deal')) {
                            $master = \App\Models\User::lockForUpdate()->where('id', $agent->parent_id)->first();
                            $deal_percent = ($type == null || $type == 'slot') ? $master->deal_percent : $master->table_deal_percent;
                            if ($master != null && $deal_percent > 0) {
                                $this->addDealerMoney($betMoney, $master, $agent_distributor, $game, $type);
                            }
                        }
                    }
                }
            }
        }

        public function addDealerMoney($betMoney, $parentUser, $childDealMoney, $game, $type)
        {
            $deal_percent = ($type == null || $type == 'slot') ? $parentUser->deal_percent : $parentUser->table_deal_percent;
            $total_deal_money = $betMoney * $deal_percent / 100;
            $deal_money = $total_deal_money - $childDealMoney;
            if ($deal_money < 0) {
                return $total_deal_money;
            }
            $balance_before = $parentUser->deal_balance;
            $parentUser->update(['deal_balance' => $parentUser->deal_balance + $total_deal_money, 'mileage' => $parentUser->mileage + $childDealMoney]);
            $balance_after = $parentUser->deal_balance;

            DealLog::create([
                'user_id' => $this->id,
                'partner_id' => $parentUser->id,
                'balance_before' => $balance_before,
                'balance_after' => $balance_after,
                'bet' => abs($betMoney),
                'deal_profit' => $total_deal_money,
                'game' => $game,
                'shop_id' => $this->shop->id,
                'type' => 'partner',
                'deal_percent' => $deal_percent,
                'mileage' => $childDealMoney
            ]);
            return $total_deal_money;
        }

        public function isInoutPartner()
        {
            if ($this->hasRole(['admin', 'group', 'comaster'])) {
                return true;
            }

            return false;
        }

        public function checkBlack()
        {
            $result = [
                'count' => 0,
                'name' => null,
                'phone' => null,
                'account' => null,
            ];
            if ($this->phone != '') {
                $blacks = BlackList::where('phone', $this->phone)->get();
                foreach ($blacks as $b) {
                    $result['count'] = $result['count'] + 1;
                    if ($result['name']) {
                        $result['name'] = $result['name'] . ',';
                    }
                    $result['name'] = $result['name'] . $b->name;

                    if ($result['phone']) {
                        $result['phone'] = $result['phone'] . ',';
                    }
                    $result['phone'] = $result['phone'] . $b->memo;
                }
            }

            if ($this->account_no != '') {
                $blacks = BlackList::where('account_number', $this->account_no)->get();
                foreach ($blacks as $b) {
                    $result['count'] = $result['count'] + 1;
                    if ($result['name']) {
                        $result['name'] = $result['name'] . ',';
                    }
                    $result['name'] = $result['name'] . $b->name;

                    if ($result['account']) {
                        $result['account'] = $result['account'] . ',';
                    }
                    $result['account'] = $result['account'] . $b->memo;
                }

            }
            return $result;
        }

        public function parents($maxlevel = 999, $minlevel = 0, $is_array = false)
        {
            $hirechy = '';
            $parents = [];
            if ($minlevel <= $this->role_id) {
                $hirechy = $this->username;
            }
            $parent = $this;
            while ($parent != null && !$parent->isInoutPartner() && $parent->role_id < $maxlevel) {
                $parent = $parent->referral;
                if ($parent != null && $minlevel <= $parent->role_id) {
                    $parents[] = ['role_id' => $parent->role_id, 'role_name' => trans($parent->role->name), 'parent' => $parent->username];
                    if ($hirechy == '') {
                        $hirechy = '[' . trans($parent->role->name) . ']&nbsp;' . $parent->username;
                    } else {
                        $hirechy = '[' . trans($parent->role->name) . ']&nbsp;' . $parent->username . '&#10;' . $hirechy;
                    }
                }
            }
            if ($is_array == true) {
                return $parents;
            } else {
                if ($hirechy == '') {
                    return $hirechy;
                } else {
                    return '상부트리&#10;' . $hirechy;
                }
            }
        }
        public function childPartnersNumbers()
        {
            $role_id = $this->role_id;
            if ($role_id <= 3) {
                return [];
            }
            $parent_ids = [$this->id];
            $roles = \jeremykenedy\LaravelRoles\Models\Role::where('id', '<', $role_id)->pluck('id', 'description')->toArray();
            $childPartnerNumbers = [];
            $status = [\App\Support\Enum\UserStatus::ACTIVE, \App\Support\Enum\UserStatus::BANNED];
            while ($role_id > 3) {
                $role_id--;
                $parent_ids = User::whereIn('parent_id', $parent_ids)->whereIn('status', $status)->pluck('id')->toArray();
                if (count($parent_ids) == 0) {
                    break;
                }
                $roleName = '';
                foreach ($roles as $name => $index) {
                    if ($index == $role_id) {
                        $roleName = $name;
                        break;
                    }
                }
                $childPartnerNumbers[] = ['role_id' => $role_id, 'role_name' => $roleName, 'count' => count($parent_ids)];
            }
            return $childPartnerNumbers;
        }
        public function childBalanceSum()
        {
            if ($this->hasRole('user')) {
                return 0;
            }
            $ids = $this->availableUsers();
            $sum = User::whereIn('id', $ids)->sum('balance');
            $sumShop = 0;
            if (!$this->hasRole('manager')) {
                $shops = $this->availableShops();
                $sumShop = Shop::whereIn('id', $shops)->sum('balance');
            }
            return $sum + $sumShop - $this->balance;
        }
        public function childPartnerBalanceSum()
        {
            if ($this->hasRole('user')) {
                return 0;
            }
            $ids = $this->hierarchyPartners();
            $sum = User::whereIn('id', $ids)->sum('balance');
            $sumShop = 0;
            if (!$this->hasRole('manager')) {
                $shops = $this->availableShops();
                $sumShop = Shop::whereIn('id', $shops)->sum('balance');
            }
            return $sum + $sumShop;
        }
        public function childDealSum()  // 롤링금
        {
            $ids = $this->availableUsers();
            $sumDealBalance = User::whereIn('id', $ids)->sum('deal_balance');
            $sumMileage = User::whereIn('id', $ids)->sum('mileage');
            return $sumDealBalance - $sumMileage;
        }

        public function bankInfo($bmask = false)
        {
            $info = $this->bank_name . ' - ' . $this->account_no . ' - ' . $this->recommender;
            if ($bmask) {
                $accno = $this->account_no;
                $recommender = $this->recommender;
                if ($accno != '') {
                    $maxlen = strlen($accno) > 1 ? 2 : 1;
                    $accno = '******' . substr($accno, -$maxlen);
                }
                if ($recommender != '') {
                    $recommender = mb_substr($recommender, 0, 1) . '***';
                }
                $info = $this->bank_name . ' - ' . $accno . ' - ' . $recommender;
            }
            return $info;
        }
        public function maxPercent()
        {
            $maxPercents = [
                'deal_percent' => '100.00',
                'table_deal_percent' => '100.00',
                'sports_deal_percent' => '100.00',
                'card_deal_percent' => '100.00',
                'pball_single_percent' => '100.00',
                'pball_comb_percent' => '100.00',
                'ggr_percent' => '100.00',
                'table_ggr_percent' => '100.00'
            ];
            if (!$this->isInOutPartner()) {
                if ($this->hasRole('user')) {
                    $parent = $this->shop;
                } else {
                    $parent = $this->referral;
                }
                foreach ($maxPercents as $dealtype => $value) {
                    if ($parent != null) {
                        $maxPercents[$dealtype] = $parent->{$dealtype};
                    } else {
                        $maxPercents[$dealtype] = 0.00;
                    }
                }
            }
            return $maxPercents;
        }
        public function minPercent()  // 롤링금
        {
            $minPercents = [
                'deal_percent' => '0.00',
                'table_deal_percent' => '0.00',
                'sports_deal_percent' => '0.00',
                'card_deal_percent' => '0.00',
                'pball_single_percent' => '0.00',
                'pball_comb_percent' => '0.00',
                'ggr_percent' => '0.00',
                'table_ggr_percent' => '0.00'
            ];

            if (!$this->hasRole('user')) {
                $childs = User::where(['parent_id' => $this->id])->get();
                if (count($childs) > 0) {
                    foreach ($childs as $child) {
                        foreach ($minPercents as $dealtype => $value) {
                            if ($child->{$dealtype} > $value) {
                                $minPercents[$dealtype] = $child->{$dealtype};
                            }
                        }
                    }
                }
            }
            return $minPercents;
        }
        public static function badgeclass()
        {
            return [
                'btn-warning',
                'btn-warning',
                'btn-warning',
                'btn-secondary',
                'btn-primary',
                'btn-danger',
                'btn-success',
                'btn-info',
                'btn-warning',
                'btn-dark',
            ];
        }
        public static function bgclass()
        {
            return [
                'bg-warning',
                'bg-warning',
                'bg-warning',
                'bg-secondary',
                'bg-primary',
                'bg-danger',
                'bg-success',
                'bg-info',
                'bg-warning',
                'bg-dark',
            ];
        }

        public function info()
        {
            return $this->hasMany('App\Models\Info', 'user_id');
        }

        public function sharebetinfo()
        {
            return $this->hasMany('App\Models\ShareBetInfo', 'partner_id');
        }

        public function isLoggedIn()
        {
            $validTimestamp = \Carbon\Carbon::now()->subMinutes(config('session.lifetime'))->timestamp;
            $session = \App\Models\Session::where('user_id', $this->id)->where('last_activity', '>=', $validTimestamp)->first();
            return $session != null;
        }

        public function sessiondata()
        {
            $session = $this->session;
            $data = json_decode($session, true);
            return $data;
        }

        public function withdrawAll($reason = '')
        {
            \DB::beginTransaction();
            $lockUser = \App\Models\User::lockForUpdate()->find($this->id);
            if ($lockUser->playing_game != null) {
                $ct = \App\Models\Category::where('href', $lockUser->playing_game)->first();
                if ($ct != null && $ct->provider != null) {
                    if ($ct->provider == 'holdem') {
                        $data = call_user_func('\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($ct->provider) . 'Controller::terminate', $this->id);
                        if ($data['error'] == -1) {
                            return false;
                        }
                    }
                    $data = call_user_func('\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($ct->provider) . 'Controller::withdrawAll', $lockUser->playing_game, $this);
                    if ($data['error'] == false) {
                        Log::channel('monitor_game')->info('Withdraw from ' . $lockUser->username . ' amount = ' . $data['amount'] . ' at ' . $ct->provider . ' | reason = ' . $reason);
                        $lockUser->update(['playing_game' => null, 'balance' => $data['amount']]);
                        $this->balance = $data['amount']; //update current object's vale
                        $this->playing_game = null; //update current object's vale
                        \DB::commit();
                        return true;
                    } else {
                        Log::channel('monitor_game')->info('Withdraw failed ' . $lockUser->username . ' at ' . $ct->provider . ' | reason = ' . $reason);
                        \DB::commit();
                        return false;
                    }
                }

            }
            \DB::commit();
            return true;
        }

        public static function syncBalance(\App\Models\User $user, $reason = '')
        {
            \DB::beginTransaction();
            $lockUser = \App\Models\User::lockForUpdate()->find($user->id);
            if ($lockUser->playing_game == null) {
                \DB::commit();
                return $lockUser->balance;
            } else {
                $ct = \App\Models\Category::where('href', $lockUser->playing_game)->first();
                if ($ct == null || $ct->provider == null) {
                    \DB::commit();
                    return $lockUser->balance;
                } else {
                    $balance = call_user_func('\\App\\Http\\Controllers\\GameProviders\\' . strtoupper($ct->provider) . 'Controller::getUserBalance', $lockUser->playing_game, $user);
                    if ($balance >= 0) {
                        Log::channel('monitor_game')->info('SyncBalance Success | ' . strtoupper($ct->provider) . ' : ' . $lockUser->playing_game . ' : ' . $lockUser->username . '(' . $user->id . ') [old=' . $lockUser->balance . '],[new=' . $balance . ']' . ' | reason = ' . $reason);
                        $lockUser->update(['balance' => $balance, 'played_at' => time()]);
                    } else {
                        Log::channel('monitor_game')->info('SyncBalance Failed | ' . strtoupper($ct->provider) . ' : ' . $lockUser->playing_game . ' : ' . $lockUser->username . '(' . $lockUser->id . ') [old=' . $lockUser->balance . '],[new=-1]' . ' | reason = ' . $reason);
                        \DB::commit();
                        return -1;
                    }
                    \DB::commit();
                    return $balance;
                }
            }

        }
        public $export_csvUserList = [];
        public function getCSVUserList($user_id)
        {
            $user = \App\Models\User::where('id', $user_id)->first();
            $parent_name = '';
            if ($user->parent_id > 0) {
                $parent_name = $user->referral->username;
            }
            $this->export_csvUserList[] = [
                'id' => $user->id,
                'username' => $user->username,
                'parent_id' => $parent_name,
                'role_id' => trans($user->role->name),
                'phone' => $user->phone,
                'bank_name' => $user->bank_name,
                'recommender' => $user->recommender,
                'account_no' => $user->account_no,
                'balance' => $user->balance
            ];
            if ($user->role_id > 1) {
                $childUsers = User::where('parent_id', $user->id)->get();
                for ($i = 0; $i < count($childUsers); $i++) {
                    $this->getCSVUserList($childUsers[$i]->id);
                }
            }
            return $this->export_csvUserList;
        }
    }

}
