<?php 
namespace App\Http\Controllers\Backend
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
            $this->users = $users;
        }
        public function getLogin(\Illuminate\Http\Request $request)
        {
            $site = \App\Models\WebSite::where('backend', $request->root())->first();
            if (!$site)
            {
                return response()->view('system.pages.siteisclosed', [], 200)->header('Content-Type', 'text/html');
            }
            return view('auth.login', compact('site'));
        }
        public function postLogin(\App\Http\Requests\Auth\LoginRequest $request, \App\Repositories\Session\SessionRepository $sessionRepository)
        {
           
            $siteMaintence = env('MAINTENANCE', 0);
            

            $throttles = settings('throttle_enabled');
            if( $throttles && $this->hasTooManyLoginAttempts($request) ) 
            {
                return $this->sendLockoutResponse($request);
            }
            $credentials = $request->getCredentials();
            $users = \App\Models\User::where('username', $credentials['username'])->get();
            $validateusers = [];
            if(count($users) == 0){
                return redirect()->to(argon_route('auth.login'))->withErrors(trans('auth.failed'));
            }else{
                $idvalidate = false;
                foreach($users as $subuser)
                {
                    if(\Auth::getProvider()->validateCredentials($subuser, $credentials))
                    {
                        $validateusers[] = $subuser;      
                    }
                }
            }
            if(count($validateusers) == 0)
            {
                return redirect()->to(argon_route('auth.login'))->withErrors(trans('auth.failed'));
            }

            //check admin id per site
            $site = \App\Models\WebSite::where('backend', $request->root())->get();
            $adminid = [0]; //default admin id
            if (count($site) > 0)
            {
                $adminid = $site->pluck('adminid')->toArray();
            }
            $user = null;
            foreach($validateusers as $validateuser)
            {
                $validateadmin = $validateuser;
                if($validateuser->hasRole(['admin','group']))
                {
                    $user = $validateuser;
                    break;
                }
                while($validateadmin != null && !$validateadmin->isInoutPartner())
                {
                    $validateadmin = $validateadmin->referral;
                }
                if (in_array($validateadmin->id, $adminid))
                {
                    $user = $validateuser;
                    break;
                }
            }
            if($user == null)
            {
                return redirect()->to(argon_route('auth.login'))->withErrors(trans('auth.failed'));
            }
            $admin = $user;
            if (!$admin->hasRole(['admin','group']))
            {

                while ($admin !=null && !$admin->hasRole('comaster'))
                {
                    if ($admin->status == \App\Support\Enum\UserStatus::DELETED)
                    {
                        return redirect()->to(argon_route('auth.login'))->withErrors(trans('DeletedAccount'));
                    }
                    if ($admin->status == \App\Support\Enum\UserStatus::BANNED)
                    {
                        return redirect()->to(argon_route('auth.login'))->withErrors(trans('AccountTemporarilySuspended'));
                    }
                    if ($admin->status == \App\Support\Enum\UserStatus::JOIN || $admin->status == \App\Support\Enum\UserStatus::UNCONFIRMED)
                    {
                        return redirect()->to(argon_route('auth.login'))->withErrors(trans('ProcessingRegister'));
                    }
                    if ($admin->status == \App\Support\Enum\UserStatus::REJECTED)
                    {
                        return redirect()->to(argon_route('auth.login'))->withErrors(trans('RejectREgister'));
                    }
                    
                    $admin = $admin->referral;
                }

                if (!$admin || !in_array($admin->id, $adminid))
                {
                    return redirect()->to(argon_route('auth.login'))->withErrors(trans('auth.failed'));
                }
                if (!$user->isInoutPartner())
                {
                    foreach ($site as $web)
                    {
                        if ($web->adminid == $admin->id && $web->status == 0)
                        {
                            return redirect()->to(argon_route('auth.login'))->withErrors(trans('SiteMaintenance'));
                        }
                    }
                }

                if ($admin->status == \App\Support\Enum\UserStatus::DELETED)
                {
                    return redirect()->to(argon_route('auth.login'))->withErrors(trans('DeletedAccount'));
                }
                if ($admin->status == \App\Support\Enum\UserStatus::BANNED)
                {
                    return redirect()->to(argon_route('auth.login'))->withErrors(trans('AccountTemporarilySuspended'));
                }
                if ($admin->status == \App\Support\Enum\UserStatus::JOIN || $admin->status == \App\Support\Enum\UserStatus::UNCONFIRMED)
                {
                    return redirect()->to(argon_route('auth.login'))->withErrors(trans('ProcessingRegister'));
                }
                if ($admin->status == \App\Support\Enum\UserStatus::REJECTED)
                {
                    return redirect()->to(argon_route('auth.login'))->withErrors(trans('RejectREgister'));
                }
            }

            if( !$user->hasRole(['admin']) && $siteMaintence==1 ) 
            {
                \Auth::logout();
                return redirect()->to(argon_route('auth.login'))->withErrors([trans('SiteMaintenance')]);
            }
            
            if( !$user->hasRole(['admin','group']) && setting('siteisclosed') ) 
            {
                \Auth::logout();
                return redirect()->to(argon_route('auth.login'))->withErrors(trans('app.site_is_turned_off'));
            }
            if( $user->hasRole([
                1, 
                2, 
                3
            ]) && (!$user->shop || $user->shop->is_blocked || $user->shop->pending != 0) ) 
            {
                return redirect()->to(argon_route('auth.login'))->withErrors(trans('app.your_shop_is_blocked'));
            }
            if( $user->isBanned() ) 
            {
                return redirect()->to(argon_route('auth.login'))->withErrors(trans('app.your_account_is_banned'));
            }

            // block Internet Explorer
            $ua = $request->header('User-Agent');
            if (str_contains($ua,'Trident') )
            {
                return redirect()->to(argon_route('auth.login'))->withErrors([trans('UseChrome')]);
            }
            
            \Auth::login($user, settings('remember_me') && $request->get('remember'));
            if(!$user->isInoutPartner())
            {
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
            }
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
                        return redirect()->to(argon_route('auth.login'))->withErrors([trans('AccessFromUnauthorizedDevice')]);
                    }
                }
            }
            event(new \App\Events\User\LoggedIn());

            return redirect()->to(argon_route('dashboard'));
        }
        public function getLogout(\Illuminate\Http\Request $request,  \App\Repositories\Session\SessionRepository $sessionRepository)
        {
            $forceall = $request->all;
            if ($forceall == '1')
            {
                $sessionRepository->invalidateAllSessionsForUser(auth()->user()->id);
            }
            event(new \App\Events\User\LoggedOut());
            \Auth::logout();
            return redirect()->to(argon_route('auth.login'));
        }

        public function reloadCaptcha(){
            return response()->json(['captcha'=>captcha_img('math')]);
        }

        public function postCaptcha(\Illuminate\Http\Request $request){
            $request->validate([
                'username' => 'required',
                'password' => 'required',
                'captcha' => 'required|captcha'
            ]);
            return "The form has been successfully submitted!";
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
    }

}
