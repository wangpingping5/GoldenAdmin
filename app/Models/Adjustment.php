<?php 
namespace App\Models
{
    class Adjustment 
    {
        public $partner;
        public $game;
        public $total_bet = 0.00;
        public $total_win = 0.00;
        public $total_in = 0.00;
        public $total_out = 0.00;
        public $deal_balance = 0.00;
        public $mileage = 0.00;
        public $total_profit = 0.00;
        public $category_names = '';
        public $total_bet_count = 0;
        public $total_percent = 0;
        public $total_percent_jps = 0; 
        public $total_percent_jpg = 0;
        public $total_deal = 0;
        public $total_mileage = 0;
        public $total_real_profit = 0;
        public $open_shift;
    }
}