<?php 
namespace App\Http\Controllers\Web\Backend\Auth
{
    class AuthController extends \App\Http\Controllers\Controller
    {
        private $users = null;
        public function __construct(\App\Repositories\User\UserRepository $users)
        {
            $this->middleware('guest', [
                'except' => ['getLogout']
            ]);
            $this->middleware('auth', [
                'only' => ['getLogout']
            ]);
            $this->middleware('registration', [
                'only' => [
                    'getRegister', 
                    'postRegister'
                ]
            ]);
            $this->users = $users;
        }
        public function getLogin(\Illuminate\Http\Request $request)
        {
            $directories = [];
            foreach( glob(resource_path() . '/lang/*', GLOB_ONLYDIR) as $fileinfo ) 
            {
                $dirname = basename($fileinfo);
                $directories[$dirname] = $dirname;
            }
            $title = settings('app_name');
            $site = \App\Models\WebSite::where('domain', $request->root())->first();
            if ($site)
            {
                $title = $site->title;
            }
            else
            {
                return response()->view('system.pages.siteisclosed', [], 200)->header('Content-Type', 'text/html');
            }
            return view('auth.login', compact('directories','title', 'site'));
        }
        public function postLogin(\App\Http\Requests\Auth\LoginRequest $request, \App\Repositories\Session\SessionRepository $sessionRepository)
        {
            $siteMaintence = env('MAINTENANCE', 0);

            if( $siteMaintence==1 ) 
            {
                \Auth::logout();
                return redirect()->to('backend/login')->withErrors([trans('SiteMaintenance')]);
            }

            $isCashier = false;
            $throttles = settings('throttle_enabled');
            $to = ($request->has('to') ? '?to=' . $request->get('to') : '');
            if( $throttles && $this->hasTooManyLoginAttempts($request) ) 
            {
                return $this->sendLockoutResponse($request);
            }
            $credentials = $request->getCredentials();
            if( !\Auth::validate($credentials) ) 
            {
                if( $throttles ) 
                {
                    $this->incrementLoginAttempts($request);
                }
                return redirect()->to('backend/login' . $to)->withErrors(trans('auth.failed'));
            }
            $user = \Auth::getProvider()->retrieveByCredentials($credentials);
            if ($user->role_id == 2) //it is co-master
            {
                $user = $user->referral;
                $isCashier = true;
            }
            //check admin id per site
            $site = \App\Models\WebSite::where('domain', $request->root())->get();
            $adminid = [0]; //default admin id
            if (count($site) > 0)
            {
                $adminid = $site->pluck('adminid')->toArray();
            }

            $admin = $user;
            if (!$admin->hasRole('admin'))
            {

                while ($admin !=null && !$admin ->hasRole('comaster'))
                {
                    if ($admin->status == \App\Support\Enum\UserStatus::DELETED)
                    {
                        return redirect()->to('backend/login' . $to)->withErrors(trans('DeletedAccount'));
                    }
                    if (!$admin->isActive())
                    {
                        return redirect()->to('backend/login' . $to)->withErrors(trans('AccountTemporarilySuspended'));
                    }
                    $admin = $admin->referral;
                }

                if (!$admin || !in_array($admin->id, $adminid))
                {
                    return redirect()->to('backend/login' . $to)->withErrors(trans('auth.failed'));
                }

                if (!$admin->isActive())
                {
                    return response()->json(['error' => true, 'msg' => trans('AccountTemporarilySuspended')]);
                }
            }

            if( $request->lang ) 
            {
                $user->update(['language' => $request->lang]);
            }
            if( !$user->hasRole('admin') && setting('siteisclosed') ) 
            {
                \Auth::logout();
                return redirect()->route(config('app.admurl').'.auth.login')->withErrors(trans('app.site_is_turned_off'));
            }
            if( $user->hasRole([
                1, 
                2, 
                3
            ]) && (!$user->shop || $user->shop->is_blocked) ) 
            {
                return redirect()->to('backend/login' . $to)->withErrors(trans('app.your_shop_is_blocked'));
            }
            if( $user->isBanned() ) 
            {
                return redirect()->to('backend/login' . $to)->withErrors(trans('app.your_account_is_banned'));
            }
            // block Internet Explorer
            $ua = $request->header('User-Agent');
            if (str_contains($ua,'Trident') )
            {
                return redirect()->to('backend/login' . $to)->withErrors([trans('UseChrome')]);
            }
            
            \Auth::login($user, settings('remember_me') && $request->get('remember'));
            if( settings('reset_authentication') && count($sessionRepository->getUserSessions(\Auth::id())) ) 
            {
                foreach( $sessionRepository->getUserSessions($user->id) as $session ) 
                {
                    if( $session->id != session()->getId() ) 
                    {
                        $sessionRepository->invalidateSession($session->id);
                    }
                }
            }
            $request->session()->put('isCashier',  $isCashier);
            return $this->handleUserWasAuthenticated($request, $throttles, $user);
        }
        protected function handleUserWasAuthenticated(\Illuminate\Http\Request $request, $throttles, $user)
        {
            if( $throttles ) 
            {
                $this->clearLoginAttempts($request);
            }
            if (auth()->user()->hasRole('admin') && config('app.checkadmip'))
            {
                //check ip address
                $ip = $request->server('HTTP_CF_CONNECTING_IP')??$request->server('REMOTE_ADDR');
                if (strpos($ip, ':') === false) // not mobile 
                {
                    $allowedIp = explode(',', config('app.admin_ip'));
                    if (!in_array($ip, $allowedIp))
                    {
                        event(new \App\Events\User\IrLoggedIn());
                        \Auth::logout();
                        return redirect('/backend/login')->withErrors(trans('AccessFromUnauthorizedDevice'));
                    }
                }

            }
            event(new \App\Events\User\LoggedIn());
            if( $request->has('to') ) 
            {
                return redirect()->to($request->get('to'));
            }
            if (config('app.admurl') == 'slot')
            {
                return redirect()->route(config('app.admurl').'.dashboard');
            }
            if( !\Auth::user()->hasPermission('dashboard') ) 
            {
                return redirect()->route(config('app.admurl').'.user.list');
            }
            return redirect()->route(config('app.admurl').'.dashboard');
        }
        public function getLogout()
        {
            event(new \App\Events\User\LoggedOut());
            \Auth::logout();
            return redirect(route(config('app.admurl').'.auth.login'));
        }
        
