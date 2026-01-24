<?php 
namespace App\Models
{
    class EvoSkins extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'evoskins';
        protected $fillable = [
            'skin', 
            'min',
            'max',
            'nexus_skin',
            'rg_skin',
        ];
        public $timestamps = false;
        public static function boot()
        {
            parent::boot();
        }
    }

}
