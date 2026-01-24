<?php 
namespace App\Http\Controllers\GameProviders
{
    class DBConnectionLoader 
    {
        public static function configDBConnection($brand)
        {
            $site = \App\Models\WebSite::Where('brand',$brand)->first();
            if ($site)
            {
                if (!config()->has('database.connections.' . $site->brand))
                {
                    \Config::set('database.connections.' . $site->brand, [
                        'driver' => 'mysql',
                        'url' => env('DATABASE_URL'),
                        'host' => $site->dbhost,
                        'port' => $site->dbport,
                        'database' => $site->dbdatabase,
                        'username' => $site->dbusername,
                        'password' => $site->dbpassword,
                        'unix_socket' => env('DB_SOCKET', ''),
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci',
                        'prefix' => 'w_',
                        'prefix_indexes' => true,
                        'strict' => false,
                        'engine' => null,
                        'options' => extension_loaded('pdo_mysql') ? array_filter([
                            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                        ]) : [],
                    ]);
                }
                return true;
            }
            else
            {
                return false;
            }
        }
    }
}
