<?php 
namespace App\Models
{
    class GameSummary extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'game_summary';
        protected $fillable = [
            'user_id',
            'category_id', 
            'shop_id',
            'name',
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
            'game_id',
            'total_ggr',
            'total_ggr_mileage'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
        
        public function game()
        {
            return $this->belongsTo('App\Models\Game');
        }
    }

}
