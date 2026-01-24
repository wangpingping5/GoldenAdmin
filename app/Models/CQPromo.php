<?php 
namespace App\Models
{
    class CQPromo extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'cqpromo';
        protected $fillable = [
            'name', 
            'promoid', 
            'promourl',
            'imageurl',
            'icon_png',
            'icon_json',
            'haslink'
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }
}
