<?php 
namespace App\Models
{
    class GameCategory extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'game_categories';
        protected $fillable = [
            'game_id', 
            'category_id'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
        public function category()
        {
            return $this->belongsTo('App\Models\Category');
        }
        public function game()
        {
            return $this->belongsTo('App\Models\Game');
        }
    }

}
