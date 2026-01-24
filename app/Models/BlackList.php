<?php 
namespace App\Models
{
    class BlackList extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'blacklist';
        protected $fillable = [
            'name', 
            'phone', 
            'account_bank', 
            'account_name', 
            'account_number', 
            'memo',
        ];
        public static function boot()
        {
            parent::boot();
        }
    }

}
