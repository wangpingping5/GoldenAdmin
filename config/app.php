<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    | NOTE: This version is overwritten inside app/Providers/AppServiceProvider.php
    | and it will se application name defined from Vanguard settings page.
    |
    */

    'name' => 'MAJOR SLOTS',

    /*
    |--------------------------------------------------------------------------
    | Vanguard Version
    |--------------------------------------------------------------------------
    */
    'version' => '7.1',

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://major999.com/'),

    'server_addr' => env('APP_SERVER', 'game.major999.com'),
    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'Asia/Seoul',

    /*
    |--------------------------------------------------------------------------
    | Application Date and Date-Time Format
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default date as well as date and time
    | format used across the application.
    |
    */

    'date_format' => 'Y-m-d',

    'time_format' => 'H:i:s',

    'date_time_format' => 'Y-m-d H:i:s',

    'stylename' => env('STYLENAME', 'major'),
    'replayurl' => env('REPLAYURL', 'http://replay.pragmeticplay.net'),

    'mainserver' => env('MAIN_SERVER', '127.0.0.1'),
    /*
    CQ9 Auth key
    */
    'cq9history' => env('CQ9_HISTORY_URL', 'http://127.0.0.1/platform'),
    'cq9wtoken' => env('CQ9_WTOKEN', 'cq9KfvEKDmHTRcVMteiApFK'),
    'cq9api' => env('CQ9_API', 'https://apid.cqgame.cc'),
    'cq9token' => env('CQ9_AUTH', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyaWQiOiI2MDczYmFhYThjMDUyNTAwMDFlMDBmMDYiLCJhY2NvdW50IjoibWFqb3JfdGVzdCIsIm93bmVyIjoiNjA3M2I4ODJjZDM3NmQwMDAxNWIzOTMyIiwicGFyZW50IjoiNjA3M2I4ODJjZDM3NmQwMDAxNWIzOTMyIiwiY3VycmVuY3kiOiJLUlciLCJqdGkiOiIzODYzNjY2OTAiLCJpYXQiOjE2MTgxOTcxNjIsImlzcyI6IkN5cHJlc3MiLCJzdWIiOiJTU1Rva2VuIn0.syESetcCYmfb8MOYguVGb3wp6KUgdUtqJWJqyX-YbYk'),

    /*
    Pragmatic Play  keys
    */
    'ppmode' => env('PP_INTEGRATION_MODE', 'sw'),
    'ppsecretkey' =>  env('PP_SECRETKEY', 'hmc7ZC55a88F6DhB'),
    'ppsecurelogin' =>  env('PP_SECURELOGIN', 'mjr_major'),
    'ppapi' => env('PP_API','https://api-sg0.pragmaticplay.net/IntegrationService/v3'),
    'ppgameserver' => env('PP_SERVER','https://major-sg0.pragmaticplay.net'),
    'ppproxy' => env('PP_PROXY', ''),
    'ppverify' => env('PP_VERIFY_SERVER',''),
    
    /*
    Booongo  keys
    */
    'bng_wallet_sign_key' => env('BNG_WALLET_SIGN_KEY', 'vJITyizfNGbKSZXABlYp09zFO'),
    'bng_api_token' => env('BNG_TOKEN','ibIIBDDIgo1gHsjd3BaeH4kCE'),
    'bng_game_server' => env('BNG_SERVER','https://gate2.betsrv.com/op/'),
    'bng_project_name' => env('BNG_PRJ_NAME','major'),
    'bng_brand' => env('BNG_BRAND','major'),
    'bng_wl' => env('BNG_WL','prod'),

    /*
    HABANERO Auth key
    */
    'hbn_passkey' => env('HBN_PASSKEY', 'mA%n@ajn!Yqv996'),
    'hbn_brandid' => env('HBN_BRANDID','9fa04080-569b-eb11-b566-0050f2388fc0'),
    'hbn_apikey' => env('HBN_APIKEY','45BAC7EF-735F-4183-B885-9AFC908D4F23'),
    'hbn_api' => env('HBN_API','https://ws-a.insvr.com/jsonapi'),
    'hbn_game_server' => env('HBN_SERVER','https://app-a.insvr.com'),

    /*
    Play'n GO Auth key
    */
    'png_access_token' => env('PNG_ACCESSTOKEN', 'stagestagestagestage'),
    'png_root_url' => env('PNG_ROOT_URL', 'https://agastage.playngonetwork.com'),
    'png_pid' => env('PNG_PRODUCT_ID', '8951'),

    /*
    Evolution Auth key
    */
    'evo_apihost_slot' => env('EVO_APIHOST_SLOT', 'https://evostage.klonostek.com'),
    'evo_casinokey_slot' => env('EVO_CASINOKEY_SLOT', 'otzthxlytee8epl6'),
    'evo_authtoken_slot' => env('EVO_AUTHTOKEN_SLOT', '9a7b0809d2c89f43ab44532b7a075ac9'),
    
    'evo_apihost_live' => env('EVO_APIHOST_LIVE', 'http://staging.evolution.asia-live.com'),
    'evo_casinokey_live' => env('EVO_CASINOKEY_LIVE', '7jpwpzbumpihdled'),
    'evo_authtoken_live' => env('EVO_AUTHTOKEN_LIVE', '435ab6e9328cc23ce102f81c7daa61a9'),
    'evo_groupid_live' => env('EVO_GROUPID_LIVE', 'pn3fb7cbs3a2b3vr'),

    /*
    ATA Auth key
    */
    'ata_api' => env('ATA_API', 'https://seamless-stage.248ka.com/restless'),
    'ata_toporg' => env('ATA_TOPORG', 'MajorGroup'),
    'ata_org' => env('ATA_ORG', 'major'),
    'ata_key' => env('ATA_KEY', 'test'),

    /*
    GAC Auth key
    */
    'gac_api' => env('GAC_API', 'http://127.0.0.1'),
    'gac_key' => env('GAC_KEY', 'test'),


    /*
    ThePlus Auth key
    */
    'tp_api' => env('TP_API', 'http://127.0.0.1'),
    'tp_api_key' => env('TP_KEY', 'test'),
    'tp_api_secret' => env('TP_SECRET', 'test'),

    /*
    DreamGaming Auth key
    */
    'dg_api' => env('DG_API', 'http://127.0.0.1'),
    'dg_agent' => env('DG_AGENT', ''),
    'dg_token' => env('DG_TOKEN', 'test'),

    /*
    HPCCasion Auth key
    */
    'hpc_api' => env('HPC_API', 'http://127.0.0.1'),
    'hpc_key' => env('HPC_KEY', ''),

    /*
    NSEVolution Auth key
    */
    'nsevo_api' => env('NSEVO_API', 'http://127.0.0.1'),
    'nsevo_key' => env('NSEVO_KEY', ''),

/*
    KUZA Auth key
    */
    'kuza_api' => env('KUZA_API', 'http://127.0.0.1'),
    'kuza_key' => env('KUZA_KEY', ''),

    /*
    BANANA Auth key
    */
    'bnn_api' => env('BNN_API', 'http://127.0.0.1'),
    'bnn_key' => env('BNN_KEY', ''),


    /*
    XIMAX Auth key
    */
    'xmx_api' => env('XMX_API', 'http://127.0.0.1'),
    'xmx_key' => env('XMX_KEY', ''),
    'xmx_op' => env('XMX_OP', ''),
    'xmx_prefix' => env('XMX_PREFIX', ''),

    /*
    KTEN Auth key
    */
    'kten_api' => env('KTEN_API', 'http://127.0.0.1'),
    'kten_op' => env('KTEN_OP', ''),
    'kten_key' => env('KTEN_KEY', ''),

    /*
    SPOGAME Auth key
    */
    'spg_api' => env('SPG_API', 'http://127.0.0.1'),
    'spg_secret' => env('SPG_SECRET', ''),
    'spg_key' => env('SPG_KEY', ''),

    /*
    TAISHAN Auth key
    */
    'taishan_api' => env('TAISHAN_API', 'http://127.0.0.1'),
    'taishan_op' => env('TAISHAN_OP', ''),
    'taishan_pass' => env('TAISHAN_PASS', ''),
    'taishan_token' => env('TAISHAN_TOKEN', ''),

    /*
    BABYLON Auth key
    */
    'babylon_api' => env('BABYLON_API', 'http://127.0.0.1'),
    'babylon_op' => env('BABYLON_OP', ''),
    'babylon_key' => env('BABYLON_KEY', ''),

    /*
    OSSCOIN 
    */
    'osscoin_url' => env('OSSCOIN_URL', 'https://testapi.osscoin.net'),
    'osscoin_user_url' => env('OSSCOIN_USER_URL', 'https://test.osscoin.net'),

    'admin_ip' => env('ADMINIP', '127.0.0.1'),
    'checkadmip' => env('CHECKADMIP', false),

    /*
    Holdem API
    */
    'holdem_api' => env('HOLDEM_API', ''),
    'holdem_opcode' => env('HOLDEM_OP', ''),
    'holdem_opkey' => env('HOLDEM_KEY', ''),

    /*
    Nexus API
    */
    'nexus_api' => env('NEXUS_API', ''),
    'nexus_agent' => env('NEXUS_AGENT', ''),
    'nexus_secretkey' => env('NEXUS_SECRETKEY', ''),

    /*
    HDLIVE API
    */
    'hdlive_api' => env('HDLIVE_API', ''),
    'hdlive_agent' => env('HDLIVE_AGENT', ''),
    /*
    
    /*
    RG Auth key
    */
    'rg_api' => env('RG_API', ''),
    'rg_key' => env('RG_KEY', ''),
    
    /*
    Tower API
    */
    'tower_api' => env('TOWER_API', ''),
    'tower_site' => env('TOWER_SITE', ''),
    'tower_key' => env('TOWER_KEY', ''),

    /*
    Gamzilo API
    */
    'gamzilo_api' => env('GAMZILO_API', ''),
    'gamzilo_agent' => env('GAMZILO_AGENT', ''),
    'gamzilo_token' => env('GAMZILO_TOKEN', ''),
    'gamzilo_secret' => env('GAMZILO_SECRET', ''),

    /*
    Truxbet API
    */
    'truxbet_api' => env('TRUXBET_API', ''),
    'truxbet_agent' => env('TRUXBET_AGENT', ''),
    'truxbet_token' => env('TRUXBET_TOKEN', ''),
    'truxbet_secret' => env('TRUXBET_SECRET', ''),
    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => env('APP_LOCALE', 'ko'),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY', 'base64:V8er1MML6Tq0POF8KBQQwHBi3BYUciqsRn4BE5GAoOA='),
    'salt' => '9748104773',
    'cipher' => 'AES-256-CBC',

    /*
    HONOR Auth key
    */
    'honor_api' => env('HONOR_API', ''),
    'honor_key' => env('HONOR_KEY', ''),

    /*
    GOLDCASINO Auth key
    */
    'gold_api' => env('GOLD_API', ''),
    'gold_op' => env('GOLD_OP', ''),
    'gold_key' => env('GOLD_KEY', ''),
    
    /*
    SC4CASINO Auth key
    */
    'sc4_api' => env('SC4_API', ''),
    'sc4_key' => env('SC4_KEY', ''),

    /*
    SPORTS Auth key
    */
    'bti_api' => env('BTI_API', ''),
    'bti_key' => env('BTI_KEY', ''),
    /*
    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /**
         * Third-Party Service Providers
         */

        Proengsoft\JsValidation\JsValidationServiceProvider::class,
        Anhskohbo\NoCaptcha\NoCaptchaServiceProvider::class,
        //Laravel\Socialite\SocialiteServiceProvider::class,
        App\Providers\HtmlServiceProvider::class,
        Intervention\Image\ImageServiceProvider::class,
        anlutro\LaravelSettings\ServiceProvider::class,
        Jenssegers\Agent\AgentServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Services\Auth\Api\JWTServiceProvider::class,
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
		
		jeremykenedy\LaravelRoles\RolesServiceProvider::class,
		
        Yajra\DataTables\DataTablesServiceProvider::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App'       => Illuminate\Support\Facades\App::class,
        'Artisan'   => Illuminate\Support\Facades\Artisan::class,
        'Auth'      => Illuminate\Support\Facades\Auth::class,
        'Blade'     => Illuminate\Support\Facades\Blade::class,
        'Bus'       => Illuminate\Support\Facades\Bus::class,
        'Cache'     => Illuminate\Support\Facades\Cache::class,
        'Config'    => Illuminate\Support\Facades\Config::class,
        'Cookie'    => Illuminate\Support\Facades\Cookie::class,
        'Crypt'     => Illuminate\Support\Facades\Crypt::class,
        'DB'        => Illuminate\Support\Facades\DB::class,
        'Eloquent'  => Illuminate\Database\Eloquent\Model::class,
        'Event'     => Illuminate\Support\Facades\Event::class,
        'File'      => Illuminate\Support\Facades\File::class,
        'Gate'      => Illuminate\Support\Facades\Gate::class,
        'Hash'      => Illuminate\Support\Facades\Hash::class,
        'Input'     => Illuminate\Support\Facades\Input::class,
        'Inspiring' => Illuminate\Foundation\Inspiring::class,
        'Lang'      => Illuminate\Support\Facades\Lang::class,
        'Log'       => Illuminate\Support\Facades\Log::class,
        'Mail'      => Illuminate\Support\Facades\Mail::class,
        'Password'  => Illuminate\Support\Facades\Password::class,
        'Queue'     => Illuminate\Support\Facades\Queue::class,
        'Redirect'  => Illuminate\Support\Facades\Redirect::class,
        'Redis'     => Illuminate\Support\Facades\Redis::class,
        'Request'   => Illuminate\Support\Facades\Request::class,
        'Response'  => Illuminate\Support\Facades\Response::class,
        'Route'     => Illuminate\Support\Facades\Route::class,
        'Schema'    => Illuminate\Support\Facades\Schema::class,
        'Session'   => Illuminate\Support\Facades\Session::class,
        'Storage'   => Illuminate\Support\Facades\Storage::class,
        'URL'       => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View'      => Illuminate\Support\Facades\View::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,

        'JsValidator' => Proengsoft\JsValidation\Facades\JsValidatorFacade::class,
        'Socialite' => Laravel\Socialite\Facades\Socialite::class,
        'Form' => Collective\Html\FormFacade::class,
        'HTML' => Collective\Html\HtmlFacade::class,
        'Image' => Intervention\Image\Facades\Image::class,
        'Settings' => anlutro\LaravelSettings\Facade::class,
        'JWTAuth' => Tymon\JWTAuth\Facades\JWTAuth::class,
        'Agent' => Jenssegers\Agent\Facades\Agent::class,

        'DataTables' => Yajra\DataTables\Facades\DataTables::class,

    ],

];
