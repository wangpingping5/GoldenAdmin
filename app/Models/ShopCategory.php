<?php 
namespace App\Models
{
    class ShopCategory extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'shop_categories';
        protected $fillable = [
            'shop_id', 
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
    }

}
