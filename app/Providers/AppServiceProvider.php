<?php

namespace App\Providers;

use Carbon\Carbon;
use App\Repositories\Activity\ActivityRepository;
use App\Repositories\Activity\EloquentActivity;
use App\Repositories\Country\CountryRepository;
use App\Repositories\Country\EloquentCountry;
use App\Repositories\Permission\EloquentPermission;
use App\Repositories\Permission\PermissionRepository;
use App\Repositories\Role\EloquentRole;
use App\Repositories\Role\RoleRepository;
use App\Repositories\Session\DbSession;
use App\Repositories\Session\SessionRepository;
use App\Repositories\User\EloquentUser;
use App\Repositories\User\UserRepository;
use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use ImLiam\ThrottleSimultaneousRequests\ThrottleSimultaneousRequests;
class AppServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        if (env('FORCE_HTTPS') == true)
        {
            URL::forceScheme('https');
            // $this->app['request']->server->set('HTTPS','on');
        }
        Carbon::setLocale(config('app.locale'));
        config(['app.name' => settings('app_name')]);
        \Illuminate\Database\Schema\Builder::defaultStringLength(191);

        // Enable pagination
        if (!Collection::hasMacro('paginate')) {

            Collection::macro('paginate',
                function ($perPage = 15, $page = null, $options = []) {
                    $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
                    return (new LengthAwarePaginator(
                        $this->forPage($page, $perPage)->values()->all(), $this->count(), $perPage, $page, $options))
                        ->withPath('');
                });
        }
        // $layout = 'Default';
        // $backendurl = 'backend';
        // $site = \App\Models\WebSite::where('backend', \Request::root())->first();
        // if ($site)
        // {
        //     $layout = $site->backend;
        //     if (strpos($layout, 'Dark') === 0)
        //     {
        //         $backendurl = 'slot';
        //     }
            
        //     $backendtheme = ['Dark', 'Default', 'Left', 'Top'];
        //     if (!in_array($layout, $backendtheme))
        //     {
        //         $backendurl = $layout;
        //     }
        // }
        $layout = '';
        $backendurl = '';
        $site = \App\Models\WebSite::where('backend', \Request::root())->first();
        if(isset($site)){
            config(['app.title' => $site->title]);
            config(['app.logo' => $site->frontend]);    
        }else{
            config(['app.title' => '메이저']);
            config(['app.logo' => '']);    
        }
        \View::share('layout', $layout);
        \View::share('admurl', $backendurl);
        config(['app.admurl' => $backendurl]);
        config(['app.slug' => $layout]);
    }
    
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(UserRepository::class, EloquentUser::class);
        $this->app->singleton(ActivityRepository::class, EloquentActivity::class);
        $this->app->singleton(RoleRepository::class, EloquentRole::class);
        $this->app->singleton(PermissionRepository::class, EloquentPermission::class);
        $this->app->singleton(SessionRepository::class, DbSession::class);
        $this->app->singleton(CountryRepository::class, EloquentCountry::class);
        $this->app->singleton(ThrottleSimultaneousRequests::class);

        if ($this->app->environment('local')) {
            //$this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
            //$this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }
    }
}
