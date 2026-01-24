<?php 
namespace App\Models
{
    class CategoryTrans extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'categories_trans_kr';
        protected $fillable = [
            'category_id',
            'name', 
            'trans_title', 
        ];
        public static function boot()
        {
            parent::boot();
        }
    }

}