        public function loginUsername()
        {
            return 'username';
        }
        protected function hasTooManyLoginAttempts(\Illuminate\Http\Request $request)
        {
            return app('Illuminate\Cache\RateLimiter')->tooManyAttempts($request->input($this->loginUsername()) . $request->ip(), $this->maxLoginAttempts());
        }
        protected function incrementLoginAttempts(\Illuminate\Http\Request $request)
        {
            app('Illuminate\Cache\RateLimiter')->hit($request->input($this->loginUsername()) . $request->ip(), $this->lockoutTime() / 60);
        }
        protected function retriesLeft(\Illuminate\Http\Request $request)
        {
            $attempts = app('Illuminate\Cache\RateLimiter')->attempts($request->input($this->loginUsername()) . $request->ip());
            return $this->maxLoginAttempts() - $attempts + 1;
        }
        protected function sendLockoutResponse(\Illuminate\Http\Request $request)
        {
            $seconds = app('Illuminate\Cache\RateLimiter')->availableIn($request->input($this->loginUsername()) . $request->ip());
            return redirect(route(config('app.admurl').'.auth.login'))->withInput($request->only($this->loginUsername(), 'remember'))->withErrors([$this->loginUsername() => $this->getLockoutErrorMessage($seconds)]);
        }
        protected function getLockoutErrorMessage($seconds)
        {
            return trans('auth.throttle', ['seconds' => $seconds]);
        }
        protected function clearLoginAttempts(\Illuminate\Http\Request $request)
        {
            app('Illuminate\Cache\RateLimiter')->clear($request->input($this->loginUsername()) . $request->ip());
        }
        protected function maxLoginAttempts()
        {
            return settings('throttle_attempts', 5);
        }
        protected function lockoutTime()
        {
            $lockout = (int)settings('throttle_lockout_time');
            if( $lockout <= 1 ) 
            {
                $lockout = 1;
            }
            return 60 * $lockout;
        }
        public function getRegister()
        {
            return view('backend.Default.auth.register');
        }
        public function postRegister(\App\Http\Requests\Auth\RegisterRequest $request, \App\Repositories\Role\RoleRepository $roles)
        {
            $user = $this->users->create(array_merge($request->only('username', 'password'), ['status' => \App\Support\Enum\UserStatus::ACTIVE]));
            $role = \jeremykenedy\LaravelRoles\Models\Role::where('name', '=', 'User')->first();
            $user->attachRole($role);
            event(new \App\Events\User\Registered($user));
            return redirect(route(config('app.admurl').'.auth.login'))->with('success', trans('app.account_created_login'));
        }
    }

}
